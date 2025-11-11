{{-- Attachment Preview: Enhanced attachment display with preview support for common file types --}}
@if (!empty($attachment))
    <div class="attachment-preview" data-attachment-id="{{ $attachment->id }}" data-mime="{{ $attachment->mime_type }}">
        <div class="attachment-preview-container">
            {{-- Preview for images --}}
            @if (strpos($attachment->mime_type, 'image/') === 0)
                <div class="attachment-preview-image">
                    <a href="{{ $attachment->url() }}" target="_blank" data-lightbox="thread-attachments">
                        <img src="{{ $attachment->url() }}" 
                             alt="{{ $attachment->file_name }}" 
                             class="img-responsive attachment-thumbnail"
                             loading="lazy" />
                    </a>
                </div>
            @endif
            
            {{-- Icon for file type --}}
            <div class="attachment-preview-icon">
                @if (strpos($attachment->mime_type, 'image/') === 0)
                    <i class="glyphicon glyphicon-picture" aria-hidden="true"></i>
                @elseif (strpos($attachment->mime_type, 'application/pdf') === 0)
                    <i class="glyphicon glyphicon-file" aria-hidden="true"></i>
                @elseif (strpos($attachment->mime_type, 'application/zip') === 0 || strpos($attachment->mime_type, 'application/x-zip') === 0)
                    <i class="glyphicon glyphicon-compressed" aria-hidden="true"></i>
                @elseif (strpos($attachment->mime_type, 'text/') === 0)
                    <i class="glyphicon glyphicon-list-alt" aria-hidden="true"></i>
                @elseif (strpos($attachment->mime_type, 'video/') === 0)
                    <i class="glyphicon glyphicon-facetime-video" aria-hidden="true"></i>
                @elseif (strpos($attachment->mime_type, 'audio/') === 0)
                    <i class="glyphicon glyphicon-volume-up" aria-hidden="true"></i>
                @elseif (strpos($attachment->mime_type, 'application/msword') !== false || strpos($attachment->mime_type, 'application/vnd.openxmlformats-officedocument.wordprocessingml') !== false)
                    <i class="glyphicon glyphicon-file" aria-hidden="true"></i>
                @elseif (strpos($attachment->mime_type, 'application/vnd.ms-excel') !== false || strpos($attachment->mime_type, 'application/vnd.openxmlformats-officedocument.spreadsheetml') !== false)
                    <i class="glyphicon glyphicon-list" aria-hidden="true"></i>
                @elseif (strpos($attachment->mime_type, 'application/vnd.ms-powerpoint') !== false || strpos($attachment->mime_type, 'application/vnd.openxmlformats-officedocument.presentationml') !== false)
                    <i class="glyphicon glyphicon-blackboard" aria-hidden="true"></i>
                @else
                    <i class="glyphicon glyphicon-paperclip" aria-hidden="true"></i>
                @endif
            </div>
            
            {{-- File information --}}
            <div class="attachment-preview-info">
                <div class="attachment-preview-name">
                    <a href="{{ $attachment->url() }}" 
                       class="attachment-link break-words" 
                       target="_blank"
                       title="{{ $attachment->file_name }}">
                        {{ $attachment->file_name }}
                    </a>
                </div>
                <div class="attachment-preview-meta">
                    <span class="attachment-size text-help">{{ $attachment->getSizeName() }}</span>
                    @if (!empty($attachment->mime_type))
                        <span class="attachment-type text-help">
                            {{ strtoupper(pathinfo($attachment->file_name, PATHINFO_EXTENSION)) }}
                        </span>
                    @endif
                </div>
            </div>
            
            {{-- Actions --}}
            <div class="attachment-preview-actions">
                <a href="{{ $attachment->url() }}" 
                   download 
                   class="btn btn-xs btn-link"
                   title="{{ __('Download') }}"
                   aria-label="{{ __('Download :filename', ['filename' => $attachment->file_name]) }}">
                    <i class="glyphicon glyphicon-download-alt"></i>
                </a>
                <a href="{{ $attachment->url() }}" 
                   target="_blank" 
                   class="btn btn-xs btn-link"
                   title="{{ __('Open in new window') }}"
                   aria-label="{{ __('Open :filename in new window', ['filename' => $attachment->file_name]) }}">
                    <i class="glyphicon glyphicon-new-window"></i>
                </a>
                @action('thread.attachment_actions', $attachment, $thread ?? null, $conversation ?? null, $mailbox ?? null)
            </div>
        </div>
        
        @action('thread.attachment_preview.after', $attachment, $thread ?? null, $conversation ?? null, $mailbox ?? null)
    </div>
@endif
