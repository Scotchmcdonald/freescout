{{-- Person Photo Component --}}
{{-- Displays avatar/photo with initials fallback --}}
@if (!empty($person))
    @php
        $size = $size ?? 'md';
        $sizeClasses = [
            'xs' => 'h-6 w-6 text-xs',
            'sm' => 'h-8 w-8 text-sm',
            'md' => 'h-10 w-10 text-base',
            'lg' => 'h-12 w-12 text-lg',
            'xl' => 'h-16 w-16 text-xl',
            '2xl' => 'h-24 w-24 text-2xl',
        ];
        $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
        
        // Get photo URL
        $photoUrl = null;
        if (method_exists($person, 'getPhotoUrl')) {
            $photoUrl = $person->getPhotoUrl();
        } elseif (isset($person->photo_url) && $person->photo_url) {
            $photoUrl = $person->photo_url;
        }
        
        // Get initials
        $initials = '';
        if (isset($person->first_name) && isset($person->last_name)) {
            $initials = strtoupper(mb_substr($person->first_name, 0, 1)) . strtoupper(mb_substr($person->last_name, 0, 1));
        } elseif (isset($person->name)) {
            $nameParts = explode(' ', $person->name);
            $initials = strtoupper(mb_substr($nameParts[0], 0, 1));
            if (count($nameParts) > 1) {
                $initials .= strtoupper(mb_substr($nameParts[count($nameParts) - 1], 0, 1));
            }
        } elseif (isset($person->email)) {
            $initials = strtoupper(mb_substr($person->email, 0, 1));
        }
        
        $altText = '';
        if (isset($person->first_name) && isset($person->last_name)) {
            $altText = $person->first_name . ' ' . $person->last_name;
        } elseif (isset($person->name)) {
            $altText = $person->name;
        } elseif (isset($person->email)) {
            $altText = $person->email;
        }
    @endphp
    
    @if ($photoUrl)
        <img class="person-photo inline-block {{ $sizeClass }} rounded-full object-cover" src="{{ $photoUrl }}" alt="{{ $altText }}">
    @else
        <span class="person-photo person-photo-auto inline-flex items-center justify-center {{ $sizeClass }} rounded-full bg-gray-500">
            <span class="font-medium leading-none text-white">{{ $initials }}</span>
        </span>
    @endif
@endif
