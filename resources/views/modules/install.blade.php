<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-neutral-800 leading-tight">
                {{ __('Install Module from GitHub') }}
            </h2>
            <a href="{{ route('modules') }}" class="text-sm text-neutral-600 hover:text-neutral-900">
                ← {{ __('Back to Modules') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12" x-data="githubModuleInstaller({{ json_encode($repositories) }}, {{ json_encode($savedToken) }})">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-neutral-900">
                            {{ __('Repository Details') }}
                        </h3>
                        <p class="mt-1 text-sm text-neutral-600">
                            {{ __('Install a module directly from a GitHub repository.') }}
                        </p>
                    </div>

                    <form @submit.prevent="installModule" class="space-y-6">

                        <!-- Repository Selection -->
                        <div>
                            <label class="block text-sm font-medium text-neutral-700 mb-2">
                                {{ __('Repository') }} <span class="text-danger-500">*</span>
                            </label>
                            
                            <div class="space-y-3">
                                <!-- Catalog Dropdown -->
                                <div>
                                    <select 
                                        x-model="selectedRepo"
                                        @change="onRepoChange"
                                        class="w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                    >
                                        <option value="">{{ __('Select from catalog...') }}</option>
                                        <template x-for="repo in knownRepos" :key="repo.url">
                                            <option :value="repo.url" x-text="repo.name"></option>
                                        </template>
                                    </select>
                                    <p class="mt-1 text-xs text-neutral-500" x-show="selectedRepo" x-transition>
                                        <span x-text="selectedRepoDescription"></span>
                                    </p>
                                </div>
                                
                                <!-- OR Divider -->
                                <div class="flex items-center">
                                    <div class="flex-1 border-t border-neutral-300"></div>
                                    <span class="px-3 text-xs text-neutral-500 uppercase font-medium">{{ __('or enter custom url') }}</span>
                                    <div class="flex-1 border-t border-neutral-300"></div>
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
                                    <p class="mt-1 text-xs text-neutral-500">
                                        {{ __('Supports HTTPS or SSH URLs') }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Access Token -->
                        <div>
                            <label for="github_token" class="block text-sm font-medium text-neutral-700 mb-2">
                                {{ __('Personal Access Token') }}
                                <span class="text-neutral-500 text-xs font-normal">{{ __('(Optional - for private repos)') }}</span>
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
                                    class="px-3 py-2 text-sm font-medium bg-success-600 text-white rounded-md hover:bg-success-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
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
                                    class="px-3 py-2 text-sm font-medium bg-danger-600 text-white rounded-md hover:bg-danger-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    title="{{ __('Clear saved token') }}"
                                >
                                    <span x-show="!clearingToken">{{ __('Clear') }}</span>
                                    <span x-show="clearingToken">...</span>
                                </button>
                            </div>
                            
                            <p class="mt-1 text-xs" style="color: var(--theme-status-success-text)" x-show="hasSavedToken">
                                ✓ {{ __('Using saved token') }}
                            </p>
                            
                            <!-- Token Instructions (Collapsible) -->
                            <div x-data="{ expanded: false }" class="mt-3">
                                <button 
                                    type="button"
                                    @click="expanded = !expanded"
                                    class="flex items-center text-sm text-primary-600 hover:text-primary-700"
                                >
                                    <svg class="w-4 h-4 mr-1 transition-transform" :class="{ 'rotate-90': expanded }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    {{ __('How to get a Personal Access Token') }}
                                </button>
                                
                                <div x-show="expanded" x-collapse class="mt-3 p-4 rounded-lg text-xs space-y-2" style="background-color: var(--theme-bg-hover); color: var(--theme-text-muted)">
                                    <p class="font-medium">{{ __('For Private Repositories:') }}</p>
                                    <ol class="list-decimal list-inside space-y-1.5 ml-2">
                                        <li>{{ __('Go to') }} <a href="https://github.com/settings/tokens/new" target="_blank" class="hover:underline" style="color: var(--theme-primary-600)">{{ __('GitHub Settings → Tokens → New Token (Classic)') }}</a></li>
                                        <li>{{ __('Set a descriptive name (e.g., "FreeScout Module Installer")') }}</li>
                                        <li>{{ __('Under "Select scopes", check:') }} <code class="px-1.5 py-0.5 rounded font-mono" style="background-color: var(--theme-bg-input); color: var(--theme-status-error-text)">repo</code></li>
                                        <li>{{ __('Click "Generate token" and copy it immediately') }}</li>
                                        <li>{{ __('Paste the token in the field above') }}</li>
                                    </ol>
                                    <p class="italic mt-3" style="color: var(--theme-text-muted)">{{ __('Note: Public repositories do not require a token.') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Connection Test & Load Branches -->
                        <div class="flex items-center space-x-3 flex-wrap">
                            <button 
                                type="button"
                                @click="previewModule"
                                :disabled="!repoUrl || previewing"
                                class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                            >
                                <span class="flex items-center">
                                    <svg x-show="previewing" class="animate-spin h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 718-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span x-text="previewing ? '{{ __('Analyzing...') }}' : '{{ __('Preview Module') }}'"></span>
                                </span>
                            </button>
                        </div>
                        
                        <div class="flex items-center space-x-3 flex-wrap mt-3"
                            <button 
                                type="button"
                                @click="testConnection"
                                :disabled="!repoUrl || testing"
                                class="px-4 py-2 bg-success-600 text-white text-sm font-medium rounded-md hover:bg-success-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
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
                                class="px-4 py-2 bg-primary-600 text-white text-sm font-medium rounded-md hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
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
                            
                            <p class="text-xs text-neutral-500" x-show="!repoUrl">
                                {{ __('Enter a repository URL first') }}
                            </p>
                        </div>

                        <!-- Connection Test Results -->
                        <div x-show="connectionResult" x-transition>
                            <div class="p-4 rounded-lg border" 
                                 :style="connectionSuccess ? 'background-color: var(--theme-status-success-bg); border-color: var(--theme-status-success-bg)' : 'background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-bg)'">
                                <p class="text-sm font-medium" 
                                   :style="connectionSuccess ? 'color: var(--theme-status-success-text)' : 'color: var(--theme-status-error-text)'" 
                                   x-text="connectionMessage"></p>
                                <div x-show="connectionSuggestions.length > 0" class="mt-2">
                                    <ul class="text-xs space-y-1" 
                                        :style="connectionSuccess ? 'color: var(--theme-status-success-text)' : 'color: var(--theme-status-error-text)'">
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
                        <div x-show="error" x-transition class="p-4 border rounded-lg" style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-bg)">
                            <p class="text-sm" style="color: var(--theme-status-error-text)" x-text="error"></p>
                        </div>

                        <!-- Installation Error -->
                        <div x-show="installError" x-transition class="p-4 border rounded-lg" style="background-color: var(--theme-status-error-bg); border-color: var(--theme-status-error-bg)">
                            <p class="text-sm" style="color: var(--theme-status-error-text)" x-text="installError"></p>
                        </div>

                        <!-- Branch Selection -->
                        <div x-show="branches.length > 0" x-transition>
                            <label for="github_branch" class="block text-sm font-medium text-neutral-700 mb-2">
                                {{ __('Branch') }}
                                <span class="text-neutral-500 text-xs font-normal">{{ __('(Optional)') }}</span>
                            </label>
                            <select 
                                id="github_branch" 
                                name="github_branch"
                                x-model="selectedBranch"
                                @change="loadCommits"
                                class="block w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">{{ __('Select a branch...') }}</option>
                                <template x-for="branch in branches" :key="branch.name">
                                    <option :value="branch.name" x-text="branch.name + (branch.name === defaultBranch ? ' (default)' : '')"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-neutral-500">
                                {{ __('Leave blank to use the default branch') }}
                            </p>
                        </div>

                        <!-- Commit Selection -->
                        <div x-show="commits.length > 0" x-transition>
                            <label for="github_commit" class="block text-sm font-medium text-neutral-700 mb-2">
                                {{ __('Commit') }}
                                <span class="text-neutral-500 text-xs font-normal">{{ __('(Optional)') }}</span>
                            </label>
                            <select 
                                id="github_commit" 
                                name="github_commit"
                                x-model="selectedCommit"
                                class="block w-full rounded-md border-neutral-300 shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                                <option value="">{{ __('Select a commit (latest)...') }}</option>
                                <template x-for="commit in commits" :key="commit.sha">
                                    <option :value="commit.sha" x-text="`${commit.sha.substring(0, 7)} - ${commit.message} (${commit.date})`"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-neutral-500">
                                {{ __('Leave blank to use the latest commit') }}
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4 border-t">
                            <!-- Progress Display -->
                            <div x-show="installing && installProgress > 0" class="mb-4" x-transition>
                                <div class="flex items-center justify-between text-sm text-neutral-600 mb-2">
                                    <span x-text="installMessage"></span>
                                    <span x-text="installProgress + '%'"></span>
                                </div>
                                <x-progress-bar 
                                    alpine="installProgress" 
                                    color="primary" 
                                />
                                <p class="text-xs text-neutral-500 mt-1 capitalize" x-text="installStage"></p>
                            </div>

                            <button 
                                type="submit" 
                                :disabled="installing"
                                class="w-full px-4 py-3 text-sm font-medium rounded-md disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                style="background-color: var(--theme-primary-600); color: var(--theme-text-inverted)"
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
                         class="fixed inset-0 transition-opacity bg-neutral-500 bg-opacity-75"
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
                                    class="bg-white rounded-md text-neutral-400 hover:text-neutral-500 focus:outline-none">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="sm:flex sm:items-start">
                            <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                                <h3 class="text-lg leading-6 font-medium text-neutral-900 mb-4">
                                    {{ __('Module Preview') }}
                                </h3>

                                <div x-show="previewData" class="space-y-4">
                                    <!-- Module Info -->
                                    <div x-show="previewData?.module_info" class="bg-neutral-50 rounded-lg p-4">
                                        <h4 class="font-semibold text-neutral-900 mb-2" x-text="previewData?.module_info?.name || '{{ __('Module') }}'"></h4>
                                        <div class="text-sm space-y-1 text-neutral-600">
                                            <p><strong>{{ __('Version:') }}</strong> <span x-text="previewData?.module_info?.version"></span></p>
                                            <p x-show="previewData?.module_info?.description"><strong>{{ __('Description:') }}</strong> <span x-text="previewData?.module_info?.description"></span></p>
                                            <p x-show="previewData?.module_info?.author"><strong>{{ __('Author:') }}</strong> <span x-text="previewData?.module_info?.author"></span></p>
                                        </div>
                                    </div>

                                    <!-- Composer Dependencies -->
                                    <div x-show="previewData?.composer_info?.require" class="rounded-lg p-4" style="background-color: var(--theme-primary-50)">
                                        <h4 class="font-semibold text-neutral-900 mb-2">{{ __('Dependencies') }}</h4>
                                        <div class="text-sm space-y-1">
                                            <template x-for="(version, package) in previewData?.composer_info?.require" :key="package">
                                                <p class="text-neutral-700">
                                                    <code class="text-xs bg-white px-2 py-1 rounded" x-text="package"></code>
                                                    <span class="text-neutral-500" x-text="': ' + version"></span>
                                                </p>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- PHP Version Requirements -->
                                    <div x-show="previewData?.composer_info?.require?.php" 
                                         class="border rounded-lg p-4"
                                         :style="previewData?.php_version_compatible ? 'background-color: var(--theme-status-success-bg); border-color: var(--theme-status-success-bg)' : 'background-color: var(--theme-status-warning-bg); border-color: var(--theme-status-warning-bg)'">
                                        <h4 class="font-semibold mb-2" 
                                            :style="previewData?.php_version_compatible ? 'color: var(--theme-status-success-text)' : 'color: var(--theme-status-warning-text)'">
                                            {{ __('PHP Version') }}
                                        </h4>
                                        <div class="text-sm space-y-1">
                                            <p :style="previewData?.php_version_compatible ? 'color: var(--theme-status-success-text)' : 'color: var(--theme-status-warning-text)'">
                                                <strong>{{ __('Required:') }}</strong> 
                                                <span x-text="previewData?.composer_info?.require?.php"></span>
                                            </p>
                                            <p :style="previewData?.php_version_compatible ? 'color: var(--theme-status-success-text)' : 'color: var(--theme-status-warning-text)'">
                                                <strong>{{ __('Current:') }}</strong> 
                                                <span x-text="previewData?.current_php_version || '{{ PHP_VERSION }}'"></span>
                                            </p>
                                            <p x-show="!previewData?.php_version_compatible" class="text-warning-800 mt-2">
                                                <svg class="inline w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                                {{ __('Warning: PHP version may not be compatible') }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- README -->
                                    <div x-show="previewData?.readme" class="bg-white border rounded-lg p-4 max-h-96 overflow-y-auto">
                                        <h4 class="font-semibold text-neutral-900 mb-2">{{ __('README') }}</h4>
                                        <div class="prose prose-sm max-w-none text-left" x-html="previewData?.readme ? previewData.readme.replace(/\n/g, '<br>') : ''"></div>
                                    </div>

                                    <!-- No data available -->
                                    <div x-show="!previewData?.module_info && !previewData?.readme && !previewData?.composer_info" class="text-center text-neutral-500 py-8">
                                        <p>{{ __('No preview data available') }}</p>
                                    </div>
                                </div>

                                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                    <button type="button"
                                            @click="showPreviewModal = false"
                                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-neutral-800 text-base font-medium text-white hover:bg-neutral-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-neutral-500 sm:ml-3 sm:w-auto sm:text-sm">
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
                        // Step 1: Initiate installation and get session ID
                        const initiateResponse = await fetch('{{ route('modules.install.initiate') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                url: this.repoUrl,
                                token: this.accessToken || '',
                                branch: this.selectedBranch || '',
                                commit: this.selectedCommit || ''
                            })
                        });

                        const initiateData = await initiateResponse.json();
                        
                        if (!initiateResponse.ok || initiateData.error) {
                            throw new Error(initiateData.message || '{{ __('Failed to initiate installation') }}');
                        }

                        // Step 2: Connect to SSE stream with session ID
                        const url = new URL('{{ route('modules.install.stream') }}');
                        url.searchParams.set('session_id', initiateData.session_id);

                        const eventSource = new EventSource(url);

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
