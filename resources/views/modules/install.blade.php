<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Install Module from GitHub') }}
            </h2>
            <a href="{{ route('modules') }}" class="text-sm text-gray-600 hover:text-gray-900">
                ← {{ __('Back to Modules') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12" x-data="githubModuleInstaller({{ json_encode($repositories) }}, {{ json_encode($savedToken) }})">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            {{ __('Repository Details') }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            {{ __('Install a module directly from a GitHub repository.') }}
                        </p>
                    </div>

                    <form @submit.prevent="installModule" class="space-y-6">

                        <!-- Repository Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('Repository') }} <span class="text-red-500">*</span>
                            </label>
                            
                            <div class="space-y-3">
                                <!-- Catalog Dropdown -->
                                <div>
                                    <select 
                                        x-model="selectedRepo"
                                        @change="onRepoChange"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    >
                                        <option value="">{{ __('Select from catalog...') }}</option>
                                        <template x-for="repo in knownRepos" :key="repo.url">
                                            <option :value="repo.url" x-text="repo.name"></option>
                                        </template>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500" x-show="selectedRepo" x-transition>
                                        <span x-text="selectedRepoDescription"></span>
                                    </p>
                                </div>
                                
                                <!-- OR Divider -->
                                <div class="flex items-center">
                                    <div class="flex-1 border-t border-gray-300"></div>
                                    <span class="px-3 text-xs text-gray-500 uppercase font-medium">{{ __('or enter custom url') }}</span>
                                    <div class="flex-1 border-t border-gray-300"></div>
                                </div>
                                
                                <!-- Custom URL -->
                                <div>
                                    <x-text-input 
                                        id="custom_url" 
                                        class="block w-full" 
                                        type="text" 
                                        x-model="customRepoUrl"
                                        @input="onCustomUrlInput"
                                        placeholder="https://github.com/username/repository or git@github.com:username/repository.git" 
                                    />
                                    <p class="mt-1 text-xs text-gray-500">
                                        {{ __('Supports HTTPS or SSH URLs') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Access Token -->
                        <div>
                            <label for="github_token" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('Personal Access Token') }}
                                <span class="text-gray-500 text-xs font-normal">{{ __('(Optional - for private repos)') }}</span>
                            </label>
                            
                            <div class="flex items-center space-x-2">
                                <x-text-input 
                                    id="github_token" 
                                    class="block flex-1" 
                                    type="password" 
                                    name="github_token"
                                    x-model="accessToken"
                                    placeholder="{{ __('ghp_xxxxxxxxxxxx') }}" 
                                />
                                
                                <button 
                                    type="button"
                                    @click="saveToken"
                                    :disabled="!accessToken || savingToken"
                                    class="px-3 py-2 text-sm font-medium bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    title="{{ __('Save token for future use') }}"
                                >
                                    <span x-show="!savingToken">{{ __('Save') }}</span>
                                    <span x-show="savingToken">...</span>
                                </button>
                                
                                <button 
                                    type="button"
                                    @click="clearToken"
                                    x-show="hasSavedToken"
                                    :disabled="clearingToken"
                                    class="px-3 py-2 text-sm font-medium bg-red-600 text-white rounded-md hover:bg-red-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    title="{{ __('Clear saved token') }}"
                                >
                                    <span x-show="!clearingToken">{{ __('Clear') }}</span>
                                    <span x-show="clearingToken">...</span>
                                </button>
                            </div>
                            
                            <p class="mt-1 text-xs text-green-600" x-show="hasSavedToken">
                                ✓ {{ __('Using saved token') }}
                            </p>
                            
                            <!-- Token Instructions (Collapsible) -->
                            <div x-data="{ expanded: false }" class="mt-3">
                                <button 
                                    type="button"
                                    @click="expanded = !expanded"
                                    class="flex items-center text-sm text-blue-600 hover:text-blue-700"
                                >
                                    <svg class="w-4 h-4 mr-1 transition-transform" :class="{ 'rotate-90': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    {{ __('How to get a Personal Access Token') }}
                                </button>
                                
                                <div x-show="expanded" x-collapse class="mt-3 p-4 bg-gray-50 rounded-lg text-xs text-gray-700 space-y-2">
                                    <p class="font-medium">{{ __('For Private Repositories:') }}</p>
                                    <ol class="list-decimal list-inside space-y-1.5 ml-2">
                                        <li>{{ __('Go to') }} <a href="https://github.com/settings/tokens/new" target="_blank" class="text-blue-600 hover:underline">{{ __('GitHub Settings → Tokens → New Token (Classic)') }}</a></li>
                                        <li>{{ __('Set a descriptive name (e.g., "FreeScout Module Installer")') }}</li>
                                        <li>{{ __('Under "Select scopes", check:') }} <code class="bg-gray-200 px-1.5 py-0.5 rounded text-red-600 font-mono">repo</code></li>
                                        <li>{{ __('Click "Generate token" and copy it immediately') }}</li>
                                        <li>{{ __('Paste the token in the field above') }}</li>
                                    </ol>
                                    <p class="text-gray-500 italic mt-3">{{ __('Note: Public repositories do not require a token.') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Connection Test & Load Branches -->
                        <div class="flex items-center space-x-3 flex-wrap">
                            <button 
                                type="button"
                                @click="previewModule"
                                :disabled="!repoUrl || previewing"
                                class="px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <span class="flex items-center">
                                    <svg x-show="previewing" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 718-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="previewing ? '{{ __('Loading...') }}' : '{{ __('Preview Module') }}'"></span>
                                </span>
                            </button>
                        </div>
                        
                        <div class="flex items-center space-x-3 flex-wrap mt-3"
                            <button 
                                type="button"
                                @click="testConnection"
                                :disabled="!repoUrl || testing"
                                class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <span class="flex items-center">
                                    <svg x-show="testing" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="testing ? '{{ __('Testing...') }}' : '{{ __('Test Connection') }}'"></span>
                                </span>
                            </button>
                            
                            <button 
                                type="button"
                                @click="loadBranches"
                                :disabled="!canLoadBranches || loading"
                                x-show="!isSSHUrl"
                                class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <span class="flex items-center">
                                    <svg x-show="loading" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-show="loading">Loading...</span>
                                    <span x-show="!loading">Load Branches &amp; Commits</span>
                                </span>
                            </button>
                            
                            <p class="text-xs text-gray-500" x-show="!repoUrl">
                                {{ __('Enter a repository URL first') }}
                            </p>
                        </div>

                        <!-- Connection Test Results -->
                        <div x-show="connectionResult" x-transition>
                            <div class="p-4 rounded-lg border" :class="connectionSuccess ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
                                <p class="text-sm font-medium" :class="connectionSuccess ? 'text-green-800' : 'text-red-800'" x-text="connectionMessage"></p>
                                <div x-show="connectionSuggestions.length > 0" class="mt-2">
                                    <ul class="text-xs space-y-1" :class="connectionSuccess ? 'text-green-700' : 'text-red-700'">
                                        <template x-for="suggestion in connectionSuggestions" :key="suggestion">
                                            <li class="flex items-start">
                                                <span class="mr-1">•</span>
                                                <span x-text="suggestion"></span>
                                            </li>
                                        </template>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div x-show="error" x-transition class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-700" x-text="error"></p>
                        </div>

                        <!-- Installation Error -->
                        <div x-show="installError" x-transition class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-sm text-red-700" x-text="installError"></p>
                        </div>

                        <!-- Branch Selection -->
                        <div x-show="branches.length > 0" x-transition>
                            <label for="github_branch" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('Branch') }}
                                <span class="text-gray-500 text-xs font-normal">{{ __('(Optional)') }}</span>
                            </label>
                            <select 
                                id="github_branch" 
                                name="github_branch"
                                x-model="selectedBranch"
                                @change="loadCommits"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">{{ __('Select a branch...') }}</option>
                                <template x-for="branch in branches" :key="branch.name">
                                    <option :value="branch.name" x-text="branch.name + (branch.name === defaultBranch ? ' (default)' : '')"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __('Leave blank to use the default branch') }}
                            </p>
                        </div>

                        <!-- Commit Selection -->
                        <div x-show="commits.length > 0" x-transition>
                            <label for="github_commit" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ __('Commit') }}
                                <span class="text-gray-500 text-xs font-normal">{{ __('(Optional)') }}</span>
                            </label>
                            <select 
                                id="github_commit" 
                                name="github_commit"
                                x-model="selectedCommit"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="">{{ __('Select a commit (latest)...') }}</option>
                                <template x-for="commit in commits" :key="commit.sha">
                                    <option :value="commit.sha" x-text="`${commit.sha.substring(0, 7)} - ${commit.message} (${commit.date})`"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __('Leave blank to use the latest commit') }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4 border-t">
                            <!-- Progress Display -->
                            <div x-show="installing && installProgress > 0" class="mb-4" x-transition>
                                <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                                    <span x-text="installMessage"></span>
                                    <span x-text="installProgress + '%'"></span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                                    <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500"
                                         :style="'width: ' + installProgress + '%'"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 capitalize" x-text="installStage"></p>
                            </div>

                            <button 
                                type="submit" 
                                :disabled="installing"
                                class="w-full px-4 py-3 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <span class="flex items-center justify-center">
                                    <svg x-show="installing" class="animate-spin h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="installing ? (installProgress > 0 ? '{{ __('Installing...') }}' : '{{ __('Starting...') }}') : '{{ __('Install Module') }}'"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Preview Modal -->
            <div x-show="showPreviewModal" 
                 x-cloak
                 @keydown.escape.window="showPreviewModal = false"
                 class="fixed inset-0 z-50 overflow-y-auto"
                 style="display: none;">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <!-- Background overlay -->
                    <div x-show="showPreviewModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0"
                         class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75"
                         @click="showPreviewModal = false"></div>

                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                    <!-- Modal panel -->
                    <div x-show="showPreviewModal"
                         x-transition:enter="ease-out duration-300"
                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave="ease-in duration-200"
                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                         class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full sm:p-6">
                        
                        <div class="absolute top-0 right-0 pt-4 pr-4">
                            <button type="button" 
                                    @click="showPreviewModal = false"
                                    class="bg-white rounded-md text-gray-400 hover:text-gray-500 focus:outline-none">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="sm:flex sm:items-start">
                            <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    {{ __('Module Preview') }}
                                </h3>

                                <div x-show="previewData" class="space-y-4">
                                    <!-- Module Info -->
                                    <div x-show="previewData?.module_info" class="bg-gray-50 rounded-lg p-4">
                                        <h4 class="font-semibold text-gray-900 mb-2" x-text="previewData?.module_info?.name || '{{ __('Module') }}'"></h4>
                                        <div class="text-sm space-y-1 text-gray-600">
                                            <p><strong>{{ __('Version:') }}</strong> <span x-text="previewData?.module_info?.version"></span></p>
                                            <p x-show="previewData?.module_info?.description"><strong>{{ __('Description:') }}</strong> <span x-text="previewData?.module_info?.description"></span></p>
                                            <p x-show="previewData?.module_info?.author"><strong>{{ __('Author:') }}</strong> <span x-text="previewData?.module_info?.author"></span></p>
                                        </div>
                                    </div>

                                    <!-- Composer Dependencies -->
                                    <div x-show="previewData?.composer_info?.require" class="bg-blue-50 rounded-lg p-4">
                                        <h4 class="font-semibold text-gray-900 mb-2">{{ __('Dependencies') }}</h4>
                                        <div class="text-sm space-y-1">
                                            <template x-for="(version, package) in previewData?.composer_info?.require" :key="package">
                                                <p class="text-gray-700">
                                                    <code class="text-xs bg-white px-2 py-1 rounded" x-text="package"></code>
                                                    <span class="text-gray-500" x-text="': ' + version"></span>
                                                </p>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- README -->
                                    <div x-show="previewData?.readme" class="bg-white border rounded-lg p-4 max-h-96 overflow-y-auto">
                                        <h4 class="font-semibold text-gray-900 mb-2">{{ __('README') }}</h4>
                                        <div class="prose prose-sm max-w-none text-left" x-html="previewData?.readme ? previewData.readme.replace(/\n/g, '<br>') : ''"></div>
                                    </div>

                                    <!-- No data available -->
                                    <div x-show="!previewData?.module_info && !previewData?.readme && !previewData?.composer_info" class="text-center text-gray-500 py-8">
                                        <p>{{ __('No preview data available') }}</p>
                                    </div>
                                </div>

                                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                    <button type="button"
                                            @click="showPreviewModal = false"
                                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-gray-800 text-base font-medium text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:ml-3 sm:w-auto sm:text-sm">
                                        {{ __('Close') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function githubModuleInstaller(repositories, savedToken) {
            return {
                knownRepos: repositories || [],
                selectedRepo: '',
                customRepoUrl: '',
                repoUrl: '',
                accessToken: savedToken || '',
                hasSavedToken: !!savedToken,
                savingToken: false,
                clearingToken: false,
                testing: false,
                connectionResult: false,
                connectionSuccess: false,
                connectionMessage: '',
                connectionSuggestions: [],
                previewing: false,
                previewData: null,
                showPreviewModal: false,
                owner: '',
                repo: '',
                branches: [],
                commits: [],
                selectedBranch: '',
                selectedCommit: '',
                defaultBranch: '',
                loading: false,
                error: '',
                installing: false,
                installError: '',
                installProgress: 0,
                installStage: '',
                installMessage: '',

                get canLoadBranches() {
                    return this.repoUrl && this.owner && this.repo && !this.isSSHUrl;
                },

                get selectedRepoDescription() {
                    const repo = this.knownRepos.find(r => r.url === this.selectedRepo);
                    return repo?.description || '';
                },

                get isSSHUrl() {
                    return this.repoUrl.startsWith('git@') || this.repoUrl.includes('ssh://');
                },

                onRepoChange() {
                    if (this.selectedRepo) {
                        this.customRepoUrl = '';
                        this.repoUrl = this.selectedRepo;
                        this.parseRepoUrl();
                        this.connectionResult = false;
                    }
                },

                onCustomUrlInput() {
                    if (this.customRepoUrl) {
                        this.selectedRepo = '';
                        this.repoUrl = this.customRepoUrl;
                        this.parseRepoUrl();
                        this.connectionResult = false;
                    }
                },

                async testConnection() {
                    if (!this.repoUrl) return;

                    this.testing = true;
                    this.connectionResult = false;
                    this.error = '';

                    try {
                        const response = await fetch('{{ route('modules.test-connection') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                url: this.repoUrl,
                                token: this.accessToken
                            })
                        });

                        const data = await response.json();
                        this.connectionResult = true;
                        this.connectionSuccess = response.ok;
                        this.connectionMessage = data.message || '';
                        this.connectionSuggestions = data.suggestions || [];

                        if (response.ok && data.repo_info) {
                            this.defaultBranch = data.repo_info.default_branch;
                        }
                    } catch (err) {
                        this.connectionResult = true;
                        this.connectionSuccess = false;
                        this.connectionMessage = '{{ __('Connection test failed') }}';
                        this.connectionSuggestions = [err.message];
                    } finally {
                        this.testing = false;
                    }
                },

                async previewModule() {
                    if (!this.repoUrl) return;

                    this.previewing = true;
                    this.error = '';

                    try {
                        const response = await fetch('{{ route('modules.preview') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]').content,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                repo_url: this.repoUrl,
                                branch: this.selectedBranch || this.defaultBranch || 'main'
                            })
                        });

                        const data = await response.json();
                        
                        if (response.ok) {
                            this.previewData = data;
                            this.showPreviewModal = true;
                        } else {
                            this.error = data.message || '{{ __('Failed to load module preview') }}';
                        }
                    } catch (err) {
                        this.error = err.message || '{{ __('Failed to load module preview') }}';
                    } finally {
                        this.previewing = false;
                    }
                },

                async saveToken() {
                    if (!this.accessToken) return;

                    this.savingToken = true;
                    try {
                        const response = await fetch('{{ route('modules.github-token.save') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ token: this.accessToken })
                        });

                        if (response.ok) {
                            this.hasSavedToken = true;
                        }
                    } catch (err) {
                        console.error('Error saving token:', err);
                    } finally {
                        this.savingToken = false;
                    }
                },

                async clearToken() {
                    this.clearingToken = true;
                    try {
                        const response = await fetch('{{ route('modules.github-token.clear') }}', {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            }
                        });

                        if (response.ok) {
                            this.accessToken = '';
                            this.hasSavedToken = false;
                        }
                    } catch (err) {
                        console.error('Error clearing token:', err);
                    } finally {
                        this.clearingToken = false;
                    }
                },

                parseRepoUrl() {
                    const match = this.repoUrl.match(/github\.com\/([^\/]+)\/([^\/]+)/);
                    if (match) {
                        this.owner = match[1];
                        this.repo = match[2].replace(/\.git$/, '');
                        this.error = '';
                    } else if (this.repoUrl) {
                        this.error = 'Invalid GitHub URL format';
                    }
                },

                async loadBranches() {
                    if (!this.canLoadBranches) return;

                    this.loading = true;
                    this.error = '';
                    this.branches = [];
                    this.commits = [];
                    this.selectedBranch = '';
                    this.selectedCommit = '';

                    try {
                        const headers = {
                            'Accept': 'application/vnd.github.v3+json'
                        };
                        
                        if (this.accessToken) {
                            headers['Authorization'] = `Bearer ${this.accessToken}`;
                        }

                        // Fetch repository info for default branch
                        const repoResponse = await fetch(`https://api.github.com/repos/${this.owner}/${this.repo}`, { headers });
                        
                        if (repoResponse.status === 404) {
                            throw new Error('Repository not found. Please check the URL and try again.');
                        }
                        
                        if (repoResponse.status === 401 || repoResponse.status === 403) {
                            if (this.accessToken) {
                                throw new Error('Authentication failed. Please check your Personal Access Token.');
                            } else {
                                throw new Error('This is a private repository. Please provide a Personal Access Token.');
                            }
                        }
                        
                        if (!repoResponse.ok) {
                            const errorData = await repoResponse.json().catch(() => ({}));
                            throw new Error(errorData.message || `Failed to fetch repository (${repoResponse.status})`);
                        }
                        
                        const repoData = await repoResponse.json();
                        this.defaultBranch = repoData.default_branch;

                        // Fetch branches
                        const branchesResponse = await fetch(`https://api.github.com/repos/${this.owner}/${this.repo}/branches`, { headers });
                        if (!branchesResponse.ok) {
                            const errorData = await branchesResponse.json().catch(() => ({}));
                            throw new Error(errorData.message || `Failed to fetch branches (${branchesResponse.status})`);
                        }
                        const branchesData = await branchesResponse.json();
                        this.branches = branchesData.map(b => ({ name: b.name }));

                        // Auto-select default branch and load its commits
                        this.selectedBranch = this.defaultBranch;
                        await this.loadCommits();

                    } catch (err) {
                        this.error = err.message;
                        console.error('Error loading branches:', err);
                    } finally {
                        this.loading = false;
                    }
                },

                async loadCommits() {
                    if (!this.selectedBranch) {
                        this.commits = [];
                        return;
                    }

                    this.loading = true;
                    this.error = '';
                    this.commits = [];

                    try {
                        const headers = {
                            'Accept': 'application/vnd.github.v3+json'
                        };
                        
                        if (this.accessToken) {
                            headers['Authorization'] = `Bearer ${this.accessToken}`;
                        }

                        const response = await fetch(
                            `https://api.github.com/repos/${this.owner}/${this.repo}/commits?sha=${this.selectedBranch}&per_page=20`,
                            { headers }
                        );

                        if (!response.ok) {
                            const errorData = await response.json().catch(() => ({}));
                            throw new Error(errorData.message || `Failed to fetch commits (${response.status})`);
                        }

                        const commitsData = await response.json();
                        this.commits = commitsData.map(c => ({
                            sha: c.sha,
                            message: c.commit.message.split('\n')[0].substring(0, 60),
                            date: new Date(c.commit.author.date).toLocaleDateString()
                        }));

                    } catch (err) {
                        this.error = err.message;
                        console.error('Error loading commits:', err);
                    } finally {
                        this.loading = false;
                    }
                },

                async installModule() {
                    if (this.installing) return;

                    this.installing = true;
                    this.installError = '';
                    this.installProgress = 0;
                    this.installStage = 'starting';
                    this.installMessage = '{{ __('Starting installation...') }}';

                    try {
                        const url = new URL('{{ route('modules.install.stream') }}');
                        const params = new URLSearchParams({
                            url: this.repoUrl,
                            token: this.accessToken || '',
                            branch: this.selectedBranch || '',
                            commit: this.selectedCommit || ''
                        });

                        const eventSource = new EventSource(url + '?' + params);

                        eventSource.onmessage = (event) => {
                            const data = JSON.parse(event.data);
                            
                            this.installProgress = data.percentage || 0;
                            this.installStage = data.stage || '';
                            this.installMessage = data.message || '';

                            if (data.stage === 'done' || data.success) {
                                eventSource.close();
                                // Redirect after a short delay
                                setTimeout(() => {
                                    window.location.href = data.redirect || '{{ route('modules') }}';
                                }, 1000);
                            } else if (data.stage === 'error' || data.error) {
                                eventSource.close();
                                this.installError = data.message || '{{ __('Installation failed') }}';
                                this.installing = false;
                            }
                        };

                        eventSource.onerror = (error) => {
                            console.error('EventSource error:', error);
                            eventSource.close();
                            
                            if (!this.installError) {
                                this.installError = '{{ __('Connection lost during installation') }}';
                            }
                            this.installing = false;
                        };

                    } catch (err) {
                        this.installError = err.message || '{{ __('Installation failed') }}';
                        console.error('Installation error:', err);
                        this.installing = false;
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
