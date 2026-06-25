@php
    use App\Models\WpSite;
    use App\Actions\Wp\WpCli;

    $wpCli = app(WpCli::class);
    $wpStatus = $wpCli->installed();
    $sites = WpSite::all();
    $tab = request('tab', 'wp-cli');
    $selectedSiteId = request('site');
    $selectedSite = $selectedSiteId ? WpSite::find($selectedSiteId) : $sites->first();
    $result = session('wp_result');
    $error = session('wp_error');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('WP Toolkits Management') }}
            </h2>
            <a href="{{ route('wp-toolkits.dashboard') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
                {{ __('Back to Dashboard') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8 overflow-x-auto">
                    <a href="{{ route('wp-toolkits.management', ['tab' => 'wp-cli']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'wp-cli' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        WP-CLI
                    </a>
                    <a href="{{ route('wp-toolkits.management', ['tab' => 'sites']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'sites' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Sites
                    </a>
                    @if ($selectedSite)
                    <a href="{{ route('wp-toolkits.management', ['tab' => 'core', 'site' => $selectedSite->id]) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'core' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Core
                    </a>
                    <a href="{{ route('wp-toolkits.management', ['tab' => 'plugins', 'site' => $selectedSite->id]) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'plugins' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Plugins
                    </a>
                    <a href="{{ route('wp-toolkits.management', ['tab' => 'themes', 'site' => $selectedSite->id]) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'themes' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Themes
                    </a>
                    <a href="{{ route('wp-toolkits.management', ['tab' => 'users', 'site' => $selectedSite->id]) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'users' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Users
                    </a>
                    <a href="{{ route('wp-toolkits.management', ['tab' => 'database', 'site' => $selectedSite->id]) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'database' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Database
                    </a>
                    @endif
                </nav>
            </div>

            @if ($result)
                <div class="mb-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Command Result</h4>
                    <pre class="text-xs font-mono text-gray-600 dark:text-gray-400 whitespace-pre-wrap max-h-96 overflow-y-auto">{{ $result }}</pre>
                </div>
            @endif

            @if ($error)
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <p class="text-sm text-red-700 dark:text-red-300">{{ $error }}</p>
                </div>
            @endif

            {{-- WP-CLI Tab --}}
            @if ($tab === 'wp-cli')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">WP-CLI Status</h3>
                        @if ($wpStatus['installed'])
                            <dl class="space-y-2">
                                <div class="flex justify-between py-1">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Version</dt>
                                    <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $wpStatus['version'] }}</dd>
                                </div>
                                <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">Binary Path</dt>
                                    <dd class="text-sm font-mono text-gray-800 dark:text-gray-200">{{ $wpStatus['path'] }}</dd>
                                </div>
                            </dl>
                            <div class="mt-4">
                                <form method="POST" action="{{ route('wp-toolkits.management') }}">
                                    @csrf
                                    <input type="hidden" name="tab" value="wp-cli">
                                    <input type="hidden" name="action" value="update-wp-cli">
                                    <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                        Update WP-CLI
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">WP-CLI is not installed on this system.</p>
                            <form method="POST" action="{{ route('wp-toolkits.management') }}">
                                @csrf
                                <input type="hidden" name="tab" value="wp-cli">
                                <input type="hidden" name="action" value="install-wp-cli">
                                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                    Install WP-CLI
                                </button>
                            </form>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Run WP-CLI Command</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Execute an arbitrary WP-CLI command. Select a site if needed.</p>
                        <form method="POST" action="{{ route('wp-toolkits.management') }}">
                            @csrf
                            <input type="hidden" name="tab" value="wp-cli">
                            <input type="hidden" name="action" value="run-command">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site</label>
                                    <select name="site_id" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                        <option value="">-- Without Site Context --</option>
                                        @foreach ($sites as $site)
                                            <option value="{{ $site->id }}">{{ $site->name }} ({{ $site->path }})</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Command</label>
                                    <input type="text" name="command" placeholder="e.g. plugin list --format=json" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200 font-mono">
                                </div>
                                <button type="submit" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition">
                                    Run Command
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            {{-- Sites Tab --}}
            @elseif ($tab === 'sites')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Add Site</h3>
                        <form method="POST" action="{{ route('wp-toolkits.management') }}">
                            @csrf
                            <input type="hidden" name="tab" value="sites">
                            <input type="hidden" name="action" value="add-site">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site Name</label>
                                    <input type="text" name="name" placeholder="My WordPress Site" required class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Site Path</label>
                                    <input type="text" name="path" placeholder="/var/www/mysite" required class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200 font-mono">
                                    <p class="text-xs text-gray-400 mt-1">Absolute path to the WordPress installation directory.</p>
                                </div>
                                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition">
                                    Add Site
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Registered Sites</h3>
                        @if ($sites->count() > 0)
                            <div class="space-y-2">
                                @foreach ($sites as $site)
                                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                        <div>
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $site->name }}</p>
                                            <p class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $site->path }}</p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('wp-toolkits.management', ['tab' => 'core', 'site' => $site->id]) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-medium">Manage</a>
                                            <form method="POST" action="{{ route('wp-toolkits.management') }}" onsubmit="return confirm('Remove this site?')">
                                                @csrf
                                                <input type="hidden" name="tab" value="sites">
                                                <input type="hidden" name="action" value="remove-site">
                                                <input type="hidden" name="site_id" value="{{ $site->id }}">
                                                <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 text-xs font-medium">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">No sites registered yet. Add one using the form.</p>
                        @endif
                    </div>
                </div>

            {{-- Core Tab --}}
            @elseif ($tab === 'core' && $selectedSite)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Core Management</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedSite->name }} ({{ $selectedSite->path }})</p>
                        </div>
                        <form method="POST" action="{{ route('wp-toolkits.management') }}">
                            @csrf
                            <input type="hidden" name="tab" value="core">
                            <input type="hidden" name="site_id" value="{{ $selectedSite->id }}">
                            <input type="hidden" name="action" value="update-core">
                            <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                Update Core
                            </button>
                        </form>
                    </div>

                    @if ($wpStatus['installed'])
                        @php $coreVersion = $wpCli->coreVersion($selectedSite->path); @endphp
                        @if ($coreVersion['success'])
                            <div class="p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    <span class="font-medium text-gray-800 dark:text-gray-200">Current Version:</span>
                                    <code class="ml-2 font-mono text-blue-600 dark:text-blue-400">{{ $coreVersion['output'] }}</code>
                                </p>
                            </div>
                        @else
                            <p class="text-sm text-red-500">{{ $coreVersion['output'] }}</p>
                        @endif
                    @else
                        <p class="text-sm text-gray-400 italic">WP-CLI is not installed.</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Site Switcher</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($sites as $site)
                            <a href="{{ route('wp-toolkits.management', ['tab' => 'core', 'site' => $site->id]) }}"
                               class="px-3 py-1.5 text-sm rounded-md transition
                                {{ $site->id === $selectedSite->id ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-300 dark:hover:bg-gray-600' }}">
                                {{ $site->name }}
                            </a>
                        @endforeach
                    </div>
                </div>

            {{-- Plugins Tab --}}
            @elseif ($tab === 'plugins' && $selectedSite && $wpStatus['installed'])
                @php $pluginsResult = $wpCli->pluginList($selectedSite->path); @endphp
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Plugins</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedSite->name }}</p>
                        </div>
                        @if ($pluginsResult['success'])
                            @php $plugins = json_decode($pluginsResult['output'], true) ?? []; @endphp
                            @if (!empty($plugins))
                                <div class="space-y-2">
                                    @foreach ($plugins as $plugin)
                                        @php $name = $plugin['name'] ?? 'Unknown'; $status = $plugin['status'] ?? 'unknown'; $update = $plugin['update'] ?? 'none'; @endphp
                                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <span class="w-2 h-2 rounded-full {{ $status === 'active' ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $name }}</span>
                                                @if ($update !== 'none')
                                                    <span class="text-xs px-1.5 py-0.5 rounded bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-300">Update</span>
                                                @endif
                                            </div>
                                            <div class="flex space-x-1">
                                                @if ($status === 'active')
                                                    <form method="POST" action="{{ route('wp-toolkits.management') }}">
                                                        @csrf
                                                        <input type="hidden" name="tab" value="plugins">
                                                        <input type="hidden" name="site_id" value="{{ $selectedSite->id }}">
                                                        <input type="hidden" name="action" value="plugin-deactivate">
                                                        <input type="hidden" name="slug" value="{{ $name }}">
                                                        <button type="submit" class="text-xs px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded hover:bg-red-200 dark:hover:bg-red-900/50">Deactivate</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('wp-toolkits.management') }}">
                                                        @csrf
                                                        <input type="hidden" name="tab" value="plugins">
                                                        <input type="hidden" name="site_id" value="{{ $selectedSite->id }}">
                                                        <input type="hidden" name="action" value="plugin-activate">
                                                        <input type="hidden" name="slug" value="{{ $name }}">
                                                        <button type="submit" class="text-xs px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded hover:bg-green-200 dark:hover:bg-green-900/50">Activate</button>
                                                    </form>
                                                @endif
                                                @if ($update !== 'none')
                                                    <form method="POST" action="{{ route('wp-toolkits.management') }}">
                                                        @csrf
                                                        <input type="hidden" name="tab" value="plugins">
                                                        <input type="hidden" name="site_id" value="{{ $selectedSite->id }}">
                                                        <input type="hidden" name="action" value="plugin-update">
                                                        <input type="hidden" name="slug" value="{{ $name }}">
                                                        <button type="submit" class="text-xs px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded hover:bg-blue-200 dark:hover:bg-blue-900/50">Update</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-400 italic">No plugins found.</p>
                            @endif
                        @else
                            <p class="text-sm text-red-500">{{ $pluginsResult['output'] }}</p>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Install Plugin</h3>
                        <form method="POST" action="{{ route('wp-toolkits.management') }}">
                            @csrf
                            <input type="hidden" name="tab" value="plugins">
                            <input type="hidden" name="site_id" value="{{ $selectedSite->id }}">
                            <input type="hidden" name="action" value="plugin-install">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Plugin Slug</label>
                                    <input type="text" name="slug" placeholder="akismet" required class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200 font-mono">
                                </div>
                                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition">
                                    Install Plugin
                                </button>
                            </div>
                        </form>

                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Bulk Actions</h4>
                            <form method="POST" action="{{ route('wp-toolkits.management') }}">
                                @csrf
                                <input type="hidden" name="tab" value="plugins">
                                <input type="hidden" name="site_id" value="{{ $selectedSite->id }}">
                                <input type="hidden" name="action" value="plugin-update-all">
                                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                    Update All Plugins
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            {{-- Themes Tab --}}
            @elseif ($tab === 'themes' && $selectedSite && $wpStatus['installed'])
                @php $themesResult = $wpCli->themeList($selectedSite->path); @endphp
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Themes</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedSite->name }}</p>
                    </div>
                    @if ($themesResult['success'])
                        @php $themes = json_decode($themesResult['output'], true) ?? []; @endphp
                        @if (!empty($themes))
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                @foreach ($themes as $theme)
                                    @php $name = $theme['name'] ?? 'Unknown'; $status = $theme['status'] ?? 'inactive'; @endphp
                                    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                        <div>
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $status === 'active' ? 'Active' : 'Inactive' }}
                                            </p>
                                        </div>
                                        @if ($status !== 'active')
                                            <form method="POST" action="{{ route('wp-toolkits.management') }}">
                                                @csrf
                                                <input type="hidden" name="tab" value="themes">
                                                <input type="hidden" name="site_id" value="{{ $selectedSite->id }}">
                                                <input type="hidden" name="action" value="theme-activate">
                                                <input type="hidden" name="slug" value="{{ $name }}">
                                                <button type="submit" class="px-3 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded-md transition">
                                                    Activate
                                                </button>
                                            </form>
                                        @else
                                            <span class="px-3 py-1 text-xs bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-md font-medium">Active</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 italic">No themes found.</p>
                        @endif
                    @else
                        <p class="text-sm text-red-500">{{ $themesResult['output'] }}</p>
                    @endif
                </div>

            {{-- Users Tab --}}
            @elseif ($tab === 'users' && $selectedSite && $wpStatus['installed'])
                @php $usersResult = $wpCli->userList($selectedSite->path); @endphp
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Users</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedSite->name }}</p>
                        </div>
                        @if ($usersResult['success'])
                            @php $users = json_decode($usersResult['output'], true) ?? []; @endphp
                            @if (!empty($users))
                                <div class="space-y-2">
                                    @foreach ($users as $user)
                                        <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-sm font-medium text-blue-600 dark:text-blue-400">
                                                    {{ strtoupper(substr($user['user_login'] ?? '?', 0, 1)) }}
                                                </div>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $user['display_name'] ?? $user['user_login'] ?? 'Unknown' }}</p>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user['user_email'] ?? '' }} · {{ $user['roles'] ?? '' }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-400 italic">No users found.</p>
                            @endif
                        @else
                            <p class="text-sm text-red-500">{{ $usersResult['output'] }}</p>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Create User</h3>
                        <form method="POST" action="{{ route('wp-toolkits.management') }}">
                            @csrf
                            <input type="hidden" name="tab" value="users">
                            <input type="hidden" name="site_id" value="{{ $selectedSite->id }}">
                            <input type="hidden" name="action" value="user-create">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Username</label>
                                    <input type="text" name="username" placeholder="johndoe" required class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                    <input type="email" name="email" placeholder="john@example.com" required class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Role</label>
                                    <select name="role" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                        <option value="subscriber">Subscriber</option>
                                        <option value="contributor">Contributor</option>
                                        <option value="author">Author</option>
                                        <option value="editor">Editor</option>
                                        <option value="administrator">Administrator</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Password (leave empty for auto-generated)</label>
                                    <input type="text" name="password" placeholder="Leave empty for random" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                </div>
                                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition">
                                    Create User
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            {{-- Database Tab --}}
            @elseif ($tab === 'database' && $selectedSite && $wpStatus['installed'])
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Database Operations</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $selectedSite->name }}</p>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Run database maintenance operations via WP-CLI.</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <form method="POST" action="{{ route('wp-toolkits.management') }}">
                            @csrf
                            <input type="hidden" name="tab" value="database">
                            <input type="hidden" name="site_id" value="{{ $selectedSite->id }}">
                            <input type="hidden" name="action" value="db-optimize">
                            <button type="submit" class="w-full px-4 py-3 bg-green-100 dark:bg-green-900/30 hover:bg-green-200 dark:hover:bg-green-900/50 text-green-700 dark:text-green-300 text-sm font-medium rounded-lg transition text-center">
                                <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                                Optimize Database
                            </button>
                        </form>
                        <form method="POST" action="{{ route('wp-toolkits.management') }}">
                            @csrf
                            <input type="hidden" name="tab" value="database">
                            <input type="hidden" name="site_id" value="{{ $selectedSite->id }}">
                            <input type="hidden" name="action" value="db-repair">
                            <button type="submit" class="w-full px-4 py-3 bg-yellow-100 dark:bg-yellow-900/30 hover:bg-yellow-200 dark:hover:bg-yellow-900/50 text-yellow-700 dark:text-yellow-300 text-sm font-medium rounded-lg transition text-center">
                                <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                </svg>
                                Repair Database
                            </button>
                        </form>
                        <form method="POST" action="{{ route('wp-toolkits.management') }}">
                            @csrf
                            <input type="hidden" name="tab" value="database">
                            <input type="hidden" name="site_id" value="{{ $selectedSite->id }}">
                            <input type="hidden" name="action" value="db-export">
                            <button type="submit" class="w-full px-4 py-3 bg-blue-100 dark:bg-blue-900/30 hover:bg-blue-200 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 text-sm font-medium rounded-lg transition text-center">
                                <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Export Database
                            </button>
                        </form>
                    </div>
                </div>

            @endif

        </div>
    </div>
</x-app-layout>
