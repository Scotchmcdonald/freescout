@props([
    'name',
    'title' => __('Are you sure?'),
    'content' => __('This action cannot be undone.'),
    'buttonText' => __('Delete'),
    'route' => '#', 
    'method' => 'DELETE',
    'actionVar' => null,
])

<x-modal name="{{ $name }}" {{ $attributes }} focusable>
    <form method="post" action="{{ $route }}" @if($actionVar) :action="{{ $actionVar }}" @endif class="p-6">
        @csrf
        @if(in_array(strtoupper($method), ['PUT', 'PATCH', 'DELETE']))
            @method($method)
        @endif

        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ $title }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ $content }}
        </p>

        <div class="mt-6 flex justify-end">
            <x-secondary-button type="button" x-on:click="$dispatch('close')">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ms-3">
                {{ $buttonText }}
            </x-danger-button>
        </div>
    </form>
</x-modal>
