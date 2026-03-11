{{--
    Tab panel component — rendered inside <x-billing::tabs>.
    Props:
      $id – must match one of the ids in the parent's $tabs array
--}}
<div x-show="activeTab === '{{ $id }}'" x-cloak>
    {{ $slot }}
</div>
