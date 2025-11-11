<?php

require __DIR__.'/vendor/autoload.php';

use Webklex\PHPIMAP\ClientManager;

// Test Gmail IMAP connection
$config = [
    'host' => 'imap.gmail.com',
    'port' => 993,
    'encryption' => 'ssl',
    'validate_cert' => true,
    'username' => 'scott.mcdonald@borealtek.ca',
    'password' => 'yuzm ltcg ucxi elye',
    'protocol' => 'imap',
    'timeout' => 30,
];

echo "Testing IMAP connection to Gmail...\n";
echo "Host: {$config['host']}:{$config['port']}\n";
echo "User: {$config['username']}\n";
echo "Encryption: {$config['encryption']}\n\n";

try {
    $cm = new ClientManager;
    $client = $cm->make($config);

    echo "Connecting...\n";
    $client->connect();

    echo "✓ Connected successfully!\n\n";

    echo "Getting INBOX folder...\n";
    $folder = $client->getFolder('INBOX');

    echo "✓ Got INBOX folder!\n\n";

    echo "Examining folder...\n";
    $folder->examine();

    echo "✓ Folder examined!\n\n";

    echo "Getting messages (without count)...\n";
    $messages = $folder->messages()->all();

    $messageCount = $messages->count();
    echo "✓ Found {$messageCount} messages in INBOX\n\n";

    $client->disconnect();
    echo "✓ Disconnected successfully\n";
} catch (\Exception $e) {
    echo '✗ Error: '.$e->getMessage()."\n";
    echo "\nFull error:\n";
    echo $e->getTraceAsString()."\n";
}
