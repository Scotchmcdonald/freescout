@props(['percent' => 0, 'label' => null, 'color' => 'primary', 'alpine' => null])

<div class="w-full">
    @if($label)
        <div class="flex justify-between mb-1">
            <span class="text-sm font-medium text-gray-700">{{ $label }}</span>
            <span class="text-sm font-medium text-gray-700">
                @if($alpine)
                    <span x-text="{{ $alpine }}"></span>%
                @else
                    {{ $percent }}%
                @endif
            </span>
        </div>
    @endif
    <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 overflow-hidden">
        <div class="bg-{{ $color }}-600 h-2.5 rounded-full transition-all duration-700 ease-out" 
             @if($alpine) :style="'width: ' + {{ $alpine }} + '%'" @else style="width: {{ $percent }}%" @endif></div>
    </div>
</div>
