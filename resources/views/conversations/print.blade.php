{{-- Conversation Print Layout: Optimized print view for conversations --}}
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>{{ __('Conversation') }} #{{ $conversation->number }} - {{ $conversation->getSubject() }}</title>
    
    <style type="text/css">
        @media print {
            body {
                font-family: Arial, sans-serif;
                font-size: 12pt;
                line-height: 1.5;
                color: #000;
                background: #fff;
            }
            
            .print-header {
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            
            .print-header h1 {
                font-size: 18pt;
                margin: 0 0 10px 0;
            }
            
            .print-meta {
                font-size: 10pt;
                color: #666;
            }
            
            .print-meta-row {
                margin: 5px 0;
            }
            
            .print-meta-label {
                font-weight: bold;
                display: inline-block;
                width: 120px;
            }
            
            .thread-item {
                border: 1px solid #ddd;
                padding: 15px;
                margin: 15px 0;
                page-break-inside: avoid;
            }
            
            .thread-header {
                border-bottom: 1px solid #eee;
                padding-bottom: 10px;
                margin-bottom: 10px;
            }
            
            .thread-from {
                font-weight: bold;
                font-size: 11pt;
            }
            
            .thread-date {
                color: #666;
                font-size: 10pt;
            }
            
            .thread-body {
                margin: 10px 0;
            }
            
            .thread-attachments {
                margin-top: 10px;
                padding-top: 10px;
                border-top: 1px solid #eee;
            }
            
            .thread-attachments ul {
                list-style: none;
                padding: 0;
                margin: 5px 0;
            }
            
            .thread-attachments li {
                margin: 3px 0;
            }
            
            .no-print {
                display: none;
            }
            
            a {
                color: #000;
                text-decoration: none;
            }
            
            .page-break {
                page-break-after: always;
            }
        }
        
        @media screen {
            body {
                font-family: Arial, sans-serif;
                max-width: 800px;
                margin: 20px auto;
                padding: 20px;
            }
            
            .print-header {
                border-bottom: 2px solid #333;
                padding-bottom: 10px;
                margin-bottom: 20px;
            }
            
            .thread-item {
                border: 1px solid #ddd;
                padding: 15px;
                margin: 15px 0;
                background: #f9f9f9;
            }
            
            .no-print {
                text-align: right;
                margin-bottom: 20px;
            }
            
            .btn-print {
                padding: 10px 20px;
                background: #0066cc;
                color: white;
                border: none;
                cursor: pointer;
                border-radius: 3px;
            }
            
            .btn-print:hover {
                background: #0052a3;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="btn-print" onclick="window.print()">{{ __('Print') }}</button>
        <button class="btn-print" onclick="window.close()">{{ __('Close') }}</button>
    </div>
    
    <div class="print-container">
        <div class="print-header">
            <h1>{{ __('Conversation') }} #{{ $conversation->number }}</h1>
            <div class="print-meta">
                <div class="print-meta-row">
                    <span class="print-meta-label">{{ __('Subject') }}:</span>
                    <span>{{ $conversation->getSubject() }}</span>
                </div>
                <div class="print-meta-row">
                    <span class="print-meta-label">{{ __('Status') }}:</span>
                    <span>{{ $conversation->getStatusName() }}</span>
                </div>
                @if ($conversation->user_id)
                    <div class="print-meta-row">
                        <span class="print-meta-label">{{ __('Assigned To') }}:</span>
                        <span>{{ $conversation->getAssigneeName(true) }}</span>
                    </div>
                @endif
                <div class="print-meta-row">
                    <span class="print-meta-label">{{ __('Mailbox') }}:</span>
                    <span>{{ $mailbox->name ?? '' }}</span>
                </div>
                <div class="print-meta-row">
                    <span class="print-meta-label">{{ __('Customer') }}:</span>
                    <span>{{ $customer ? $customer->getFullName(true) : '' }} &lt;{{ $conversation->customer_email }}&gt;</span>
                </div>
                <div class="print-meta-row">
                    <span class="print-meta-label">{{ __('Created') }}:</span>
                    <span>{{ App\User::dateFormat($conversation->created_at) }}</span>
                </div>
                @if ($conversation->closed_at)
                    <div class="print-meta-row">
                        <span class="print-meta-label">{{ __('Closed') }}:</span>
                        <span>{{ App\User::dateFormat($conversation->closed_at) }}</span>
                    </div>
                @endif
            </div>
        </div>
        
        <div class="print-threads">
            @if (!empty($threads))
                @foreach ($threads as $thread)
                    @if (in_array($thread->type, [App\Thread::TYPE_MESSAGE, App\Thread::TYPE_CUSTOMER, App\Thread::TYPE_NOTE]))
                        <div class="thread-item">
                            <div class="thread-header">
                                <div class="thread-from">
                                    @if ($thread->type == App\Thread::TYPE_CUSTOMER)
                                        {{ __('From') }}: 
                                        @if ($thread->customer_cached)
                                            {{ $thread->customer_cached->getFullName(true) }}
                                        @else
                                            {{ $thread->getFromName() }}
                                        @endif
                                        &lt;{{ $thread->getFromEmail() }}&gt;
                                    @elseif ($thread->type == App\Thread::TYPE_NOTE)
                                        {{ __('Note by') }}: 
                                        @if ($thread->created_by_user)
                                            {{ $thread->created_by_user->getFullName() }}
                                        @else
                                            {{ __('Unknown') }}
                                        @endif
                                    @else
                                        {{ __('From') }}: 
                                        @if ($thread->created_by_user)
                                            {{ $thread->created_by_user->getFullName() }}
                                        @endif
                                    @endif
                                </div>
                                <div class="thread-date">
                                    {{ App\User::dateFormat($thread->created_at) }}
                                </div>
                                
                                @if ($thread->type == App\Thread::TYPE_MESSAGE && !$thread->isDraft())
                                    @if ($thread->getToArray())
                                        <div class="thread-to">
                                            {{ __('To') }}: {{ implode(', ', $thread->getToArray()) }}
                                        </div>
                                    @endif
                                    @if ($thread->getCcArray())
                                        <div class="thread-cc">
                                            {{ __('CC') }}: {{ implode(', ', $thread->getCcArray()) }}
                                        </div>
                                    @endif
                                @endif
                            </div>
                            
                            <div class="thread-body">
                                {!! $thread->getCleanBody() !!}
                            </div>
                            
                            @if ($thread->has_attachments && !empty($thread->attachments))
                                <div class="thread-attachments">
                                    <strong>{{ __('Attachments') }}:</strong>
                                    <ul>
                                        @foreach ($thread->attachments as $attachment)
                                            <li>
                                                {{ $attachment->file_name }} ({{ $attachment->getSizeName() }})
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
        
        <div class="print-footer" style="margin-top: 30px; padding-top: 10px; border-top: 1px solid #ddd; font-size: 10pt; color: #666;">
            <p>{{ __('Printed on') }}: {{ date('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
