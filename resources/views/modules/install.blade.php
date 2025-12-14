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
                        <div class="flex items-center space-x-3">
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
                                    <span x-text="installing ? 'Installing Module...' : 'Install Module'"></span>
                                </span>
                            </button>
                        </div>
                    </form>
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

                    try {
                        const formData = new FormData();
                        formData.append('github_url', this.repoUrl);
                        if (this.accessToken) formData.append('github_token', this.accessToken);
                        if (this.selectedBranch) formData.append('github_branch', this.selectedBranch);
                        if (this.selectedCommit) formData.append('github_commit', this.selectedCommit);

                        const response = await fetch('{{ route('modules.install') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            throw new Error(data.message || 'Installation failed');
                        }

                        // Success - redirect to modules page
                        window.location.href = '{{ route('modules') }}';
                    } catch (err) {
                        this.installError = err.message;
                        console.error('Installation error:', err);
                    } finally {
                        this.installing = false;
                    }
                }
            };
        }
    </script>
    @endpush
</x-app-layout>
