{{-- Thread Attachments Partial - Displays attachments for a thread --}}
@if(isset($attachments) && $attachments->count() > 0)
    <div class="mt-4 pt-4 border-t border-gray-200">
        <div class="text-sm font-medium text-gray-700 mb-2 flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
            </svg>
            {{ trans_choice('{1} :count Attachment|[2,*] :count Attachments', $attachments->count(), ['count' => $attachments->count()]) }}
        </div>
        
        <div class="space-y-2">
            @foreach($attachments as $attachment)
                @php
                    $file_extension = strtolower(pathinfo($attachment->file_name, PATHINFO_EXTENSION));
                    $is_image = in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                    $file_size_kb = round($attachment->size / 1024, 2);
                @endphp
                
                <div class="flex items-center gap-2 p-2 bg-gray-50 rounded hover:bg-gray-100 transition">
                    {{-- File Icon or Image Thumbnail --}}
                    @if($is_image && !empty($attachment->url))
                        <div class="flex-shrink-0 w-12 h-12 overflow-hidden rounded border border-gray-200">
                            <img src="{{ $attachment->url }}" 
                                 alt="{{ $attachment->file_name }}" 
                                 class="w-full h-full object-cover"
                                 loading="lazy">
                        </div>
                    @else
                        <div class="flex-shrink-0 w-12 h-12 bg-blue-100 rounded flex items-center justify-center">
                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                    @endif
                    
                    {{-- File Info --}}
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 truncate">
                            {{ $attachment->file_name }}
                        </div>
                        <div class="text-xs text-gray-500">
                            {{ $file_size_kb }} KB
                            @if($file_extension)
                                <span class="mx-1">•</span>
                                <span class="uppercase">{{ $file_extension }}</span>
                            @endif
                        </div>
                    </div>
                    
                    {{-- Download Button --}}
                    @if(!empty($attachment->url))
                        <a href="{{ $attachment->url }}" 
                           download="{{ $attachment->file_name }}"
                           class="flex-shrink-0 p-2 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded transition"
                           title="{{ __('Download') }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </a>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
