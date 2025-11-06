# Quick Reference: Email System Commands

## Essential Commands

### Fetch Emails
```bash
# Fetch from all mailboxes
php artisan freescout:fetch-emails

# Fetch from specific mailbox (ID)
php artisan freescout:fetch-emails 1

# Fetch with verbose output
php artisan freescout:fetch-emails 1 --verbose
```

### Test Event System
```bash
# Manually trigger events
php artisan freescout:test-events

# Watch logs for event activity
tail -f storage/logs/laravel.log | grep "SendAutoReply\|CustomerCreated"
```

### Check Logs
```bash
# View last 50 lines
tail -50 storage/logs/laravel.log

# Follow logs in real-time
tail -f storage/logs/laravel.log

# Search for specific errors
grep "ERROR\|Exception" storage/logs/laravel.log

# Check IMAP fetch results
grep "IMAP fetch completed" storage/logs/laravel.log
```

### Database Queries
```bash
# Count conversations
php artisan tinker --execute="echo \App\Models\Conversation::count() . ' conversations' . PHP_EOL;"

# Count threads
php artisan tinker --execute="echo \App\Models\Thread::count() . ' threads' . PHP_EOL;"

# Count customers
php artisan tinker --execute="echo \App\Models\Customer::count() . ' customers' . PHP_EOL;"

# Count attachments (total and embedded)
php artisan tinker --execute="echo 'Total: ' . \App\Models\Attachment::count() . ', Embedded: ' . \App\Models\Attachment::where('embedded', 1)->count() . PHP_EOL;"

# Check mailbox settings
php artisan tinker --execute="var_dump(\App\Models\Mailbox::find(1)->only(['name', 'email', 'auto_reply_enabled']));"
```

## File Locations

### Core Services
- **IMAP Service**: `app/Services/ImapService.php`
- **Mail Helper**: `app/Misc/MailHelper.php`

### Event System
- **Events**: `app/Events/CustomerCreatedConversation.php`, `CustomerReplied.php`
- **Listeners**: `app/Listeners/SendAutoReply.php`
- **Provider**: `app/Providers/EventServiceProvider.php`

### Commands
- **Fetch Emails**: `app/Console/Commands/FetchEmails.php`
- **Test Events**: `app/Console/Commands/TestEventSystem.php`

### Models
- **Conversation**: `app/Models/Conversation.php`
- **Thread**: `app/Models/Thread.php`
- **Customer**: `app/Models/Customer.php`
- **Mailbox**: `app/Models/Mailbox.php`
- **Attachment**: `app/Models/Attachment.php`

### Configuration
- **Bootstrap**: `bootstrap/app.php` (provider registration)
- **Mail Config**: `config/mail.php`

## Common Tasks

### Enable Auto-Reply for Mailbox
```bash
php artisan tinker
```
```php
$mailbox = \App\Models\Mailbox::find(1);
$mailbox->auto_reply_enabled = true;
$mailbox->auto_reply_subject = 'Thank you for contacting us';
$mailbox->auto_reply_message = 'We have received your message and will respond shortly.';
$mailbox->save();
```

### Check Recent Conversations
```bash
php artisan tinker
```
```php
\App\Models\Conversation::latest()->take(5)->get(['id', 'number', 'subject', 'created_at']);
```

### Find Conversations with Inline Images
```bash
php artisan tinker
```
```php
\App\Models\Conversation::whereHas('threads', function($q) {
    $q->whereRaw('body LIKE "%/storage/app/public/attachments/%"');
})->count();
```

### Check Embedded Attachments
```bash
php artisan tinker
```
```php
\App\Models\Attachment::where('embedded', 1)->get(['id', 'file_name', 'conversation_id']);
```

## Event System Verification

### Check Events Are Firing
1. Run email fetch: `php artisan freescout:fetch-emails 1`
2. Check logs: `grep "Fired CustomerCreatedConversation" storage/logs/laravel.log`
3. Verify listener: `grep "SendAutoReply listener triggered" storage/logs/laravel.log`

### Test Events Manually
```bash
php artisan freescout:test-events
tail -20 storage/logs/laravel.log
```

