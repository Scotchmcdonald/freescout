@props(['steps', 'currentStep' => 1])

<div class="w-full py-6">
    <div class="flex items-center justify-between px-16">
        @foreach($steps as $index => $label)
            @php 
                $stepNum = $index + 1; 
                $isCompleted = $stepNum < $currentStep;
                $isActive = $stepNum == $currentStep;
                $isLast = $stepNum == count($steps);
            @endphp
            
            <!-- Step Node -->
            <div class="relative flex flex-col items-center group z-10">
                <div class="rounded-full transition-all duration-500 ease-in-out h-10 w-10 flex items-center justify-center border-2 bg-white shadow-sm
                    {{ $isCompleted ? 'bg-primary-600 border-primary-600 text-white' : '' }}
                    {{ $isActive ? 'border-primary-600 text-primary-600 ring-4 ring-primary-50' : '' }}
                    {{ !$isCompleted && !$isActive ? 'border-gray-200 text-gray-300' : '' }}">
                    
                    @if($isCompleted)
                        <!-- Checkmark -->
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    @elseif($isActive)
                        <!-- Spinner or Number -->
                        <span class="font-bold text-sm font-mono">{{ $stepNum }}</span>
                    @else
                        <span class="font-bold text-sm font-mono">{{ $stepNum }}</span>
                    @endif
                </div>
                
                <div class="absolute top-0 -ml-16 text-center mt-12 w-40 text-xs font-bold uppercase tracking-wider transition-colors duration-300
                    {{ $stepNum <= $currentStep ? 'text-primary-900' : 'text-gray-400' }}">
                    {{ $label }}
                </div>
            </div>
            
            <!-- Connector Line -->
            @if(!$isLast)
                <div class="flex-auto border-t-2 transition-colors duration-500 ease-in-out mx-4
                    {{ $stepNum < $currentStep ? 'border-primary-600' : 'border-gray-200' }}"></div>
            @endif
        @endforeach
    </div>
</div>
