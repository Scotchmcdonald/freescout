<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
            {{ __('Data Import') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-col md:flex-row">
            <x-settings-sidebar :sections="$sections" :current-section="$currentSection" />
            
            <div class="flex-1 space-y-6">
                
                @if(session('success'))
                    <div class="bg-success-100 border border-success-400 text-success-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                @endif
                
                @if(session('error'))
                    <div class="bg-danger-100 border border-danger-400 text-danger-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                @endif
            
                <!-- Clients Import Card -->
                <div id="import-clients-card" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-neutral-200">
                        <h3 class="text-lg font-medium text-neutral-900 mb-4">{{ __('Import Clients') }}</h3>
                        <p class="text-sm text-neutral-600 mb-4">
                            Import client organizations from a CSV file.
                        </p>
                        
                        <div class="mb-4">
                             <a href="/import_templates/clients_import_template.csv" class="text-primary-600 hover:text-primary-900 text-sm font-medium" download>
                                Download Clients Template
                            </a>
                        </div>
                        
                        <form method="POST" action="{{ route('crm.clients.import.process') }}" enctype="multipart/form-data" class="flex items-center gap-4">
                            @csrf
                            <input class="block w-full text-sm text-neutral-900 border border-neutral-300 rounded-lg cursor-pointer bg-neutral-50 focus:outline-none" type="file" name="csv_file" required accept=".csv" />
                            <x-primary-button>
                                {{ __('Import Clients') }}
                            </x-primary-button>
                        </form>
                         <x-input-error :messages="$errors->get('csv_file')" class="mt-2" />
                    </div>
                </div>
                
                <!-- Products Import Card -->
                @if(Module::isEnabled('SoftwareSubscriptions'))
                <div id="import-products-card" class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-neutral-200">
                        <h3 class="text-lg font-medium text-neutral-900 mb-4">{{ __('Import Software Products') }}</h3>
                         <p class="text-sm text-neutral-600 mb-4">
                            Import software catalog items from a CSV file.
                        </p>

                        <div class="mb-4">
                             <a href="/import_templates/products_import_template.csv" class="text-primary-600 hover:text-primary-900 text-sm font-medium" download>
                                Download Products Template
                            </a>
                        </div>
                        
                        <form method="POST" action="{{ route('admin.softwaresubscriptions.products.import.process') }}" enctype="multipart/form-data" class="flex items-center gap-4">
                            @csrf
                            <input class="block w-full text-sm text-neutral-900 border border-neutral-300 rounded-lg cursor-pointer bg-neutral-50 focus:outline-none" type="file" name="csv_file" required accept=".csv" />
                            <x-primary-button>
                                {{ __('Import Products') }}
                            </x-primary-button>
                        </form>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
