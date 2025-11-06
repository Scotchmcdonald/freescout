# Email System Testing Guide

**Date:** November 4, 2025  
**Purpose:** Step-by-step guide to test the email system implementation

---

## Prerequisites

Before testing, ensure:
- [ ] Application is running (`php artisan serve` or web server configured)
- [ ] Database is migrated and seeded
- [ ] Queue worker is running: `php artisan queue:work`
- [ ] You have access to a test email account with IMAP/SMTP enabled
- [ ] Admin user account exists

---

## Test Email Account Setup

### Option 1: Gmail Test Account (Recommended for Testing)

1. **Create/Use Gmail Account**
   - Go to https://accounts.google.com
   - Create a new account or use existing test account

2. **Enable 2-Factor Authentication**
   - Go to https://myaccount.google.com/security
   - Enable 2-Step Verification

3. **Generate App Password**
   - Go to https://myaccount.google.com/apppasswords
   - Select "Mail" and "Other (Custom name)"
   - Name it "FreeScout Test"
   - Copy the generated 16-character password

4. **Settings for FreeScout:**
   ```
   SMTP Settings:
   - Server: smtp.gmail.com
   - Port: 587
   - Encryption: TLS (2)
   - Username: your-email@gmail.com
   - Password: [16-char app password]
   
   IMAP Settings:
   - Server: imap.gmail.com
   - Port: 993
   - Protocol: IMAP (1)
   - Encryption: SSL (1)
   - Username: your-email@gmail.com
   - Password: [16-char app password]
   - Validate Certificate: Yes
   ```

### Option 2: Mailtrap (For Development Only)

Mailtrap captures emails without sending them to real inboxes.

1. **Sign up at https://mailtrap.io**
2. **Get SMTP Credentials** (SMTP tab)
   ```
   SMTP Settings:
   - Server: sandbox.smtp.mailtrap.io
   - Port: 587 or 2525
   - Encryption: TLS (2)
   - Username: [from Mailtrap]
   - Password: [from Mailtrap]
   ```

**Note:** Mailtrap doesn't support IMAP, so it's only for testing outbound emails.

### Option 3: Microsoft 365 / Outlook

1. **Create/Use Microsoft Account**
2. **Enable App Password** (if 2FA enabled)
3. **Settings for FreeScout:**
   ```
   SMTP Settings:
   - Server: smtp.office365.com
   - Port: 587
   - Encryption: TLS (2)
   - Username: your-email@outlook.com
   - Password: [your password or app password]
   
   IMAP Settings:
   - Server: outlook.office365.com
   - Port: 993
   - Protocol: IMAP (1)
   - Encryption: SSL (1)
   - Username: your-email@outlook.com
   - Password: [your password or app password]
   - Validate Certificate: Yes
   ```

---

## Testing Procedure

### Phase 1: Database Setup

1. **Check Mailbox Exists**
   ```bash
   cd /var/www/html
   php artisan tinker
   ```
   
   ```php
   // In tinker:
   \App\Models\Mailbox::count();
   // Should return at least 1
   
   $mailbox = \App\Models\Mailbox::first();
   echo $mailbox->name;
   echo $mailbox->email;
   
   exit
   ```

2. **Create Test Mailbox (if needed)**
   ```bash
   php artisan tinker
   ```
   
   ```php
   $mailbox = \App\Models\Mailbox::create([
       'name' => 'Test Support',
       'email' => 'your-test-email@gmail.com',
       'out_method' => 3, // SMTP
       'out_server' => 'smtp.gmail.com',
       'out_port' => 587,
       'out_username' => 'your-test-email@gmail.com',
       'out_password' => 'your-app-password',
       'out_encryption' => 2, // TLS
       'in_server' => 'imap.gmail.com',
       'in_port' => 993,
       'in_username' => 'your-test-email@gmail.com',
       'in_password' => 'your-app-password',
       'in_protocol' => 1, // IMAP
       'in_encryption' => 1, // SSL
       'in_validate_cert' => true,
   ]);
   
   echo "Mailbox created with ID: " . $mailbox->id;
   
   exit
   ```

