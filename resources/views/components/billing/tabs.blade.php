{{--
    Tabs wrapper component.
    Props:
      $tabs   – array of ['id' => string, 'label' => string, 'icon' => string (optional)]
      $active – id of the initially active tab
--}}
<div
    x-data="{
        activeTab: '{{ $active ?: ($tabs[0]['id'] ?? '') }}'
    }"
>
    {{-- Tab navigation bar --}}
    <div class="border-b border-gray-200 mb-6">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            @foreach ($tabs as $tab)
                <button
                    type="button"
                    @click="activeTab = '{{ $tab['id'] }}'"
                    :class="activeTab === '{{ $tab['id'] }}'
                        ? 'border-b-2 text-sm font-medium py-3 px-1 transition-colors'
                        : 'border-b-2 border-transparent text-sm font-medium py-3 px-1 transition-colors hover:opacity-75'"
                    :style="activeTab === '{{ $tab['id'] }}'
                        ? 'border-color: var(--theme-primary-600); color: var(--theme-primary-600)'
                        : 'color: var(--theme-text-secondary)'"
                    aria-selected="activeTab === '{{ $tab['id'] }}'"
                >
                    @if (!empty($tab['icon']))
                        <i class="fas fa-{{ $tab['icon'] }} mr-1.5"></i>
                    @endif
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Tab panels are injected via slot --}}
    {{ $slot }}
</div>
