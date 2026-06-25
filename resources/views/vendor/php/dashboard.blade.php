@php
$info = app(\App\Actions\Php\PhpInfo::class);
$php = $info->all();
$error = null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('PHP Dashboard') }}
            </h2>
            <a href="{{ route('php.settings') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
                {{ __('Settings') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">PHP Version</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $php['version']['version'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">SAPI</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $php['version']['sapi'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/30">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">OPcache</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $php['opcache']['enabled'] ? 'Enabled' : 'Disabled' }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-900/30">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Extensions</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $php['extensions']['total'] }} loaded</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full {{ $php['fpm']['installed'] ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }}">
                            <svg class="w-6 h-6 {{ $php['fpm']['installed'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">PHP-FPM</p>
                            <p class="text-lg font-semibold {{ $php['fpm']['installed'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $php['fpm']['installed'] ? 'Installed' : 'Not Installed' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">PHP Environment</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between py-1">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Version</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $php['version']['version'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">SAPI</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $php['version']['sapi'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Architecture</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $php['version']['arch'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Operating System</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $php['version']['os'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Build Date</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $php['version']['build_date'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Zend Engine</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $php['version']['zend_version'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">PHP Ini Path</dt>
                            <dd class="text-sm font-mono text-gray-800 dark:text-gray-200">{{ $php['version']['ini_path'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Ini Scanned Dir</dt>
                            <dd class="text-sm font-mono text-gray-800 dark:text-gray-200">{{ $php['version']['ini_scanned_path'] }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Key Configuration</h3>
                    <dl class="space-y-2">
                        @foreach ($php['ini']['Core'] as $key => $setting)
                        <div class="flex justify-between py-1 {{ !$loop->first ? 'border-t border-gray-100 dark:border-gray-700' : '' }}">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $key }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $setting['local'] }}</dd>
                        </div>
                        @endforeach
                    </dl>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">OPcache</h3>
                    @if ($php['opcache']['enabled'])
                    @php
                    $totalMemory = ($php['opcache']['memory_usage']['used'] + $php['opcache']['memory_usage']['free'] + $php['opcache']['memory_usage']['wasted']);
                    $usedPct = $totalMemory > 0 ? round(($php['opcache']['memory_usage']['used'] / $totalMemory) * 100, 1) : 0;
                    $hitRate = $php['opcache']['stats']['hit_rate'] ?? 0;
                    @endphp
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-500 dark:text-gray-400">Memory Usage</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">
                                {{ $php['opcache']['memory_usage']['used'] > 0 ? round($php['opcache']['memory_usage']['used'] / 1048576, 2) : 0 }} MB
                                / {{ $totalMemory > 0 ? round($totalMemory / 1048576, 2) : 0 }} MB
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div class="bg-blue-500 h-4 rounded-full transition-all" style="width: {{ min($usedPct, 100) }}%"></div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-500 dark:text-gray-400">Hit Rate</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ number_format($hitRate, 1) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div class="bg-green-500 h-4 rounded-full transition-all" style="width: {{ min($hitRate, 100) }}%"></div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ number_format($php['opcache']['stats']['num_cached_scripts']) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Cached Scripts</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ number_format($php['opcache']['stats']['hits']) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Cache Hits</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xl font-bold text-red-600 dark:text-red-400">{{ number_format($php['opcache']['stats']['misses']) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Cache Misses</p>
                        </div>
                        <div class="text-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ $php['opcache']['jit'] ?: 'Off' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">JIT</p>
                        </div>
                    </div>
                    @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">{{ $php['opcache']['message'] ?? 'OPcache is not enabled' }}</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Xdebug</h3>
                    @if ($php['xdebug']['enabled'])
                    <dl class="space-y-2">
                        @foreach ([
                        'version' => 'Version',
                        'mode' => 'Mode',
                        'client_host' => 'Client Host',
                        'client_port' => 'Client Port',
                        'max_nesting_level' => 'Max Nesting Level',
                        'start_with_request' => 'Start With Request',
                        'idekey' => 'IDE Key',
                        ] as $key => $label)
                        <div class="flex justify-between py-1 {{ !$loop->first ? 'border-t border-gray-100 dark:border-gray-700' : '' }}">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $php['xdebug'][$key] ?? 'N/A' }}</dd>
                        </div>
                        @endforeach
                    </dl>
                    @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">Xdebug is not loaded</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Loaded Extensions</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ $php['extensions']['total'] }} extensions loaded</p>
                    <div class="space-y-3">
                        @foreach ($php['extensions']['categorized'] as $category => $exts)
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-1">{{ $category }} ({{ count($exts) }})</h4>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($exts as $ext)
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                    {{ $ext }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">PHP-FPM</h3>
                    @if ($php['fpm']['installed'])
                    <dl class="space-y-2">
                        <div class="flex justify-between py-1">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Binary</dt>
                            <dd class="text-sm font-mono text-gray-800 dark:text-gray-200">{{ $php['fpm']['binary'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Version</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $php['fpm']['version'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Config Test</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                <span class="{{ $php['fpm']['config_ok'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $php['fpm']['config_ok'] ? 'Passed' : 'Failed' }}
                                </span>
                            </dd>
                        </div>
                        @if (!empty($php['fpm']['pools']))
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Pools</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ count($php['fpm']['pools']) }}</dd>
                        </div>
                        <div class="mt-2 space-y-1">
                            @foreach ($php['fpm']['pools'] as $pool)
                            <div class="text-xs font-mono text-gray-600 dark:text-gray-400">{{ $pool['file'] }}</div>
                            @endforeach
                        </div>
                        @endif
                    </dl>
                    @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic mb-3">PHP-FPM is not installed on this system.</p>
                    <a href="{{ route('php.settings') }}" class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                        Install PHP-FPM
                    </a>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Error Log</h3>
                    @if ($php['error_log']['available'] && !empty($php['error_log']['entries']))
                    <p class="text-xs text-gray-400 dark:text-gray-500 mb-2 font-mono">{{ $php['error_log']['path'] }}</p>
                    <div class="space-y-1 max-h-64 overflow-y-auto">
                        @foreach ($php['error_log']['entries'] as $logLine)
                        <div class="text-xs font-mono text-gray-600 dark:text-gray-400 truncate hover:text-clip" title="{{ $logLine }}">
                            {{ $logLine }}
                        </div>
                        @endforeach
                    </div>
                    @else
                    @if ($php['error_log']['available'])
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">No error log entries available.</p>
                    @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">Error log path: {{ $php['error_log']['path'] }}</p>
                    @endif
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Swoole / Octane</h3>
                    @if ($php['swoole']['enabled'])
                    <dl class="space-y-2">
                        <div class="flex justify-between py-1">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Swoole Version</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $php['swoole']['version'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Octane Server</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $php['swoole']['server'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Workers</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $php['swoole']['workers'] ?? 'N/A' }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Max Requests</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $php['swoole']['max_requests'] ?? 'N/A' }}</dd>
                        </div>
                    </dl>
                    @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">Swoole is not loaded</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>