Expected output in logs:
```
[2025-11-05 01:28:33] local.INFO: SendAutoReply listener triggered 
{"conversation_id":35,"customer_email":"nicole.lammatao@borealtek.ca","mailbox_id":1}

[2025-11-05 01:28:33] local.DEBUG: Auto-reply disabled for mailbox {"mailbox_id":1}
```

## Troubleshooting

### No Emails Fetched
```bash
# Check IMAP connection
grep "IMAP connection established" storage/logs/laravel.log

# Check mailbox credentials
php artisan tinker --execute="var_dump(\App\Models\Mailbox::find(1)->only(['email', 'in_server', 'in_port']));"

# Test OAuth token
php artisan tinker
```
```php
$mailbox = \App\Models\Mailbox::find(1);
$token = json_decode($mailbox->oauth_token, true);
echo "Token expires: " . date('Y-m-d H:i:s', $token['expires']) . "\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n";
```

### Events Not Firing
```bash
# Check provider is registered
grep "EventServiceProvider" bootstrap/app.php

# Check event classes exist
ls -la app/Events/CustomerCreatedConversation.php

# Check listener exists
ls -la app/Listeners/SendAutoReply.php

# Manually test events
php artisan freescout:test-events
```

### Inline Images Not Displaying
```bash
# Check attachment has content-id
php artisan tinker
```
```php
$attachment = \App\Models\Attachment::where('embedded', 1)->first();
echo "File: " . $attachment->file_name . "\n";
echo "Embedded: " . $attachment->embedded . "\n";

// Check thread body for replacement
$thread = \App\Models\Thread::find($attachment->thread_id);
echo strpos($thread->body, $attachment->file_name) !== false ? "URL found in body" : "URL NOT found";
```

### Permissions Issues
```bash
# Fix storage permissions
chmod -R 775 storage
chown -R www-data:www-data storage

# Fix attachment directory
chmod -R 775 storage/app/public/attachments
```

## Performance Monitoring

### Check Processing Speed
```bash
grep "IMAP fetch completed" storage/logs/laravel.log | tail -5
```

### Monitor Memory Usage
```bash
# During fetch
watch -n 1 'ps aux | grep "artisan freescout:fetch-emails"'
```

### Check Database Size
```bash
php artisan tinker
```
```php
echo "Conversations: " . \App\Models\Conversation::count() . "\n";
echo "Threads: " . \App\Models\Thread::count() . "\n";
echo "Customers: " . \App\Models\Customer::count() . "\n";
echo "Attachments: " . \App\Models\Attachment::count() . "\n";
```

## Development Tips

### Add Debug Logging
```php
// In any file
\Log::debug('My debug message', ['key' => 'value']);
\Log::info('Info message', ['data' => $data]);
\Log::error('Error message', ['exception' => $e->getMessage()]);
```

### Test with Specific Email
```php
// In ImapService.php
$messages = $query->get()->filter(function($msg) {
    return str_contains($msg->getSubject()->toString(), 'Test');
});
```

### Clear Logs
```bash
# Truncate log file
> storage/logs/laravel.log

# Or delete and recreate
rm storage/logs/laravel.log
touch storage/logs/laravel.log
chmod 664 storage/logs/laravel.log
```

## Next Steps

1. **Test Inline Images**:
   - Send email with embedded image
   - Run fetch command
   - Verify image displays

2. **Implement Full Auto-Reply**:
   - Create SendAutoReply job
   - Add email sending logic
   - Test with real customer

3. **Add More Features**:
   - User email replies
   - Bounce detection
   - Auto-responder detection

## Documentation Links

- **Implementation Status**: `EMAIL_SYSTEM_STATUS.md`
- **Session Summary**: `SESSION_3_SUMMARY.md`
- **Deep Review**: `IMAP_IMPLEMENTATION_REVIEW.md`
- **Architecture**: `MODERNIZATION_INDEX.md`

---

**Last Updated**: 2025-11-05  
**System Status**: ✅ Operational  
**Event System**: ✅ Verified Working