3. **Create Inbox Folder (if needed)**
   ```bash
   php artisan tinker
   ```
   
   ```php
   $mailbox = \App\Models\Mailbox::first();
   
   $folder = \App\Models\Folder::firstOrCreate([
       'mailbox_id' => $mailbox->id,
       'type' => 1, // Inbox
   ], [
       'name' => 'Inbox',
   ]);
   
   echo "Folder created with ID: " . $folder->id;
   
   exit
   ```

### Phase 2: SMTP Testing (Outbound Email)

1. **Access Mailbox Settings**
   - Log in as admin user
   - Navigate to: `/mailbox/{id}/settings`
   - Example: http://localhost:8000/mailbox/1/settings

2. **Test SMTP Connection**
   - Click "Test Connection" button in SMTP section
   - Enter your test email address
   - Click "Send Test Email"
   - Wait for response (should be green success message)

3. **Verify Email Received**
   - Check inbox of test email address
   - Check spam folder if not in inbox
   - Email subject should be: "FreeScout SMTP Test - [timestamp]"

4. **Check Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   
   Look for:
   ```
   [INFO] Starting SMTP test
   [DEBUG] SMTP configuration applied
   [INFO] SMTP test successful
   ```

### Phase 3: IMAP Testing (Inbound Email)

1. **Prepare Test Email**
   - From another email account, send an email to your mailbox email
   - Subject: "Test Email 1"
   - Body: "This is a test email for FreeScout"
   - **Do not mark as read**

2. **Test IMAP Connection**
   - In mailbox settings, click "Test Connection" in IMAP section
   - Should show green message with message count
   - Example: "Connected successfully. Found 1 messages in INBOX."

3. **Check Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   
   Look for:
   ```
   [INFO] Starting IMAP test
   [INFO] IMAP connection established
   ```

### Phase 4: Manual Email Fetch

1. **Fetch Emails via UI**
   - In mailbox settings, click "Fetch Emails Now" button
   - Button should show spinner and "Fetching..." text
   - Wait for response
   - Should show: "Successfully fetched X emails. Created Y new conversations."

2. **Verify Conversation Created**
   - Navigate to mailbox view: `/mailbox/{id}`
   - Should see new conversation with subject "Test Email 1"
   - Click conversation to view details

