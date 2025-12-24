{{-- Flash Messages Component --}}
{{-- Usage: <x-flash-messages /> or <x-flash-messages :flashes="$flashes" /> --}}

@props(['flashes' => []])

@if (session('flash_success') || session('flash_success_unescaped'))
    <div class="rounded-md p-4 mb-4 border" role="alert" style="background-color: var(--theme-status-success-bg); border-color: var(--theme-status-success-bg)">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5" style="color: var(--theme-status-success-text)" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium" style="color: var(--theme-status-success-text)">
                    {{ session('flash_success') }}{!! session('flash_success_unescaped') !!}
                </p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2" 
                            style="background-color: var(--theme-status-success-bg); color: var(--theme-status-success-text); --tw-ring-color: var(--theme-status-success-text)"
                            onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@if (session('flash_warning'))
    <div class="rounded-md p-4 mb-4 border" role="alert" style="background-color: var(--theme-status-warning-bg); border-color: var(--theme-status-warning-bg)">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5" style="color: var(--theme-status-warning-text)" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium" style="color: var(--theme-status-warning-text)">
                    {{ session('flash_warning') }}
                </p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2" 
                            style="background-color: var(--theme-status-warning-bg); color: var(--theme-status-warning-text); --tw-ring-color: var(--theme-status-warning-text)"
                            onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@if (session('flash_error'))
    <div class="rounded-md p-4 mb-4 border" role="alert" style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-bg)">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5" style="color: var(--theme-status-error-text)" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium" style="color: var(--theme-status-error-text)">
                    {{ session('flash_error') }}
                </p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2" 
                            style="background-color: var(--theme-status-error-bg); color: var(--theme-status-error-text); --tw-ring-color: var(--theme-status-error-text)"
                            onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

@if (session('flash_error_unescaped'))
    <div class="rounded-md p-4 mb-4 border" role="alert" style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-bg)">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5" style="color: var(--theme-status-error-text)" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3 flex-1">
                <p class="text-sm font-medium" style="color: var(--theme-status-error-text)">
                    {!! session('flash_error_unescaped') !!}
                </p>
            </div>
            <div class="ml-auto pl-3">
                <div class="-mx-1.5 -my-1.5">
                    <button type="button" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2" 
                            style="background-color: var(--theme-status-error-bg); color: var(--theme-status-error-text); --tw-ring-color: var(--theme-status-error-text)"
                            onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                        <span class="sr-only">Dismiss</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif

{{-- Custom flash messages array support --}}
@if (!empty($flashes) && is_array($flashes))
    @foreach ($flashes as $flash)
        @php
            $type = $flash['type'] ?? 'info';
            $colors = [
                'success' => [
                    'style' => 'background-color: var(--theme-status-success-bg); color: var(--theme-status-success-text); border-color: var(--theme-status-success-bg)',
                    'icon' => 'color: var(--theme-status-success-text)',
                    'button' => 'background-color: var(--theme-status-success-bg); color: var(--theme-status-success-text); --tw-ring-color: var(--theme-status-success-text)'
                ],
                'warning' => [
                    'style' => 'background-color: var(--theme-status-warning-bg); color: var(--theme-status-warning-text); border-color: var(--theme-status-warning-bg)',
                    'icon' => 'color: var(--theme-status-warning-text)',
                    'button' => 'background-color: var(--theme-status-warning-bg); color: var(--theme-status-warning-text); --tw-ring-color: var(--theme-status-warning-text)'
                ],
                'danger' => [
                    'style' => 'background-color: var(--theme-status-error-bg); color: var(--theme-status-error-text); border-color: var(--theme-status-error-bg)',
                    'icon' => 'color: var(--theme-status-error-text)',
                    'button' => 'background-color: var(--theme-status-error-bg); color: var(--theme-status-error-text); --tw-ring-color: var(--theme-status-error-text)'
                ],
                'error' => [
                    'style' => 'background-color: var(--theme-status-error-bg); color: var(--theme-status-error-text); border-color: var(--theme-status-error-bg)',
                    'icon' => 'color: var(--theme-status-error-text)',
                    'button' => 'background-color: var(--theme-status-error-bg); color: var(--theme-status-error-text); --tw-ring-color: var(--theme-status-error-text)'
                ],
                'info' => [
                    'style' => 'background-color: var(--theme-status-info-bg); color: var(--theme-status-info-text); border-color: var(--theme-status-info-bg)',
                    'icon' => 'color: var(--theme-status-info-text)',
                    'button' => 'background-color: var(--theme-status-info-bg); color: var(--theme-status-info-text); --tw-ring-color: var(--theme-status-info-text)'
                ],
            ];
            $colorScheme = $colors[$type] ?? $colors['info'];
        @endphp
        <div class="rounded-md p-4 mb-4 border" role="alert" style="{{ $colorScheme['style'] }}">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5" style="{{ $colorScheme['icon'] }}" viewBox="0 0 20 20" fill="currentColor">
                        @if ($type === 'success')
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        @elseif (in_array($type, ['danger', 'error']))
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" />
                        @elseif ($type === 'warning')
                            <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                        @else
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                        @endif
                    </svg>
                </div>
                <div class="ml-3 flex-1">
                    <p class="text-sm font-medium" style="color: inherit">
                        @if (!empty($flash['unescaped']))
                            {!! $flash['text'] !!}
                        @else
                            {{ $flash['text'] }}
                        @endif
                    </p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" class="inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2" 
                                style="{{ $colorScheme['button'] }}"
                                onclick="this.parentElement.parentElement.parentElement.parentElement.remove()">
                            <span class="sr-only">Dismiss</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endif
