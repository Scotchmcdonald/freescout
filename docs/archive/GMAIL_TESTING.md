# Gmail Testing - Quick Start Guide

## Prerequisites Completed ✓
- [x] Database seeded with test data
- [x] Admin user created: `admin@freescout.local` / `password`
- [x] Test mailbox created (ID: 1)
- [x] Folders created (Inbox, Drafts, etc.)

---

## Step 1: Generate Gmail App Password

**Important:** You cannot use your regular Gmail password. You need an App Password.

### Instructions:

1. **Enable 2-Factor Authentication** (if not already enabled)
   - Go to: https://myaccount.google.com/security
   - Click "2-Step Verification"
   - Follow the setup process

2. **Generate App Password**
   - Go to: https://myaccount.google.com/apppasswords
   - You may need to sign in again
   - Select app: **Mail**
   - Select device: **Other (Custom name)**
   - Enter name: **FreeScout**
   - Click **Generate**
   - Copy the **16-character password** (shown without spaces)
   - Save it securely (you won't be able to see it again)

---

## Step 2: Configure Mailbox

Run the interactive configuration command:

```bash
cd /var/www/html
php artisan mailbox:configure-gmail
```

You'll be prompted for:
1. Your Gmail address (e.g., `yourname@gmail.com`)
2. Your App Password (the 16-character password from Step 1)

The command will automatically configure:
- SMTP: `smtp.gmail.com:587` (TLS)
- IMAP: `imap.gmail.com:993` (SSL)

---

## Step 3: Test IMAP Connection

Test if FreeScout can connect to Gmail and fetch emails:

```bash
php artisan freescout:fetch-emails 1 --test
```

Expected output:
```
Testing IMAP connections...

Mailbox: Support
Status: ✓ Connected successfully
Messages in INBOX: X
```

If this fails, check:
- App Password is correct
- IMAP is enabled in Gmail settings
- Your firewall allows port 993

---

## Step 4: Prepare Test Email

Before fetching, send a test email to your Gmail account:

1. From another email account (or your phone), send an email to your Gmail
2. Subject: "Test Email 1"
3. Body: "This is a test for FreeScout email system"
4. **Important:** Leave it unread in Gmail

---

## Step 5: Fetch Emails via Command Line

```bash
php artisan freescout:fetch-emails 1
```

Expected output:
```
Fetching emails from mailboxes...

Mailbox: Support
Fetched: 1, Created: 1, Errors: 0

Summary:
Total Fetched: 1
Total Created: 1
Total Errors: 0
```

Check logs:
```bash
tail -f storage/logs/laravel.log
```

---

## Step 6: Test via Web Interface

### Start the application:
```bash
php artisan serve
```

### Access the application:
1. Open browser: http://localhost:8000
2. Login with:
   - Email: `admin@freescout.local`
   - Password: `password`

### Navigate to mailbox settings:
http://localhost:8000/mailbox/1/settings

### Test SMTP (outbound email):
1. Click "Test Connection" in SMTP section
2. Enter your email address
3. Click "Send Test Email"
4. Check your inbox (and spam folder)

### Test IMAP (inbound email):
1. Click "Test Connection" in IMAP section
2. Should show message count

### Manual fetch:
1. Click "Fetch Emails Now" button
2. Should show success message with statistics

### View conversation:
1. Navigate to: http://localhost:8000/mailbox/1
2. You should see the test email as a conversation
3. Click it to view details

---

## Step 7: Test Email Threading

1. **Reply from FreeScout:**
   - Open the conversation
   - Type a reply
   - Click "Send"
   - Check that email arrives in Gmail

2. **Reply from Gmail:**
   - Reply to the FreeScout email from Gmail
   - Keep the same subject line

3. **Fetch the reply:**
   ```bash
   php artisan freescout:fetch-emails 1
   ```
   OR click "Fetch Emails Now" in web interface

4. **Verify threading:**
   - Go back to the conversation in FreeScout
   - The reply should appear as a new thread
   - Should NOT create a new conversation

---

## Step 8: Test with Attachment

1. **Send email with attachment:**
   - From Gmail, send email with a small attachment (image, PDF, etc.)
   - Subject: "Test Attachment"

2. **Fetch the email:**
   ```bash
   php artisan freescout:fetch-emails 1
   ```

3. **Verify attachment:**
   - Open conversation in web interface
   - Attachment should be displayed
   - Click to download and verify

4. **Check storage:**
   ```bash
   ls -la storage/app/attachments/
   ```

---

## Monitoring Logs

Watch logs in real-time:
```bash
tail -f storage/logs/laravel.log
```

Look for these log entries:
- `[INFO] Starting IMAP fetch`
- `[INFO] Found unread messages`
- `[DEBUG] Processing message`
- `[INFO] Created new conversation`
- `[INFO] Message processed successfully`

---

## Common Issues

### "Authentication failed"
- Verify you're using App Password, not regular password
- Check IMAP is enabled: https://mail.google.com/mail/u/0/#settings/fwdandpop
- Try regenerating App Password

### "Connection timeout"
- Check firewall allows port 993 (IMAP) and 587 (SMTP)
- Verify you're not on restrictive network

### "No messages fetched"
- Ensure emails are unread in Gmail
- Check if email is in INBOX (not other folders)
- Run with verbose logging: `php artisan freescout:fetch-emails 1 -vvv`

### Email appears multiple times
- This shouldn't happen due to Message-ID duplicate detection
- Check logs for "Message already exists"

---

## Quick Reference

### Useful Commands:
```bash
# Configure mailbox
php artisan mailbox:configure-gmail 1

# Test connection
php artisan freescout:fetch-emails 1 --test

# Fetch emails
php artisan freescout:fetch-emails 1

# Fetch all mailboxes
php artisan freescout:fetch-emails

# View logs
tail -f storage/logs/laravel.log

# Start web server
php artisan serve

# Start queue worker (for sending emails)
php artisan queue:work
```

### Database Queries:
```bash
php artisan tinker
```

```php
// Check data
\App\Models\Mailbox::find(1);
\App\Models\Conversation::count();
\App\Models\Thread::latest()->first();
\App\Models\Customer::all();

// Check specific mailbox
$mailbox = \App\Models\Mailbox::find(1);
echo $mailbox->email;
echo $mailbox->conversations()->count();
```

---

## Success Criteria

You've successfully tested the email system when:
- [x] IMAP connection test succeeds
- [x] SMTP test email is received
- [x] Email is fetched and creates conversation
- [x] Reply from FreeScout is sent
- [x] Reply from Gmail is threaded correctly
- [x] Attachment is saved and accessible
- [x] Logs show detailed processing steps

---

## Next Steps After Testing

1. **Set up automatic fetching:**
   ```bash
   # Add to crontab
   * * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
   ```

2. **Start queue worker as service** (for production)

3. **Configure production mailboxes** with real email addresses

4. **Set up monitoring and alerts**

5. **Review and adjust fetch frequency** (currently every 5 minutes)

---

**Ready to start? Run the configuration command:**

```bash
cd /var/www/html
php artisan mailbox:configure-gmail
```

Good luck! 🚀