3. **Check Logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```
   
   Look for:
   ```
   [INFO] Starting IMAP fetch
   [INFO] Found unread messages (count: 1)
   [DEBUG] Processing message
   [DEBUG] Customer identified
   [INFO] Created new conversation
   [INFO] Created thread
   [INFO] Message processed successfully
   [INFO] IMAP fetch completed
   ```

### Phase 5: Command Line Fetch

1. **Test Connection First**
   ```bash
   php artisan freescout:fetch-emails --test
   ```
   
   Expected output:
   ```
   Testing IMAP connections...
   
   Mailbox: Test Support
   Status: ✓ Connected successfully
   Messages in INBOX: 0 (already fetched)
   ```

2. **Fetch Emails via Command**
   ```bash
   php artisan freescout:fetch-emails
   ```
   
   Expected output:
   ```
   Fetching emails from mailboxes...
   
   Mailbox: Test Support
   Fetched: 0, Created: 0, Errors: 0
   
   Summary:
   Total Fetched: 0
   Total Created: 0
   Total Errors: 0
   ```

3. **Test with Specific Mailbox**
   ```bash
   php artisan freescout:fetch-emails 1
   ```

### Phase 6: Email Threading Test

1. **Reply to Existing Conversation**
   - Open the conversation in FreeScout
   - Click "Reply" and send a response
   - Check that email is sent to customer

2. **Customer Replies Back**
   - From the test email account, reply to the FreeScout email
   - Subject should maintain "Re: Test Email 1"

3. **Fetch Reply**
   - Click "Fetch Emails Now" in settings
   - OR wait for scheduled fetch (5 minutes)

4. **Verify Threading**
   - Navigate to the original conversation
   - Should see customer's reply as new thread
   - Should NOT create a new conversation
   - Check logs for: "Found existing conversation for reply"

### Phase 7: Attachment Test

1. **Send Email with Attachment**
   - From test email account, send email with attachment
   - Use small file (image, PDF, text file)
   - Subject: "Test Email with Attachment"

2. **Fetch Email**
   - Click "Fetch Emails Now"
   - Check logs for: "Processing attachments"

3. **Verify Attachment**
   - Open conversation in FreeScout
   - Should see attachment listed
   - Click attachment to download
   - Verify file is correct

4. **Check Storage**
   ```bash
   ls -la storage/app/attachments/
   ```
   
   Should see directory with conversation ID containing the attachment file.

### Phase 8: Automatic Scheduling Test

1. **Verify Schedule**
   ```bash
   php artisan schedule:list
   ```
   
   Should show:
   ```
   */5 * * * *  php artisan freescout:fetch-emails ... Next Due: X minutes
   ```

2. **Test Schedule Manually**
   ```bash
   php artisan schedule:run
   ```
   
   Should execute the fetch command if it's time.

3. **Set Up Cron (Production)**
   ```bash
   # Edit crontab
   crontab -e
   
   # Add this line:
   * * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
   ```

4. **Verify Cron is Running**
   ```bash
   # Check cron logs (varies by system)
   grep CRON /var/log/syslog | tail
   ```

---

## Troubleshooting

### SMTP Test Fails

**Error: "Connection refused"**
- Check firewall allows outbound on port 587/465
- Verify SMTP server address is correct
- Try different port (587 vs 465)

**Error: "Authentication failed"**
- Verify username is correct (usually full email)
- Check password (use app password for Gmail/O365)
- Ensure 2FA is set up and app password generated

**Error: "SSL certificate verification failed"**
- Try setting `in_validate_cert` to `false` (testing only)
- Check system has updated CA certificates
- For self-signed certs, add to system trust store

### IMAP Test Fails

**Error: "Connection timeout"**
- Check firewall allows outbound on port 993/143
- Verify IMAP server address
- Check IMAP is enabled on email account

**Error: "Login failed"**
- Same as SMTP authentication troubleshooting
- Ensure IMAP access is enabled in email account settings
- For Gmail: Check "Less secure app access" is not blocking

**Error: "Folder not found"**
- Some servers use different folder names
- Try "INBOX" (uppercase) or "inbox" (lowercase)
- For custom folders, check folder mapping

### Emails Not Creating Conversations

**No errors but conversations not appearing:**
```bash
# Check if messages are being fetched
php artisan freescout:fetch-emails -vvv

# Check database
php artisan tinker
```

```php
\App\Models\Conversation::count();
\App\Models\Thread::count();
\App\Models\Customer::where('emails->0->email', 'test@example.com')->first();
```

**Check folder exists:**
```php
$mailbox = \App\Models\Mailbox::first();
$folder = $mailbox->folders()->where('type', 1)->first();
echo $folder ? "Folder exists" : "Folder missing!";
```

### Duplicate Messages

**Same email appears multiple times:**
- Check if IMAP is marking messages as "seen"
- Verify Message-ID is being stored correctly
- Check logs for "Message already exists" warnings

```bash
# Check for duplicates in database
php artisan tinker
```

```php
\App\Models\Thread::select('message_id', DB::raw('count(*) as count'))
    ->groupBy('message_id')
    ->having('count', '>', 1)
    ->get();
```

---

## Test Results Template

Copy this template to document your test results:

```
# Email System Test Results
Date: __________
Tester: __________

## Configuration
- Email Provider: __________
- Mailbox Email: __________
- SMTP Server: __________
- IMAP Server: __________

