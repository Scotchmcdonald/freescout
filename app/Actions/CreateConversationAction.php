<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\Mailbox;
use App\Models\Thread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Action class for creating new conversations.
 *
 * Encapsulates the business logic for creating a conversation with all
 * related entities (customer, threads) in a single transaction.
 */
class CreateConversationAction
{
    /**
     * Execute the action to create a new conversation.
     *
     * @param  Mailbox  $mailbox  The mailbox to create the conversation in
     * @param  User  $user  The user creating the conversation
     * @param  array<string, mixed>  $data  Validated data from the request
     * @return Conversation The created conversation
     *
     * @throws \Exception If customer or inbox folder cannot be found/created
     */
    public function execute(Mailbox $mailbox, User $user, array $data): Conversation
    {
        return DB::transaction(function () use ($mailbox, $user, $data) {
            $customer = $this->resolveCustomer($data);
            $customerEmail = $this->getCustomerEmail($customer, $data);
            $folder = $this->getInboxFolder($mailbox);
            $number = $this->getNextConversationNumber($mailbox);

            $conversation = $this->createConversation(
                $mailbox,
                $customer,
                $folder,
                $user,
                $customerEmail,
                $number,
                $data
            );

            $this->createInitialThread($conversation, $user, $mailbox, $customerEmail, $data);

            // Allow modules to modify conversation after creation
            $conversation = \Eventy::filter('conversation.create', $conversation);

            return $conversation;
        });
    }

    /**
     * Resolve or create a customer from the provided data.
     */
    private function resolveCustomer(array $data): Customer
    {
        if (! empty($data['customer_id'])) {
            return Customer::findOrFail($data['customer_id']);
        }

        $customer = Customer::create($data['customer_email'], [
            'first_name' => $data['customer_first_name'] ?? '',
            'last_name' => $data['customer_last_name'] ?? '',
        ]);

        if (! $customer) {
            throw new \Exception('Failed to create customer with email: '.$data['customer_email']);
        }

        return $customer;
    }

    /**
     * Get the customer email address.
     */
    private function getCustomerEmail(Customer $customer, array $data): string
    {
        return $customer->getMainEmail() ?? $data['customer_email'];
    }

    /**
     * Get the inbox folder for the mailbox.
     */
    private function getInboxFolder(Mailbox $mailbox): \App\Models\Folder
    {
        $folder = $mailbox->folders()->where('type', 1)->first();

        if (! $folder) {
            throw new \Exception('Inbox folder not found for mailbox: '.$mailbox->name);
        }

        return $folder;
    }

    /**
     * Get the next conversation number for the mailbox.
     */
    private function getNextConversationNumber(Mailbox $mailbox): int
    {
        $maxNumber = $mailbox->conversations()->max('number');

        return (is_int($maxNumber) ? $maxNumber : 0) + 1;
    }

    /**
     * Create the conversation record.
     */
    private function createConversation(
        Mailbox $mailbox,
        Customer $customer,
        \App\Models\Folder $folder,
        User $user,
        string $customerEmail,
        int $number,
        array $data
    ): Conversation {
        return Conversation::create([
            'mailbox_id' => $mailbox->id,
            'customer_id' => $customer->id,
            'folder_id' => $folder->id,
            'user_id' => $data['assign_to'] ?? null,
            'number' => $number,
            'subject' => $data['subject'],
            'type' => Conversation::TYPE_EMAIL,
            'status' => $data['status'] ?? Conversation::STATUS_ACTIVE,
            'state' => Conversation::STATE_PUBLISHED,
            'source_via' => Conversation::SOURCE_VIA_USER,
            'source_type' => Conversation::SOURCE_TYPE_WEB,
            'customer_email' => $customerEmail,
            'preview' => mb_substr(strip_tags($data['body']), 0, 255),
            'created_by_user_id' => $user->id,
            'last_reply_at' => now(),
        ]);
    }

    /**
     * Create the initial thread for the conversation.
     */
    private function createInitialThread(
        Conversation $conversation,
        User $user,
        Mailbox $mailbox,
        string $customerEmail,
        array $data
    ): Thread {
        return Thread::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'type' => Thread::TYPE_MESSAGE,
            'status' => Thread::STATUS_ACTIVE,
            'state' => Thread::STATE_PUBLISHED,
            'source_via' => Thread::SOURCE_VIA_USER,
            'source_type' => Thread::SOURCE_TYPE_WEB,
            'body' => $data['body'],
            'from' => $mailbox->email,
            'to' => json_encode([$customerEmail]),
            'first' => true,
        ]);
    }
}
