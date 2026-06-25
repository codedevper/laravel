@php
    $tab = request('tab', 'general');
    $result = session('node_result');
    $error = session('node_error');

    $nodeVersion = trim(@shell_exec('node --version 2>/dev/null') ?: 'N/A');
    $npmVersion = trim(@shell_exec('npm --version 2>/dev/null') ?: 'N/A');

    $nvmDir = trim(@shell_exec('echo $NVM_DIR 2>/dev/null') ?: '');
    if (empty($nvmDir)) {
        $nvmDir = getenv('NVM_DIR') ?: '/home/master/.nvm';
    }
    $nvmDir = rtrim($nvmDir, '/');

    $nvmVersions = [];
    try {
        $nvmRaw = @shell_exec("ls {$nvmDir}/versions/node/ 2>/dev/null");
        if ($nvmRaw) {
            $nvmVersions = array_filter(explode("\n", trim($nvmRaw)));
        }
    } catch (\Exception $e) {
        $nvmVersions = [];
    }

    $globalPackages = [];
    try {
        $globalRaw = @shell_exec('npm list -g --depth=0 2>/dev/null');
        if ($globalRaw) {
            $lines = explode("\n", trim($globalRaw));
            foreach ($lines as $i => $line) {
                if ($i === 0) continue;
                $line = trim($line);
                if (empty($line)) continue;
                if (preg_match('/^[├└]──\s+(.+)$/', $line, $m)) {
                    $globalPackages[] = $m[1];
                }
            }
        }
    } catch (\Exception $e) {
        $globalPackages = [];
    }

    $pm2Installed = false;
    $pm2Version = null;
    $pm2Processes = [];
    $pm2Online = 0;
    $pm2Total = 0;
    try {
        $pm2Path = trim(@shell_exec('which pm2 2>/dev/null') ?: '');
        $pm2Installed = ! empty($pm2Path);

        if ($pm2Installed) {
            $pm2Version = trim(@shell_exec('pm2 --version 2>/dev/null') ?: '');
            $pm2Raw = @shell_exec('pm2 jlist 2>/dev/null');
            if ($pm2Raw) {
                $decoded = json_decode($pm2Raw, true);
                if (is_array($decoded)) {
                    $pm2Processes = $decoded;
                    $pm2Total = count($pm2Processes);
                    $pm2Online = count(array_filter($pm2Processes, fn($p) => ($p['pm2_env']['status'] ?? '') === 'online'));
                }
            }
        }
    } catch (\Exception $e) {
        $pm2Processes = [];
    }

    $formatBytes = function ($bytes) {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Node.js Settings') }}
            </h2>
            <a href="{{ route('node.dashboard') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
                {{ __('Back to Dashboard') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8 overflow-x-auto">
                    <a href="{{ route('node.settings', ['tab' => 'general']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'general' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        General
                    </a>
                    <a href="{{ route('node.settings', ['tab' => 'version']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'version' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Version
                    </a>
                    <a href="{{ route('node.settings', ['tab' => 'global-packages']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'global-packages' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Global Packages
                    </a>
                    <a href="{{ route('node.settings', ['tab' => 'pm2']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'pm2' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        PM2
                    </a>
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
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <span class="text-red-700 dark:text-red-300 text-sm">{{ $error }}</span>
                    </div>
                </div>
            @endif

            @if ($tab === 'general')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">General Settings</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">Configure npm registry, environment, and other Node.js global settings.</p>

                    <form method="POST" action="{{ route('node.settings', ['tab' => 'general']) }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="registry" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">npm Registry URL</label>
                            <input type="text" id="registry" name="registry" value="{{ old('registry', trim(@shell_exec('npm config get registry 2>/dev/null') ?: 'https://registry.npmjs.org/')) }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono">
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Set a custom npm registry mirror (e.g., https://registry.npmmirror.com).</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="prefix" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Global Install Prefix</label>
                                <input type="text" id="prefix" name="prefix" value="{{ old('prefix', trim(@shell_exec('npm config get prefix 2>/dev/null') ?: '')) }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono">
                            </div>
                            <div>
                                <label for="cache" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">npm Cache Path</label>
                                <input type="text" id="cache" name="cache" value="{{ old('cache', trim(@shell_exec('npm config get cache 2>/dev/null') ?: '')) }}" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono">
                            </div>
                        </div>

                        <div>
                            <label for="node_env" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">NODE_ENV</label>
                            <select id="node_env" name="node_env" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                @php $currentEnv = trim(@shell_exec('node -e "console.log(process.env.NODE_ENV || \'not set\')" 2>/dev/null') ?: 'not set'); @endphp
                                <option value="" {{ $currentEnv === 'not set' ? 'selected' : '' }}>Not set</option>
                                <option value="development" {{ $currentEnv === 'development' ? 'selected' : '' }}>development</option>
                                <option value="production" {{ $currentEnv === 'production' ? 'selected' : '' }}>production</option>
                                <option value="test" {{ $currentEnv === 'test' ? 'selected' : '' }}>test</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
                            <p class="text-xs text-gray-400 dark:text-gray-500">Current Node.js: <span class="font-mono">{{ $nodeVersion }}</span> &middot; npm: <span class="font-mono">{{ $npmVersion }}</span></p>
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                Save Settings
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            @if ($tab === 'version')
                <div class="grid grid-cols-1 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Installed Versions</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Currently installed Node.js versions via NVM.</p>

                        @if (!empty($nvmVersions))
                            <div class="space-y-3">
                                @foreach ($nvmVersions as $version)
                                    @php $isCurrent = str_contains($nodeVersion, $version) || $nodeVersion === $version; @endphp
                                    <div class="flex items-center justify-between py-3 px-4 rounded-lg {{ $isCurrent ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-700' }}">
                                        <div class="flex items-center space-x-3">
                                            <span class="w-3 h-3 rounded-full {{ $isCurrent ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $version }}</span>
                                            @if ($isCurrent)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">Active</span>
                                            @endif
                                        </div>
                                        @if (!$isCurrent)
                                            <form method="POST" action="{{ route('node.settings', ['tab' => 'version']) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="action" value="use-version">
                                                <input type="hidden" name="version" value="{{ $version }}">
                                                <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md transition">
                                                    Switch
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">No NVM installed versions found.</p>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Install New Version</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Install a new Node.js version using NVM.</p>

                        <form method="POST" action="{{ route('node.settings', ['tab' => 'version']) }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="action" value="install-version">

                            <div class="flex items-end gap-4">
                                <div class="flex-1">
                                    <label for="install_version" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Version</label>
                                    <div class="flex gap-2">
                                        <input type="text" id="install_version" name="version" placeholder="e.g., 20.11.0 or lts/iron" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition whitespace-nowrap">
                                            Install
                                        </button>
                                    </div>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Use any NVM version format: <code class="text-xs font-mono">18</code>, <code class="text-xs font-mono">20.11.0</code>, <code class="text-xs font-mono">lts/iron</code>, <code class="text-xs font-mono">node</code> (latest).</p>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            @if ($tab === 'global-packages')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Installed Global Packages</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Packages installed with <code class="text-xs font-mono">npm install -g</code>.</p>

                        @if (!empty($globalPackages))
                            <div class="space-y-2">
                                @foreach ($globalPackages as $pkg)
                                    @php
                                        $parts = explode('@', $pkg);
                                        $name = $parts[0] ?? $pkg;
                                        $ver = $parts[1] ?? '';
                                    @endphp
                                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                        <div class="flex items-center space-x-2 min-w-0">
                                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                            </svg>
                                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate">{{ $name }}</span>
                                            @if ($ver)
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $ver }}</span>
                                            @endif
                                        </div>
                                        <form method="POST" action="{{ route('node.settings', ['tab' => 'global-packages']) }}" class="inline shrink-0 ml-2">
                                            @csrf
                                            <input type="hidden" name="action" value="remove-package">
                                            <input type="hidden" name="package" value="{{ $name }}">
                                            <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 text-xs font-medium hover:underline" onclick="return confirm('Remove {{ $name }} from global packages?')">Remove</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">No global packages installed.</p>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Install Global Package</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Install a package globally via npm.</p>

                        <form method="POST" action="{{ route('node.settings', ['tab' => 'global-packages']) }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="action" value="install-package">

                            <div>
                                <label for="package_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Package Name</label>
                                <input type="text" id="package_name" name="package" placeholder="e.g., pm2, yarn, nodemon" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            </div>

                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                Install
                            </button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($tab === 'pm2')
                <div class="grid grid-cols-1 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-1">PM2 Process Manager</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">Manage your Node.js application processes.</p>
                            </div>
                            @if ($pm2Installed)
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">v{{ $pm2Version }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $pm2Online > 0 ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                        {{ $pm2Online }} / {{ $pm2Total }} online
                                    </span>
                                    <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="action" value="pm2-ping">
                                        <button type="submit" class="inline-flex items-center px-2 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-xs font-medium rounded-md transition">Ping</button>
                                    </form>
                                </div>
                            @endif
                        </div>

                        @if (!$pm2Installed)
                            <div class="p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg mb-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 text-yellow-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                        </svg>
                                        <span class="text-yellow-700 dark:text-yellow-300 text-sm font-medium">PM2 is not installed</span>
                                    </div>
                                    <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="action" value="pm2-install">
                                        <button type="submit" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md transition">Install PM2</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <div class="overflow-x-auto mb-4">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <th class="text-left py-1.5 pr-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ID</th>
                                            <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                                            <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                            <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Mode</th>
                                            <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">CPU</th>
                                            <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Memory</th>
                                            <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Restarts</th>
                                            <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Uptime</th>
                                            <th class="text-right py-1.5 pl-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($pm2Processes as $proc)
                                            @php
                                                $status = $proc['pm2_env']['status'] ?? 'unknown';
                                                $uptimeTs = $proc['pm2_env']['pm_uptime'] ?? null;
                                                $uptime = 'N/A';
                                                if ($uptimeTs && $status === 'online') {
                                                    $diff = time() - ($uptimeTs / 1000);
                                                    $days = floor($diff / 86400);
                                                    $hours = floor(($diff % 86400) / 3600);
                                                    $mins = floor(($diff % 3600) / 60);
                                                    $uptime = $days > 0 ? "{$days}d {$hours}h" : "{$hours}h {$mins}m";
                                                } elseif ($status !== 'online') {
                                                    $uptime = '-';
                                                }
                                            @endphp
                                            <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                <td class="py-1.5 pr-2 text-xs font-mono text-gray-500 dark:text-gray-400">{{ $proc['pm_id'] ?? '?' }}</td>
                                                <td class="py-1.5 px-2 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $proc['name'] ?? 'N/A' }}</td>
                                                <td class="py-1.5 px-2">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $status === 'online' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : ($status === 'stopped' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300') }}">
                                                        {{ $status }}
                                                    </span>
                                                </td>
                                                <td class="py-1.5 px-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $proc['pm2_env']['exec_mode'] ?? 'N/A' }}</td>
                                                <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ number_format($proc['monit']['cpu'] ?? 0, 1) }}%</td>
                                                <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ isset($proc['monit']['memory']) ? $formatBytes($proc['monit']['memory']) : 'N/A' }}</td>
                                                <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $proc['pm2_env']['restart_time'] ?? 0 }}</td>
                                                <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $uptime }}</td>
                                                <td class="py-1.5 pl-2 text-right">
                                                    <div class="flex items-center justify-end space-x-1">
                                                        @php $pmName = $proc['name'] ?? ''; @endphp
                                                        @if ($status === 'online')
                                                            <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="action" value="pm2-stop">
                                                                <input type="hidden" name="pm_name" value="{{ $pmName }}">
                                                                <button type="submit" class="inline-flex items-center px-1.5 py-1 text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400 hover:underline">Stop</button>
                                                            </form>
                                                            <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="action" value="pm2-restart">
                                                                <input type="hidden" name="pm_name" value="{{ $pmName }}">
                                                                <button type="submit" class="inline-flex items-center px-1.5 py-1 text-xs font-medium text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 hover:underline">Restart</button>
                                                            </form>
                                                            <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="action" value="pm2-reload">
                                                                <input type="hidden" name="pm_name" value="{{ $pmName }}">
                                                                <button type="submit" class="inline-flex items-center px-1.5 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 hover:underline">Reload</button>
                                                            </form>
                                                        @else
                                                            <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline">
                                                                @csrf
                                                                <input type="hidden" name="action" value="pm2-start">
                                                                <input type="hidden" name="pm_name" value="{{ $pmName }}">
                                                                <button type="submit" class="inline-flex items-center px-1.5 py-1 text-xs font-medium text-green-600 hover:text-green-800 dark:text-green-400 hover:underline">Start</button>
                                                            </form>
                                                        @endif
                                                        <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline">
                                                            @csrf
                                                            <input type="hidden" name="action" value="pm2-delete">
                                                            <input type="hidden" name="pm_name" value="{{ $pmName }}">
                                                            <button type="submit" class="inline-flex items-center px-1.5 py-1 text-xs font-medium text-red-700 hover:text-red-900 dark:text-red-500 hover:underline" onclick="return confirm('Delete process {{ $pmName }}?')">Delete</button>
                                                        </form>
                                                        <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline">
                                                            @csrf
                                                            <input type="hidden" name="action" value="pm2-logs">
                                                            <input type="hidden" name="pm_name" value="{{ $pmName }}">
                                                            <button type="submit" class="inline-flex items-center px-1.5 py-1 text-xs font-medium text-gray-600 hover:text-gray-800 dark:text-gray-400 hover:underline">Logs</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="py-6 text-center text-sm text-gray-400 dark:text-gray-500 italic">No PM2 processes running.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    @if ($pm2Installed)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Start New Process</h3>
                            <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="space-y-4">
                                @csrf
                                <input type="hidden" name="action" value="pm2-start">

                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label for="pm_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">App Name</label>
                                        <input type="text" id="pm_name" name="pm_name" placeholder="my-app" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                    <div>
                                        <label for="pm_script" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Script / Config Path</label>
                                        <input type="text" id="pm_script" name="pm_script" placeholder="/path/to/app.js or ecosystem.config.js" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                    </div>
                                    <div>
                                        <label for="pm_args" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Arguments</label>
                                        <input type="text" id="pm_args" name="pm_args" placeholder="--port=3000" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>
                                </div>

                                <div class="flex items-center space-x-4">
                                    <label class="flex items-center space-x-2 text-sm text-gray-700 dark:text-gray-300">
                                        <input type="checkbox" name="pm_watch" value="1" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                        <span>Watch for changes</span>
                                    </label>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                        Start
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">PM2 Utilities</h3>
                                <div class="grid grid-cols-2 gap-3">
                                    <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="pm2-save">
                                        <button type="submit" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition text-center">
                                            Save Process List
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="pm2-startup">
                                        <button type="submit" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition text-center">
                                            Setup Startup
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}">
                                        @csrf
                                        <input type="hidden" name="action" value="pm2-resurrect">
                                        <button type="submit" class="w-full px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition text-center">
                                            Resurrect Processes
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" onsubmit="return confirm('Kill the PM2 daemon?')">
                                        @csrf
                                        <input type="hidden" name="action" value="pm2-kill">
                                        <button type="submit" class="w-full px-3 py-2 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 text-red-700 dark:text-red-400 text-sm font-medium rounded-md transition text-center">
                                            Kill Daemon
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Quick Logs</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">View the last 50 lines of logs for any process.</p>
                                <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="space-y-3">
                                    @csrf
                                    <input type="hidden" name="action" value="pm2-logs">
                                    <div class="flex gap-2">
                                        <select name="pm_name" class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                            <option value="">All processes</option>
                                            @foreach ($pm2Processes as $proc)
                                                <option value="{{ $proc['name'] ?? '' }}">{{ $proc['name'] ?? 'N/A' }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">View</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
