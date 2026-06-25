@php
    $error = null;
    $nodeVersion = 'N/A';
    $npmVersion = 'N/A';
    $nodePath = 'N/A';
    $versions = [];
    $globalPackages = [];
    $npmConfig = [];
    $nvmVersions = [];
    $corepackVersion = 'N/A';
    $yarnVersion = null;
    $pnpmVersion = null;
    $pm2Processes = [];
    $pm2Installed = false;
    $pm2Version = null;
    $pm2Online = 0;
    $pm2Total = 0;

    try {
        $nodeVersion = trim(@shell_exec('node --version 2>/dev/null') ?: 'N/A');
    } catch (\Exception $e) {
        $error = 'Node.js is not installed or not accessible.';
    }

    if ($nodeVersion !== 'N/A') {
        try {
            $npmVersion = trim(@shell_exec('npm --version 2>/dev/null') ?: 'N/A');
        } catch (\Exception $e) {
            $npmVersion = 'N/A';
        }

        try {
            $nodePath = trim(@shell_exec('which node 2>/dev/null') ?: 'N/A');
        } catch (\Exception $e) {
            $nodePath = 'N/A';
        }

        try {
            $versionsJson = @shell_exec('node -e "console.log(JSON.stringify(process.versions))" 2>/dev/null');
            if ($versionsJson) {
                $versions = json_decode($versionsJson, true) ?? [];
            }
        } catch (\Exception $e) {
            $versions = [];
        }

        try {
            $globalRaw = @shell_exec('npm list -g --depth=0 2>/dev/null');
            if ($globalRaw) {
                $lines = explode("\n", trim($globalRaw));
                foreach ($lines as $i => $line) {
                    if ($i === 0) continue;
                    $line = trim($line);
                    if (empty($line)) continue;
                    if (preg_match('/^[├└]──\s+(.+@.+)$/', $line, $m)) {
                        $globalPackages[] = $m[1];
                    } elseif (preg_match('/^[├└]──\s+(.+)$/', $line, $m)) {
                        $globalPackages[] = $m[1];
                    }
                }
            }
        } catch (\Exception $e) {
            $globalPackages = [];
        }

        try {
            $configRaw = @shell_exec('npm config list 2>/dev/null');
            if ($configRaw) {
                foreach (explode("\n", trim($configRaw)) as $line) {
                    if (str_contains($line, '=') && !str_starts_with($line, ';')) {
                        [$key, $value] = explode('=', $line, 2);
                        $npmConfig[trim($key)] = trim($value);
                    }
                }
            }
        } catch (\Exception $e) {
            $npmConfig = [];
        }

        try {
            $nvmDir = trim(@shell_exec('echo $NVM_DIR 2>/dev/null') ?: '');
            if (empty($nvmDir)) {
                $nvmDir = getenv('NVM_DIR') ?: '/home/master/.nvm';
            }
            $nvmDir = rtrim($nvmDir, '/');
            $nvmRaw = @shell_exec("ls {$nvmDir}/versions/node/ 2>/dev/null");
            if ($nvmRaw) {
                $nvmVersions = array_filter(explode("\n", trim($nvmRaw)));
            }
        } catch (\Exception $e) {
            $nvmVersions = [];
        }

        try {
            $cp = trim(@shell_exec('corepack --version 2>/dev/null') ?: '');
            $corepackVersion = $cp ?: 'N/A';
        } catch (\Exception $e) {
            $corepackVersion = 'N/A';
        }

        try {
            $yr = trim(@shell_exec('yarn --version 2>/dev/null') ?: '');
            $yarnVersion = $yr ?: null;
        } catch (\Exception $e) {
            $yarnVersion = null;
        }

        try {
            $pp = trim(@shell_exec('pnpm --version 2>/dev/null') ?: '');
            $pnpmVersion = $pp ?: null;
        } catch (\Exception $e) {
            $pnpmVersion = null;
        }

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
                {{ __('Node.js Dashboard') }}
            </h2>
            <a href="{{ route('node.settings') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
                {{ __('Settings') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

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

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Node.js</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $nodeVersion }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">npm</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $npmVersion }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/30">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Global Packages</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ count($globalPackages) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full {{ $pm2Installed ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-700' }}">
                            <svg class="w-6 h-6 {{ $pm2Installed ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">PM2 Apps</p>
                            <p class="text-lg font-semibold {{ $pm2Installed ? 'text-gray-800 dark:text-gray-200' : 'text-gray-400 dark:text-gray-500' }}">
                                {{ $pm2Installed ? "{$pm2Online} / {$pm2Total}" : 'Not Installed' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Node.js Environment</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between py-1">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Version</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $nodeVersion }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Exec Path</dt>
                            <dd class="text-sm font-mono text-gray-800 dark:text-gray-200">{{ $nodePath }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Architecture</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $versions['arch'] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Platform</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $versions['platform'] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">V8</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $versions['v8'] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">npm</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $npmVersion }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Corepack</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $corepackVersion }}</dd>
                        </div>
                        @if ($yarnVersion)
                            <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Yarn</dt>
                                <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $yarnVersion }}</dd>
                            </div>
                        @endif
                        @if ($pnpmVersion)
                            <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">pnpm</dt>
                                <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $pnpmVersion }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">npm Configuration</h3>
                    @if (!empty($npmConfig))
                        <dl class="space-y-2">
                            @foreach ($npmConfig as $key => $value)
                                <div class="flex justify-between py-1 {{ !$loop->first ? 'border-t border-gray-100 dark:border-gray-700' : '' }}">
                                    <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $key }}</dt>
                                    <dd class="text-sm font-mono text-gray-800 dark:text-gray-200 truncate max-w-[60%]" title="{{ $value }}">{{ $value }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No npm configuration available.</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Node.js Internal Versions</h3>
                    @if (!empty($versions))
                        <dl class="space-y-2">
                            @foreach (['v8' => 'V8', 'uv' => 'libuv', 'zlib' => 'zlib', 'openssl' => 'OpenSSL', 'ares' => 'c-ares', 'nghttp2' => 'nghttp2', 'brotli' => 'Brotli', 'cldr' => 'CLDR', 'icu' => 'ICU', 'unicode' => 'Unicode', 'modules' => 'ABI Modules'] as $key => $label)
                                @if (isset($versions[$key]))
                                    <div class="flex justify-between py-1 {{ !$loop->first ? 'border-t border-gray-100 dark:border-gray-700' : '' }}">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                        <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $versions[$key] }}</dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No version data available.</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Global Packages</h3>
                    @if (!empty($globalPackages))
                        <div class="flex flex-wrap gap-1">
                            @foreach ($globalPackages as $pkg)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                    {{ $pkg }}
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No global packages installed.</p>
                    @endif
                </div>
            </div>

            @if ($pm2Installed)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">PM2 Process Manager</h3>
                        <div class="flex items-center space-x-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">v{{ $pm2Version }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $pm2Total > 0 ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ $pm2Online }} / {{ $pm2Total }} online
                            </span>
                            <a href="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline-flex items-center px-2.5 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-xs font-medium rounded-md transition">
                                Manage
                            </a>
                        </div>
                    </div>

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
                                    <th class="text-right py-1.5 pl-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Uptime</th>
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
                                                        <button type="submit" class="inline-flex items-center px-1.5 py-1 text-xs font-medium text-red-600 hover:text-red-800 dark:text-red-400 hover:underline" title="Stop">Stop</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="action" value="pm2-restart">
                                                        <input type="hidden" name="pm_name" value="{{ $pmName }}">
                                                        <button type="submit" class="inline-flex items-center px-1.5 py-1 text-xs font-medium text-yellow-600 hover:text-yellow-800 dark:text-yellow-400 hover:underline" title="Restart">Restart</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="action" value="pm2-reload">
                                                        <input type="hidden" name="pm_name" value="{{ $pmName }}">
                                                        <button type="submit" class="inline-flex items-center px-1.5 py-1 text-xs font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 hover:underline" title="Reload">Reload</button>
                                                    </form>
                                                @else
                                                    <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline">
                                                        @csrf
                                                        <input type="hidden" name="action" value="pm2-start">
                                                        <input type="hidden" name="pm_name" value="{{ $pmName }}">
                                                        <button type="submit" class="inline-flex items-center px-1.5 py-1 text-xs font-medium text-green-600 hover:text-green-800 dark:text-green-400 hover:underline" title="Start">Start</button>
                                                    </form>
                                                @endif
                                                <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="inline">
                                                    @csrf
                                                    <input type="hidden" name="action" value="pm2-delete">
                                                    <input type="hidden" name="pm_name" value="{{ $pmName }}">
                                                    <button type="submit" class="inline-flex items-center px-1.5 py-1 text-xs font-medium text-red-700 hover:text-red-900 dark:text-red-500 hover:underline" title="Delete" onclick="return confirm('Delete process {{ $pmName }}?')">Delete</button>
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

                    <div class="border-t border-gray-100 dark:border-gray-700 pt-4">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Start New Process</h4>
                        <form method="POST" action="{{ route('node.settings', ['tab' => 'pm2']) }}" class="flex flex-wrap items-end gap-3">
                            @csrf
                            <input type="hidden" name="action" value="pm2-start">
                            <div class="flex-1 min-w-[160px]">
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Name</label>
                                <input type="text" name="pm_name" placeholder="app-name" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="flex-[2] min-w-[240px]">
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Script Path</label>
                                <input type="text" name="pm_script" placeholder="/path/to/app.js or ecosystem.config.js" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="min-w-[120px]">
                                <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Args</label>
                                <input type="text" name="pm_args" placeholder="--port=3000" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="flex items-center space-x-2">
                                <label class="flex items-center space-x-1 text-xs text-gray-500 dark:text-gray-400">
                                    <input type="checkbox" name="pm_watch" value="1" class="rounded border-gray-300 dark:border-gray-600 text-blue-600 focus:ring-blue-500">
                                    <span>Watch</span>
                                </label>
                                <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md transition">Start</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">NVM Installed Versions</h3>
                    @if (!empty($nvmVersions))
                        <div class="flex flex-wrap gap-1">
                            @foreach ($nvmVersions as $v)
                                @php $isCurrent = str_contains($nodeVersion, $v) || $nodeVersion === $v; @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $isCurrent ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 ring-1 ring-green-300 dark:ring-green-700' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $v }}
                                    @if ($isCurrent)
                                        <svg class="w-3 h-3 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    @endif
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No NVM installed versions found or NVM is not being used.</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Package Managers</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between py-1">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">npm</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $npmVersion }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Corepack</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $corepackVersion }}</dd>
                        </div>
                        @if ($yarnVersion)
                            <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">Yarn</dt>
                                <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $yarnVersion }}</dd>
                            </div>
                        @endif
                        @if ($pnpmVersion)
                            <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">pnpm</dt>
                                <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $pnpmVersion }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
