{{-- RBAC Role Header Component --}}
@props([
    'role' => null,
    'canDelete' => true,
])

<th class="px-3 py-3 text-center min-w-[120px] max-w-[160px]">
    <div class="flex flex-col items-center gap-1">
        <span class="text-sm font-semibold text-gray-700 dark:text-gray-200 truncate max-w-full"
              title="{{ $role->label ?? $role->name }}">
            {{ $role->label ?? $role->name }}
        </span>

        @if($role->is_super_admin)
            <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400">
                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 1l2.928 5.856L19 7.875l-4.5 4.386L15.855 19 10 15.856 4.145 19l1.355-6.74L1 7.876l6.072-1.019L10 1z" clip-rule="evenodd" />
                </svg>
                Super Admin
            </span>
        @else
            <span class="text-[10px] text-gray-400 dark:text-gray-500 font-mono">{{ $role->scope }}</span>
        @endif

        @if($canDelete && !$role->is_super_admin)
            {{-- Role deletion intentionally disabled to prevent accidental removal.
                 To delete a role, use: php artisan tinker → Role::where('name','...')->first()->delete() --}}
        @endif
    </div>
</th>
