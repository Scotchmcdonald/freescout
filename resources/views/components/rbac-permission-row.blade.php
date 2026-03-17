{{-- RBAC Permission Row Component --}}
@props([
    'permission' => null,
    'roles' => [],
])

@php
    $perm = $permission;
@endphp

<tr class="border-b border-neutral-100 dark:border-neutral-700/50 last:border-b-0 hover:bg-neutral-50/50 dark:hover:bg-neutral-700/30 transition-colors duration-100"
    x-show="open && matchesSearch('{{ $perm->name }}')"
    x-transition>
    <td class="px-4 py-2.5 text-sm pl-10">
        <div class="flex flex-col">
            <span class="text-neutral-700 dark:text-neutral-300 font-medium">{{ $perm->label ?? $perm->name }}</span>
            <span class="text-xs text-neutral-400 dark:text-neutral-500 font-mono">{{ $perm->name }}</span>
        </div>
    </td>
    @foreach($roles as $role)
        <td class="px-3 py-2.5 text-center">
            @if($role->is_super_admin)
                {{-- Super admin always has all permissions — show locked check --}}
                <span class="inline-flex items-center justify-center w-5 h-5 rounded text-success-500 dark:text-success-400"
                      title="Super Admin — all permissions granted">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                    </svg>
                </span>
            @else
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox"
                           class="sr-only peer"
                           :checked="isChecked({{ $role->id }}, {{ $perm->id }})"
                           @change="togglePermission({{ $role->id }}, {{ $perm->id }}, $event.target.checked)">
                    <div class="w-5 h-5 rounded border-2 border-neutral-300 dark:border-neutral-500
                                peer-checked:border-primary-500 dark:peer-checked:border-primary-400
                                peer-checked:bg-primary-500 dark:peer-checked:bg-primary-400
                                flex items-center justify-center transition-all duration-150
                                hover:border-primary-400 dark:hover:border-primary-500">
                        <svg class="w-3 h-3 text-white opacity-0 peer-checked:opacity-100 transition-opacity"
                             fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                    </div>
                </label>
            @endif
        </td>
    @endforeach
</tr>
