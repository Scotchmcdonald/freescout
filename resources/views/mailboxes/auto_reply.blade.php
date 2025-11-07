@extends('layouts.app')

@section('title', 'Auto Reply - '.$mailbox->name)

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-3">
            @include('mailboxes._partials.settings_nav')
        </div>
        <div class="col-md-9">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Auto Reply Settings</h4>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('mailboxes.auto_reply.save', $mailbox) }}">
                        @csrf

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="auto_reply_enabled" 
                                   name="auto_reply_enabled" value="1"
                                   @checked(old('auto_reply_enabled', $mailbox->auto_reply_enabled))>
                            <label class="form-check-label" for="auto_reply_enabled">
                                Enable Auto Reply
                            </label>
                            <div class="form-text">When enabled, automatically reply to incoming emails</div>
                        </div>

                        <div class="mb-3">
                            <label for="auto_reply_subject" class="form-label">Subject</label>
                            <input type="text" class="form-control @error('auto_reply_subject') is-invalid @enderror" 
                                   id="auto_reply_subject" name="auto_reply_subject"
                                   value="{{ old('auto_reply_subject', $mailbox->auto_reply_subject ?? 'Re: {%subject%}') }}">
                            <div class="form-text">Available variables: {%subject%}, {%mailbox_name%}</div>
                            @error('auto_reply_subject')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="auto_reply_message" class="form-label">Message</label>
                            <textarea class="form-control @error('auto_reply_message') is-invalid @enderror" 
                                      id="auto_reply_message" name="auto_reply_message" rows="8">{{ old('auto_reply_message', $mailbox->auto_reply_message) }}</textarea>
                            <div class="form-text">Available variables: {%customer_name%}, {%mailbox_name%}</div>
                            @error('auto_reply_message')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="auto_bcc" class="form-label">BCC</label>
                            <input type="email" class="form-control @error('auto_bcc') is-invalid @enderror" 
                                   id="auto_bcc" name="auto_bcc"
                                   value="{{ old('auto_bcc', $mailbox->auto_bcc) }}"
                                   placeholder="bcc@example.com">
                            <div class="form-text">Optional: Send a copy of auto-replies to this address</div>
                            @error('auto_bcc')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                        <a href="{{ route('mailboxes.settings', $mailbox) }}" class="btn btn-secondary">Cancel</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
