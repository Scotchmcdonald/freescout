<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Conversation #{{ $conversation->number }}</title>
    <style>
        body { font-family: sans-serif; line-height: 1.5; }
        .thread { border-bottom: 1px solid #ccc; padding: 10px 0; }
        .meta { color: #666; font-size: 0.9em; }
    </style>
</head>
<body>
    <h1>{{ $conversation->subject }}</h1>
    <div class="meta">
        Conversation #{{ $conversation->number }} | {{ $conversation->customer->getFullName() }}
    </div>
    
    @foreach($conversation->threads as $thread)
        <div class="thread">
            <div class="meta">
                <strong>{{ $thread->user ? $thread->user->getFullName() : ($thread->customer ? $thread->customer->getFullName() : $thread->from) }}</strong>
                at {{ $thread->created_at }}
            </div>
            <div class="body">
                {!! $thread->body !!}
            </div>
        </div>
    @endforeach
    
    <script>
        window.print();
    </script>
</body>
</html>
