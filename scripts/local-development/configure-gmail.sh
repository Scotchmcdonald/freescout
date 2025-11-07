#!/bin/bash

# Gmail Configuration Helper Script
# This script helps configure a mailbox with Gmail SMTP/IMAP settings

echo "=================================="
echo "Gmail Mailbox Configuration"
echo "=================================="
echo ""

# Get mailbox ID
read -p "Enter Mailbox ID (default: 1): " MAILBOX_ID
MAILBOX_ID=${MAILBOX_ID:-1}

# Get Gmail address
read -p "Enter your Gmail address: " GMAIL_ADDRESS

# Get App Password
echo ""
echo "IMPORTANT: You need a Gmail App Password (not your regular password)"
echo "To generate one:"
echo "1. Go to https://myaccount.google.com/apppasswords"
echo "2. Enable 2-Factor Authentication if not already enabled"
echo "3. Generate an app password for 'Mail'"
echo "4. Copy the 16-character password (without spaces)"
echo ""
read -sp "Enter your Gmail App Password: " APP_PASSWORD
echo ""
echo ""

# Update mailbox
cd /var/www/html

php artisan tinker --execute="
\$mailbox = \App\Models\Mailbox::find($MAILBOX_ID);
if (\$mailbox) {
    \$mailbox->update([
        'email' => '$GMAIL_ADDRESS',
        'out_server' => 'smtp.gmail.com',
        'out_port' => 587,
        'out_username' => '$GMAIL_ADDRESS',
        'out_password' => '$APP_PASSWORD',
        'out_encryption' => 2,
        'in_server' => 'imap.gmail.com',
        'in_port' => 993,
        'in_username' => '$GMAIL_ADDRESS',
        'in_password' => '$APP_PASSWORD',
        'in_protocol' => 1,
        'in_encryption' => 1,
        'in_validate_cert' => true,
    ]);
    echo 'Mailbox configured successfully!' . PHP_EOL;
    echo 'Mailbox ID: ' . \$mailbox->id . PHP_EOL;
    echo 'Email: ' . \$mailbox->email . PHP_EOL;
    echo 'SMTP: smtp.gmail.com:587 (TLS)' . PHP_EOL;
    echo 'IMAP: imap.gmail.com:993 (SSL)' . PHP_EOL;
} else {
    echo 'Error: Mailbox not found!' . PHP_EOL;
}
"

echo ""
echo "=================================="
echo "Configuration Complete!"
echo "=================================="
echo ""
echo "Next steps:"
echo "1. Start the application: php artisan serve"
echo "2. Login at: http://localhost:8000/login"
echo "   Email: admin@freescout.local"
echo "   Password: password"
echo "3. Navigate to: http://localhost:8000/mailbox/1/settings"
echo "4. Test SMTP and IMAP connections"
echo ""
