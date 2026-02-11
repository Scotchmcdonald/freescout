@props(['options', 'name', 'id' => null, 'selected' => null, 'label' => null, 'placeholder' => 'Select an option...'])

@php
    $id = $id ?? $name;
    // Normalize options to ensure they are iterable and have label/value
    $normalizedOptions = [];
    foreach($options as $key => $value) {
        if(is_object($value)) {
             // Handle objects (like Eloquent models)
             $normalizedOptions[] = ['value' => $value->id, 'label' => $value->name ?? $value->title ?? $value->label ?? 'Option'];
        } elseif(is_array($value)) {
             // Handle arrays
             $normalizedOptions[] = ['value' => $value['id'] ?? $key, 'label' => $value['name'] ?? $value['label'] ?? $value];
        } else {
             // Handle simple key-value pairs
             $normalizedOptions[] = ['value' => $key, 'label' => $value];
        }
    }
@endphp

<div x-data="{
    options: {{ json_encode($normalizedOptions) }},
    value: '{{ $selected }}',
    open: false,
    search: '',
    get filteredOptions() {
        if (this.search === '') {
            return this.options;
        }
        return this.options.filter(option => {
            return option.label.toLowerCase().includes(this.search.toLowerCase());
        });
    },
    get selectedLabel() {
        const selectedOption = this.options.find(o => o.value == this.value);
        return selectedOption ? selectedOption.label : '{{ $placeholder }}';
    },
    select(option) {
        this.value = option.value;
        this.open = false;
        this.search = '';
    }
}" class="relative">
    @if($label)
        <label for="{{ $id }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">{{ $label }}</label>
    @endif

    <input type="hidden" name="{{ $name }}" :value="value" id="{{ $id }}">

    <div class="relative">
        <button type="button" @click="open = !open" @click.away="open = false"
                class="relative w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm pl-3 pr-10 py-2 text-left cursor-default focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
            <span class="block truncate" x-text="selectedLabel" :class="{'text-gray-500': !value}"></span>
            <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l3 3a1 1 0 01-1.414 1.414L10 5.414 7.707 7.707a1 1 0 01-1.414-1.414l3-3A1 1 0 0110 3zm-3.707 9.293a1 1 0 011.414 0L10 14.586l2.293-2.293a1 1 0 011.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </span>
        </button>

        <div x-show="open" x-cloak
             style="display: none;"
             class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
            
            <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 p-2 border-b border-gray-200 dark:border-gray-700">
                <input type="text" x-model="search" placeholder="Search..."
                       class="w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500 text-sm"
                       @click.stop>
            </div>

            <ul class="pt-1">
                <template x-for="option in filteredOptions" :key="option.value">
                    <li @click="select(option)"
                        class="text-gray-900 dark:text-gray-300 cursor-default select-none relative py-2 pl-3 pr-9 hover:bg-primary-50 dark:hover:bg-gray-700"
                        :class="{'bg-primary-50 dark:bg-gray-700': value == option.value}">
                        <span class="block truncate" x-text="option.label" :class="{'font-semibold': value == option.value, 'font-normal': value != option.value}"></span>
                        
                        <span x-show="value == option.value" class="absolute inset-y-0 right-0 flex items-center pr-4 text-primary-600">
                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </li>
                </template>
                <div x-show="filteredOptions.length === 0" class="py-2 px-3 text-gray-500 dark:text-gray-400 text-sm">
                    No results found.
                </div>
            </ul>
        </div>
    </div>
</div>