## Test Results

### SMTP Testing
- [ ] Test connection succeeded
- [ ] Test email received
- [ ] Logs show successful send
- [ ] Errors: __________

### IMAP Testing
- [ ] Test connection succeeded
- [ ] Message count displayed
- [ ] Logs show successful connection
- [ ] Errors: __________

### Manual Fetch
- [ ] Fetch button works
- [ ] Loading state displays
- [ ] Success message shows statistics
- [ ] Conversation created
- [ ] Logs show processing steps
- [ ] Errors: __________

### Command Line Fetch
- [ ] Test mode works
- [ ] Fetch command works
- [ ] Statistics displayed
- [ ] Errors: __________

### Email Threading
- [ ] Reply sent from FreeScout
- [ ] Customer reply fetched
- [ ] Reply added to existing conversation
- [ ] No duplicate conversation created
- [ ] Errors: __________

### Attachment Handling
- [ ] Email with attachment fetched
- [ ] Attachment stored on disk
- [ ] Attachment displayed in UI
- [ ] Attachment downloadable
- [ ] Errors: __________

### Automatic Scheduling
- [ ] Schedule configured
- [ ] Cron job set up
- [ ] Automatic fetch working
- [ ] Errors: __________

## Notes
__________
__________
__________

## Overall Status
[ ] All tests passed
[ ] Some tests failed (see errors above)
[ ] Not production ready
[ ] Production ready
```

---

## Production Readiness Checklist

Before deploying to production:

### Configuration
- [ ] Real email accounts configured (not test accounts)
- [ ] App passwords generated (for Gmail/O365)
- [ ] All mailboxes tested individually
- [ ] Encryption settings verified (SSL/TLS)
- [ ] Certificate validation enabled

### Server Setup
- [ ] Cron job configured and running
- [ ] Queue worker running as system service
- [ ] Log rotation configured
- [ ] Storage permissions set correctly (775)
- [ ] Firewall allows IMAP/SMTP ports

### Monitoring
- [ ] Log aggregation set up
- [ ] Alerts configured for failed fetches
- [ ] Disk space monitoring (for attachments)
- [ ] Queue failure monitoring
- [ ] Email delivery rate tracking

### Security
- [ ] Passwords stored securely
- [ ] SSL/TLS enforced
- [ ] Admin access restricted
- [ ] CSRF protection verified
- [ ] Rate limiting configured

### Performance
- [ ] Redis configured for queues (optional)
- [ ] Database indexes verified
- [ ] Attachment cleanup policy set
- [ ] Connection pooling optimized

### Documentation
- [ ] Team trained on email system
- [ ] Troubleshooting guide accessible
- [ ] Escalation procedures defined
- [ ] Configuration documented

---

## Support

If you encounter issues not covered in this guide:

1. **Check Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Enable Verbose Logging:**
   - Set `LOG_LEVEL=debug` in `.env`
   - Restart application

3. **Review Documentation:**
   - `EMAIL_SYSTEM_IMPLEMENTATION.md`
   - `EMAIL_SYSTEM_CONTINUATION.md`

4. **Common Log Locations:**
   - Laravel: `storage/logs/laravel.log`
   - Queue: `storage/logs/queue.log` (if configured)
   - System: `/var/log/syslog` (Linux)

5. **Debug Commands:**
   ```bash
   # Check queue status
   php artisan queue:work --once
   
   # Clear cache
   php artisan cache:clear
   php artisan config:clear
   
   # Check routes
   php artisan route:list
   
   # Database queries
   php artisan tinker
   ```

---

## Next Steps

After successful testing:

1. **Document Results** using the template above
2. **Review Performance** under load
3. **Set Up Production** following the Production Readiness Checklist
4. **Train Team** on using the email system
5. **Monitor** for first week in production
6. **Iterate** based on feedback

---

**Good luck with testing! 🚀**
