<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Exceptions\ConnectionFailedException;

class ImapService
{
    /**
     * Fetch emails from a mailbox.
     *
     * @return array{fetched: int, created: int, errors: int, messages: array<int, string>}
     */
    public function fetchEmails(Mailbox $mailbox): array
    {
        $stats = [
            'fetched' => 0,
            'created' => 0,
            'errors' => 0,
            'messages' => [],
        ];

        if (empty($mailbox->in_server)) {
            $message = "No IMAP server configured for mailbox: {$mailbox->name}";
            $stats['messages'][] = $message;
            Log::warning('IMAP fetch skipped - no server configured', [
                'mailbox_id' => $mailbox->id,
                'mailbox_name' => $mailbox->name,
            ]);

            return $stats;
        }

        Log::info('Starting IMAP fetch', [
            'mailbox_id' => $mailbox->id,
            'mailbox_name' => $mailbox->name,
            'server' => $mailbox->in_server,
            'port' => $mailbox->in_port,
        ]);

        try {
            $client = $this->createClient($mailbox);
            $client->connect();

            Log::debug('IMAP connection established', [
                'mailbox_id' => $mailbox->id,
            ]);

            // Safely explode folders - handle string or array
            $folderPathsRaw = $mailbox->in_imap_folders;
            if (is_array($folderPathsRaw)) {
                $folderPaths = $folderPathsRaw;
                // @phpstan-ignore-next-line - in_imap_folders can be null from DB despite PHPDoc
            } elseif ($folderPathsRaw && trim((string) $folderPathsRaw) !== '') {
                $folderPaths = explode(',', (string) $folderPathsRaw);
            } else {
                $folderPaths = ['INBOX'];
            }

            foreach ($folderPaths as $folderPath) {
                $folder = $client->getFolder($folderPath);
                if (! $folder) {
                    Log::warning('IMAP folder not found, skipping.', [
                        'mailbox_id' => $mailbox->id,
                        'folder' => $folderPath,
                    ]);
                    continue;
                }

                Log::debug('Processing folder', [
                    'mailbox_id' => $mailbox->id,
                    'folder' => $folderPath,
                ]);

                // Use query builder approach like original FreeScout
                // Get unseen messages from the last 3 days
                $messages_query = $folder->query()
                    ->since(now()->subDays(3))
                    ->unseen()
                    ->leaveUnread();

                // Handle charset issues with Gmail/Microsoft
                $messages = collect([]);
                $last_error = '';

                try {
                    $messages = $messages_query->get();

                    if (method_exists($client, 'getLastError')) {
                        $last_error = $client->getLastError();
                    }
                } catch (\Exception $e) {
                    $last_error = $e->getMessage();
                }

                // Solution for MS mailboxes that don't support charset
                if ($last_error && stristr($last_error, 'The specified charset is not supported')) {
                    Log::warning('Retrying without charset', [
                        'mailbox_id' => $mailbox->id,
                    ]);

                    $messages = $folder->query()
                        ->since(now()->subDays(3))
                        ->unseen()
                        ->leaveUnread()
                        ->setCharset(null)
                        ->get();
                }

                $stats['fetched'] += $messages->count();

                Log::info('Found unread messages', [
                    'mailbox_id' => $mailbox->id,
                    'folder' => $folderPath,
                    'count' => $messages->count(),
                ]);

                // Sort messages by date to ensure chronological processing
                $sortedMessages = $messages->sortBy(function ($message) {
                    return $message->getDate();
                });

                foreach ($sortedMessages as $message) {
                    try {
                        $messageId = $message->getMessageId();
                        Log::debug('Processing message', [
                            'mailbox_id' => $mailbox->id,
                            'message_id' => $messageId,
                        ]);

                        $this->processMessage($mailbox, $message);
                        $stats['created']++;

                        // Mark as seen
                        $message->setFlag('Seen');

                        Log::info('Message processed successfully', [
                            'mailbox_id' => $mailbox->id,
                            'message_id' => $messageId,
                        ]);
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        $errorMsg = 'Error processing message: '.$e->getMessage();
                        $stats['messages'][] = $errorMsg;
                        Log::error('IMAP message processing error', [
                            'mailbox_id' => $mailbox->id,
                            'message_id' => $messageId ?? 'unknown',
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                        ]);
                    }
                }
            }

            $client->disconnect();

            Log::info('IMAP fetch completed', [
                'mailbox_id' => $mailbox->id,
                'fetched' => $stats['fetched'],
                'created' => $stats['created'],
                'errors' => $stats['errors'],
            ]);
        } catch (ConnectionFailedException $e) {
            $stats['errors']++;
            $errorMsg = 'Connection failed: '.$e->getMessage();
            $stats['messages'][] = $errorMsg;
            Log::error('IMAP connection failed', [
                'mailbox_id' => $mailbox->id,
                'mailbox_name' => $mailbox->name,
                'server' => $mailbox->in_server,
                'port' => $mailbox->in_port,
                'encryption' => $this->getEncryption($mailbox->in_encryption),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } catch (\Exception $e) {
            $stats['errors']++;
            $errorMsg = 'Error: '.$e->getMessage();
            $stats['messages'][] = $errorMsg;
            Log::error('IMAP fetch error', [
                'mailbox_id' => $mailbox->id,
                'mailbox_name' => $mailbox->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $stats;
    }

    /**
     * Create IMAP client for mailbox.
     */
    protected function createClient(Mailbox $mailbox): \Webklex\PHPIMAP\Client
    {
        $encryption = $this->getEncryption($mailbox->in_encryption);

        $config = [
            'host' => $mailbox->in_server,
            'port' => $mailbox->in_port,
            'encryption' => $encryption,
            'validate_cert' => $mailbox->in_validate_cert ?? true,
            'username' => $mailbox->in_username,
            'password' => $mailbox->in_password,
            'protocol' => 'imap',
            'timeout' => 30,
        ];

        $cm = new ClientManager;

        return $cm->make($config);
    }

    /**
     * Get encryption protocol.
     */
    protected function getEncryption(int|string|null $encryption): ?string
    {
        // Convert string to int if needed
        if (is_string($encryption)) {
            $encryption = (int) $encryption;
        }

        return match ($encryption) {
            1 => 'ssl',
            2 => 'tls',
            default => null,
        };
    }

    /**
     * Process an email message.
     */
    protected function processMessage(Mailbox $mailbox, \Webklex\PHPIMAP\Message $message): void
    {
        DB::beginTransaction();

        try {
            // Get or create customer
            $from = $message->getFrom();

            // Convert to array if it's an Attribute object
            // Note: The IMAP library returns Attribute objects that can be converted to arrays
            if (is_object($from)) {
                if (method_exists($from, 'toArray')) {
                    $from = $from->toArray();
                } elseif (method_exists($from, 'get')) {
                    $from = $from->get();
                }
            }

            if (! is_array($from) || empty($from)) {
                throw new \Exception('No sender found in message');
            }

            // Get first sender - it's an Address object
            $fromAddress = reset($from);

            // The Address object can be accessed as a string or has methods
            if (is_object($fromAddress)) {
                // Use the Address object methods
                // @phpstan-ignore-next-line - IMAP extension Address object properties
                $fromEmail = method_exists($fromAddress, 'mail') ? $fromAddress->mail : null;
                // @phpstan-ignore-next-line - IMAP extension Address object properties
                $fromName = method_exists($fromAddress, 'personal') ? $fromAddress->personal : '';

                // If mail is not a property, try as array access or string parsing
                if (! $fromEmail) {
                    // @phpstan-ignore-next-line - IMAP extension Address object cast
                    $addressString = (string) $fromAddress;
                    // Parse "Name <email@example.com>" format
                    if (preg_match('/<([^>]+)>/', $addressString, $matches)) {
                        $fromEmail = $matches[1];
                        $fromName = trim(str_replace('<'.$fromEmail.'>', '', $addressString));
                    } else {
                        $fromEmail = $addressString;
                    }
                }
            } elseif (is_array($fromAddress)) {
                $fromEmail = $fromAddress['mail'] ?? $fromAddress['email'] ?? null;
                $fromName = $fromAddress['personal'] ?? $fromAddress['name'] ?? '';
            } else {
                // It's a string
                $fromEmail = $fromAddress;
                $fromName = '';
            }

            if (! $fromEmail) {
                throw new \Exception('No sender email found in message');
            }

            Log::debug('Processing message from', [
                'from_email' => $fromEmail,
                'from_name' => $fromName,
            ]);

            // Check if sender is an internal user
            /** @var \App\Models\User|null $senderUser */
            $senderUser = \App\Models\User::where('email', $fromEmail)->first();

            if ($senderUser) {
                Log::debug('Sender is an internal user', [
                    'user_id' => $senderUser->id,
                    'user_name' => $senderUser->getFullName(),
                ]);
            }

            // Parse name and limit length (first_name is VARCHAR(20) in database)
            $nameParts = explode(' ', $fromName, 2);
            $firstName = $nameParts[0];
            $lastName = $nameParts[1] ?? '';

            // Limit first name to 20 characters
            if (strlen($firstName) > 20) {
                $firstName = mb_substr($firstName, 0, 20);
            }
            // Limit last name to 30 characters (database field size)
            if (strlen($lastName) > 30) {
                $lastName = mb_substr($lastName, 0, 30);
            }

            // Use the original FreeScout Customer::create() method
            $customer = Customer::create($fromEmail, [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ]);

            if (! $customer) {
                throw new \Exception('Failed to create/find customer for email: '.$fromEmail);
            }

            Log::debug('Customer identified', [
                'customer_id' => $customer->id,
                'email' => $fromEmail,
            ]);

            // Check if conversation already exists by Message-ID
            $messageIdRaw = $message->getMessageId();

            // Convert Attribute to string if needed (IMAP library returns Attribute objects)
            $messageId = (string) $messageIdRaw;

            if (! $messageId || trim($messageId) === '') {
                Log::warning('Message has no Message-ID header, generating one');
                $messageId = '<'.uniqid('freescout-', true).'@'.($mailbox->in_server ?? 'localhost').'>';
            }

            /** @var \App\Models\Thread|null $existingThread */
            $existingThread = Thread::where('message_id', $messageId)->first();

            // Handle messages sent to multiple mailboxes (e.g. via BCC)
            $isExtraImport = false;
            if ($existingThread) {
                $recipients = array_merge(
                    $this->parseAddresses($message->getTo()),
                    $this->parseAddresses($message->getCc())
                );

                // If current mailbox is not in To/Cc and the existing thread is in another mailbox,
                // it's likely a BCC, so we should import it.
                /** @var \App\Models\Conversation $existingConversation */
                $existingConversation = $existingThread->conversation;
                if (! in_array($mailbox->email, $recipients) && $existingConversation->mailbox_id != $mailbox->id) {
                    $isExtraImport = true;
                    // Generate an artificial message ID to avoid unique constraint violation
                    $messageId = \App\Misc\MailHelper::generateMessageId($messageId, $mailbox->id.$messageId);
                    Log::info('BCC to another mailbox detected. Creating new thread with artificial Message-ID.', [
                        'original_message_id' => $existingThread->message_id,
                        'new_message_id' => $messageId,
                        'mailbox_id' => $mailbox->id,
                    ]);
                } else {
                    Log::info('Message already exists (duplicate), skipping', [
                        'message_id' => $messageId,
                        'thread_id' => $existingThread->id,
                    ]);
                    DB::rollBack();

                    return;
                }
            }

            // Get subject (IMAP library returns Attribute objects)
            $subjectRaw = $message->getSubject();
            $subject = (string) $subjectRaw;
            if (! $subject || trim($subject) === '') {
                $subject = '(No Subject)';
            }

            // Check if this is a reply (has In-Reply-To or References header)
            $header = $message->getHeader();
            $inReplyTo = $header?->get('in_reply_to')?->first();
            $references = $header?->get('references')?->first();

            $conversation = null;

            if (($inReplyTo || $references) && ! $isExtraImport) {
                Log::debug('Message appears to be a reply', [
                    'in_reply_to' => $inReplyTo,
                    'references' => $references,
                ]);

                // Try to find existing conversation
                $replyToMessageId = $inReplyTo ?: $references;
                /** @var \App\Models\Thread|null $parentThread */
                $parentThread = Thread::where('message_id', $replyToMessageId)->first();

                if ($parentThread) {
                    /** @var \App\Models\Conversation $conversation */
                    $conversation = $parentThread->conversation;
                    Log::debug('Found existing conversation for reply', [
                        'conversation_id' => $conversation->id,
                    ]);
                } else {
                    Log::debug('Could not find parent thread, will create new conversation');
                }
            }

            // Create new conversation if not found
            if (! $conversation) {
                $maxNumber = $mailbox->conversations()->max('number');
                $number = (is_int($maxNumber) ? $maxNumber : 0) + 1;
                /** @var \App\Models\Folder|null $folder */
                $folder = $mailbox->folders()->where('type', 1)->first(); // Inbox

                if (! $folder) {
                    throw new \Exception("No inbox folder found for mailbox {$mailbox->id}");
                }

                /** @var \App\Models\Conversation $conversation */
                $conversation = Conversation::create([
                    'mailbox_id' => $mailbox->id,
                    'customer_id' => $customer->id,
                    'folder_id' => $folder->id,
                    'number' => $number,
                    'subject' => $subject,
                    'type' => 1, // Email
                    'status' => 1, // Active
                    'state' => 2, // Published
                    'source_via' => 2, // Customer
                    'source_type' => 1, // Email
                    'customer_email' => $fromEmail,
                    'preview' => mb_substr(strip_tags($message->getTextBody()), 0, 255),
                    'last_reply_at' => now(),
                ]);

                Log::info('Created new conversation', [
                    'conversation_id' => $conversation->id,
                    'number' => $number,
                    'subject' => $subject,
                ]);
            }

            // Get email body
            $isHtml = $message->hasHTMLBody();
            $body = $isHtml ? $message->getHTMLBody() : $message->getTextBody();

            if (empty($body)) {
                Log::warning('Message has no body content');
                $body = '(Empty message)';
            }

            // Handle forwarded emails to create tickets (@fwd command)
            if (
                ! $inReplyTo && ! $references && // Not a reply
                preg_match("/^[[:alpha:]]{1,3}\s*:(.*)/i", (string) $subject) && // Starts with Fwd:, Re:, etc.
                str_starts_with(strtolower(trim(strip_tags($body))), '@fwd')
            ) {
                // Try to get original sender from the forwarded body
                $originalSender = $this->getOriginalSenderFromFwd($body);
                $isUser = \App\Models\User::where('email', $fromEmail)->exists();

                if ($originalSender && $isUser) {
                    Log::debug('Processing as forwarded message', [
                        'original_sender' => $originalSender,
                        'forwarder' => $fromEmail,
                    ]);
                    // Overwrite sender details
                    $fromEmail = $originalSender['email'];
                    $fromName = $originalSender['name'];
                    $nameParts = explode(' ', $fromName, 2);
                    $firstName = $nameParts[0];
                    $lastName = $nameParts[1] ?? '';

                    // Clean body
                    $body = trim((string) preg_replace("/@fwd([\s<]+)/su", '$1', $body));
                }
            }

            // Separate reply from quoted text
            $body = $this->separateReply($body, $isHtml, (bool) ($inReplyTo || $references));

            // Get recipients - parse Address objects properly
            $to = $this->parseAddresses($message->getTo());
            $cc = $this->parseAddresses($message->getCc());
            $bcc = $this->parseAddresses($message->getBcc());

            // Create customer records for all participants (original FreeScout behavior)
            $this->createCustomersFromMessage($message, $mailbox);

            // Update conversation if it's a reply to an existing one
            if ($conversation && $conversation->exists) {
                // Update conversation metadata
                $conversation->customer_id = $customer->id;
                $conversation->customer_email = $fromEmail;
                $conversation->status = 1; // Active
                $conversation->last_reply_at = now();
                $conversation->last_reply_from = 2; // Customer

                // Update CC list - merge existing CC with new recipients
                $existingCcRaw = $conversation->cc;
                $existingCc = is_array($existingCcRaw) ? $existingCcRaw : [];
                $newCc = array_unique(array_merge($existingCc, $cc, array_diff($to, [$mailbox->email])));
                $conversation->cc = ! empty($newCc) ? $newCc : null;

                // Update BCC only if the new message has BCC
                if (! empty($bcc)) {
                    $conversation->bcc = $bcc;
                }

                $conversation->save();

                Log::debug('Updated existing conversation', [
                    'conversation_id' => $conversation->id,
                    'status' => $conversation->status,
                ]);
            }

            // Create thread
            $threadData = [
                'conversation_id' => $conversation->id,
                'type' => 1, // Message
                'status' => 1, // Active
                'state' => 2, // Published
                'body' => $body,
                'from' => $fromEmail,
                'to' => json_encode($to),
                'cc' => ! empty($cc) ? json_encode($cc) : null,
                'bcc' => ! empty($bcc) ? json_encode($bcc) : null,
                'message_id' => $messageId,
                'headers' => method_exists($message, 'getRawHeader')
                    ? $message->getRawHeader()
                    : ($message->getHeader() && method_exists($message->getHeader(), '__toString') ? (string) $message->getHeader() : ''),
                'first' => $conversation->threads_count === 0,
            ];

            // If sender is an internal user, set created_by_user_id and mark as from user
            if ($senderUser) {
                $threadData['created_by_user_id'] = $senderUser->id;
                $threadData['user_id'] = $senderUser->id; // Assignee is the user who replied
                $threadData['source_via'] = 1; // User
                $threadData['source_type'] = 1; // Email

                // Update conversation to show last reply was from user
                $conversation->last_reply_from = 1; // User
                $conversation->save();
            } else {
                // Reply from customer
                $threadData['customer_id'] = $customer->id;
                $threadData['source_via'] = 2; // Customer
                $threadData['source_type'] = 1; // Email
            }

            /** @var \App\Models\Thread $thread */
            $thread = Thread::create($threadData);

            Log::info('Created thread', [
                'thread_id' => $thread->id,
                'conversation_id' => $conversation->id,
            ]);

            // Track if this is a new conversation or reply
            $isNewConversation = $thread->first;

            // Handle attachments with inline image support
            $savedAttachments = [];
            $hasNonEmbeddedAttachments = false;

            if ($message->hasAttachments()) {
                $attachments = $message->getAttachments();

                Log::debug('Processing attachments', [
                    'count' => count($attachments),
                ]);

                foreach ($attachments as $attachment) {
                    try {
                        /** @var \Webklex\PHPIMAP\Attachment $attachment */
                        $filename = $attachment->getName();
                        $content = $attachment->getContent();
                        $contentId = $attachment->getId();

                        if (empty($filename)) {
                            Log::warning('Attachment has no filename, skipping');
                            continue;
                        }

                        // Store attachment
                        $path = storage_path('app/attachments/'.$conversation->id);
                        if (! file_exists($path)) {
                            mkdir($path, 0755, true);
                        }

                        $uniqueFilename = uniqid().'_'.$filename;
                        $filepath = $path.'/'.$uniqueFilename;
                        file_put_contents($filepath, $content);

                        // Check if this is an embedded/inline image
                        $isEmbedded = false;
                        $disposition = '';

                        if (property_exists($attachment, 'disposition') && is_string($attachment->disposition)) {
                            $disposition = strtolower($attachment->disposition);
                        }

                        // Check if attachment has CID and appears in body
                        if ($contentId && strpos($body, 'cid:'.$contentId) !== false) {
                            $isEmbedded = true;
                        } elseif ($disposition === 'inline') {
                            $isEmbedded = true;
                        }

                        $attachmentModel = \App\Models\Attachment::create([
                            'thread_id' => $thread->id,
                            'conversation_id' => $conversation->id,
                            'file_name' => $uniqueFilename,
                            'file_dir' => 'attachments/'.$conversation->id,
                            'file_size' => strlen($content),
                            'mime_type' => $attachment->getContentType(),
                            'embedded' => $isEmbedded,
                        ]);

                        $savedAttachments[] = [
                            'model' => $attachmentModel,
                            'content_id' => $contentId,
                            'is_embedded' => $isEmbedded,
                        ];

                        if (! $isEmbedded) {
                            $hasNonEmbeddedAttachments = true;
                        }

                        Log::debug('Saved attachment', [
                            'filename' => $filename,
                            'size' => strlen($content),
                            'embedded' => $isEmbedded,
                            'content_id' => $contentId,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Failed to save attachment', [
                            'error' => $e->getMessage(),
                            'filename' => $filename ?? 'unknown',
                        ]);
                        // Continue processing other attachments
                    }
                }

                // Replace CID references in body with attachment URLs
                $bodyUpdated = false;
                foreach ($savedAttachments as $attachmentData) {
                    if ($attachmentData['content_id'] && $attachmentData['is_embedded']) {
                        $cid = 'cid:'.$attachmentData['content_id'];
                        $url = url('storage/attachments/'.$conversation->id.'/'.basename($attachmentData['model']->file_name));

                        if (strpos($body, $cid) !== false) {
                            $body = str_replace($cid, $url, $body);
                            $bodyUpdated = true;

                            Log::debug('Replaced CID reference', [
                                'cid' => $cid,
                                'url' => $url,
                            ]);
                        }
                    }
                }

                // Update thread body if CID references were replaced
                if ($bodyUpdated) {
                    $thread->body = $body;
                    $thread->save();
                }

                // Update conversation has_attachments flag
                if ($hasNonEmbeddedAttachments) {
                    $conversation->has_attachments = true;
                    $conversation->save();
                }

                Log::info('Processed attachments', [
                    'total' => count($attachments),
                    'saved' => count($savedAttachments),
                    'embedded' => count(array_filter($savedAttachments, fn ($a) => $a['is_embedded'])),
                    'regular' => count(array_filter($savedAttachments, fn ($a) => ! $a['is_embedded'])),
                ]);
            }

            // Update conversation
            $conversation->update([
                'threads_count' => $conversation->threads_count + 1,
                'last_reply_at' => now(),
            ]);

            // Fire appropriate Laravel event
            if ($isNewConversation) {
                event(new \App\Events\CustomerCreatedConversation($conversation, $thread, $customer));
                Log::debug('Fired CustomerCreatedConversation event');
            } else {
                // For replies (not new conversations)
                if ($senderUser) {
                    // Internal user replied via email - don't fire CustomerReplied event
                    Log::debug('Internal user replied via email', ['user_id' => $senderUser->id]);
                } else {
                    event(new \App\Events\CustomerReplied($conversation, $thread, $customer));
                    Log::debug('Fired CustomerReplied event');
                }
            }

            // Broadcast real-time notification for new message
            event(new \App\Events\NewMessageReceived($thread, $conversation));
            Log::debug('Fired NewMessageReceived broadcast event');

            DB::commit();

            Log::info('Email processed successfully', [
                'mailbox_id' => $mailbox->id,
                'conversation_id' => $conversation->id,
                'thread_id' => $thread->id,
                'subject' => $subject,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to process message', [
                'mailbox_id' => $mailbox->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Test IMAP connection.
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(Mailbox $mailbox): array
    {
        $result = [
            'success' => false,
            'message' => '',
        ];

        try {
            $client = $this->createClient($mailbox);
            $client->connect();

            $folder = $client->getFolder('INBOX');

            if (! $folder) {
                throw new \Exception('Could not access INBOX folder');
            }

            // Try to get unseen messages count like in original FreeScout
            $messages_query = $folder->query()
                ->since(now()->subDays(1))
                ->leaveUnread();

            try {
                $messages = $messages_query->get();
                $messageCount = $messages->count();

                // Count unseen
                $unseenCount = 0;
                foreach ($messages as $message) {
                    /** @var \Webklex\PHPIMAP\Message $message */
                    if (! $message->hasFlag('Seen')) {
                        $unseenCount++;
                    }
                }

                $client->disconnect();

                $result['success'] = true;
                $result['message'] = "Connected successfully. Found {$messageCount} messages in INBOX ({$unseenCount} unread).";
            } catch (\Exception $e) {
                // If charset issue, try without charset
                if (stristr($e->getMessage(), 'charset')) {
                    $messages = $folder->query()
                        ->since(now()->subDays(1))
                        ->leaveUnread()
                        ->setCharset(null)
                        ->get();

                    $messageCount = $messages->count();
                    $unseenCount = 0;
                    foreach ($messages as $message) {
                        if (! $message->hasFlag('Seen')) {
                            $unseenCount++;
                        }
                    }

                    $client->disconnect();

                    $result['success'] = true;
                    $result['message'] = "Connected successfully. Found {$messageCount} messages in INBOX ({$unseenCount} unread).";
                } else {
                    throw $e;
                }
            }
        } catch (ConnectionFailedException $e) {
            $result['message'] = 'Connection failed: '.$e->getMessage();
        } catch (\Exception $e) {
            $result['message'] = 'Error: '.$e->getMessage();
        }

        return $result;
    }

    /**
     * Separate reply from quoted text.
     * Matches original FreeScout implementation.
     */
    protected function separateReply(string $body, bool $isHtml, bool $isReply): string
    {
        if (! $isReply) {
            return $body;
        }

        if ($isHtml) {
            // Extract content from <body> tag if present
            if (preg_match("/<body[^>]*>(.*?)<\/body>/is", $body, $matches)) {
                $body = $matches[1];
            }
        } else {
            $body = nl2br($body);
        }

        // List of reply separators, from most to least specific
        $separators = [
            '<div class="protonmail_quote">', // ProtonMail
            '---- Replied Above ----', // Generic separator
            'On(.*)wrote:', // "On [date], [sender] wrote:"
            'From: ', // Forwarded message header
            '________', // Underscore separator
        ];

        foreach ($separators as $separator) {
            $parts = preg_split('/'.preg_quote($separator, '/').'/i', $body);
            if ($parts !== false && count($parts) > 1) {
                // Check if the part before the separator has actual content
                if (trim(strip_tags($parts[0]))) {
                    return $parts[0];
                }
            }
        }

        return $body;
    }

    /**
     * Get original sender from a forwarded email body.
     * Matches original FreeScout implementation.
     *
     * @return array{email: string, name: string}|null
     */
    protected function getOriginalSenderFromFwd(string $body): ?array
    {
        // Clean up body for better matching
        $cleanBody = (string) preg_replace("/[\"']cid:/", '!', $body);
        $cleanBody = (string) preg_replace("/@fwd([\s<]+)/isu", '$1', $cleanBody);

        // Regex to find "From: Name <email@example.com>"
        if (preg_match('/From:\s*(.*?)\s*<([^>]+)>/i', $cleanBody, $matches)) {
            return [
                'name' => trim($matches[1]),
                'email' => trim($matches[2]),
            ];
        }

        // Regex to find just an email address
        if (preg_match("/[\"'<:;]([^\"'<:;!@\s]+@[^\"'>:&@\s]+)[\"'>:&]/", $cleanBody, $matches)) {
            $emailRaw = preg_replace('#.*&lt;(.*)&gt.*#', '$1', $matches[1]);
            $emailSanitized = is_string($emailRaw) ? \App\Models\Email::sanitizeEmail($emailRaw) : false;

            if ($emailSanitized) {
                return [
                    'name' => '',
                    'email' => $emailSanitized,
                ];
            }
        }

        return null;
    }

    /**
     * Create customer records for all participants in an email.
     * Matches original FreeScout implementation.
     */
    protected function createCustomersFromMessage(\Webklex\PHPIMAP\Message $message, Mailbox $mailbox): void
    {
        $mailboxEmails = [$mailbox->email];

        // Collect all email addresses from the message
        $allAddresses = array_merge(
            $this->getAddressesWithNames($message->getFrom()),
            $this->getAddressesWithNames($message->getReplyTo()),
            $this->getAddressesWithNames($message->getTo()),
            $this->getAddressesWithNames($message->getCc()),
            $this->getAddressesWithNames($message->getBcc())
        );

        foreach ($allAddresses as $addressData) {
            // Skip if this is the mailbox's own email
            if (in_array($addressData['email'], $mailboxEmails)) {
                continue;
            }

            // Create or update customer
            Customer::create($addressData['email'], [
                'first_name' => $addressData['first_name'],
                'last_name' => $addressData['last_name'],
            ]);
        }
    }

    /**
     * Get email addresses with names from IMAP address objects.
     *
     * @return array<int, array{email: string, first_name: string, last_name: string}>
     */
    protected function getAddressesWithNames(mixed $addresses): array
    {
        if (empty($addresses)) {
            return [];
        }

        // Convert Attribute to array
        if (is_object($addresses) && get_class($addresses) === 'Webklex\PHPIMAP\Attribute') {
            $addresses = $addresses->get();
        }

        if (! is_array($addresses)) {
            return [];
        }

        $result = [];
        foreach ($addresses as $addr) {
            $email = null;
            $name = '';

            if (is_object($addr)) {
                $email = $addr->mail ?? $addr->email ?? null;
                $name = $addr->personal ?? $addr->name ?? '';

                // If mail is not a property, try parsing the string representation
                if (! $email) {
                    // @phpstan-ignore-next-line - IMAP extension Address object cast
                    $addressString = (string) $addr;
                    if (preg_match('/<([^>]+)>/', $addressString, $matches)) {
                        $email = $matches[1];
                        $name = trim(str_replace('<'.$email.'>', '', $addressString));
                    } else {
                        $email = $addressString;
                    }
                }
            } elseif (is_array($addr)) {
                $email = $addr['mail'] ?? $addr['email'] ?? null;
                $name = $addr['personal'] ?? $addr['name'] ?? '';
            } elseif (is_string($addr)) {
                $email = $addr;
            }

            if (is_string($email)) {
                $nameParts = explode(' ', $name, 2);
                $firstName = isset($nameParts[0]) ? $nameParts[0] : '';
                $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
                $result[] = [
                    'email' => $email,
                    'first_name' => strlen($firstName) <= 20 ? $firstName : mb_substr($firstName, 0, 20),
                    'last_name' => strlen($lastName) <= 30 ? $lastName : mb_substr($lastName, 0, 30),
                ];
            }
        }

        return $result;
    }

    /**
     * Parse email addresses from IMAP Attribute object.
     *
     * @return array<int, string>
     */
    protected function parseAddresses(mixed $addresses): array
    {
        if (empty($addresses)) {
            return [];
        }

        // Convert Attribute to array
        if (is_object($addresses) && get_class($addresses) === 'Webklex\PHPIMAP\Attribute') {
            $addresses = $addresses->get();
        }

        if (! is_array($addresses)) {
            return [];
        }

        $result = [];
        foreach ($addresses as $addr) {
            if (is_object($addr)) {
                // Try to get email as property
                $email = $addr->mail ?? $addr->email ?? null;

                // If not a property, try parsing the string representation
                if (! $email) {
                    // @phpstan-ignore-next-line - IMAP extension Address object cast
                    $addressString = (string) $addr;
                    if (preg_match('/<([^>]+)>/', $addressString, $matches)) {
                        $email = $matches[1];
                    } else {
                        $email = $addressString;
                    }
                }

                if (is_string($email)) {
                    $result[] = $email;
                }
            } elseif (is_array($addr)) {
                $email = $addr['mail'] ?? $addr['email'] ?? null;
                if (is_string($email)) {
                    $result[] = $email;
                }
            } elseif (is_string($addr)) {
                $result[] = $addr;
            }
        }

        return $result;
    }
}
