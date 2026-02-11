<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9fafb;
            padding: 20px;
            border: 1px solid #e5e7eb;
        }
        .reply-info {
            background-color: white;
            padding: 15px;
            border-left: 4px solid #10b981;
            margin: 15px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>New Reply on Your Ticket</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>You have received a new reply on your support ticket.</p>
            
            <div class="reply-info">
                <p><strong>Ticket Number:</strong> {{ $ticketNumber }}</p>
                <p><strong>Subject:</strong> {{ $subject }}</p>
                <p><strong>Reply:</strong></p>
                <p>{{ $replyMessage }}</p>
            </div>
            
            <p>Please log in to the client portal to view the full conversation and respond.</p>
        </div>
        <div class="footer">
            <p>This is an automated message. Please do not reply to this email.</p>
        </div>
    </div>
</body>
</html>
