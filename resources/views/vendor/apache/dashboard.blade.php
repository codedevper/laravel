@php
    $statusUrl = env('APACHE_STATUS_URL', 'http://localhost/server-status?auto');
    $errorLogPath = env('APACHE_ERROR_LOG', '/var/log/apache2/error.log');

    $serverInfo = [];
    $workers = [];
    $connections = [];
    $requests = [];
    $load = [];
    $vhosts = [];
    $errorLog = [];
    $error = null;

    try {
        $status = @file_get_contents($statusUrl);

        if ($status !== false) {
            $lines = explode("\n", $status);
            $parsed = [];
            foreach ($lines as $line) {
                if (str_contains($line, ':')) {
                    [$key, $value] = explode(':', $line, 2);
                    $parsed[trim($key)] = trim($value);
                }
            }

            $serverInfo = [
                'version' => $parsed['ServerVersion'] ?? 'N/A',
                'mpm' => $parsed['ServerMPM'] ?? 'N/A',
                'built' => $parsed['Server Built'] ?? 'N/A',
                'uptime' => $parsed['ServerUptime'] ?? 'N/A',
                'restart_time' => $parsed['RestartTime'] ?? 'N/A',
            ];

            $workers = [
                'total_accesses' => $parsed['Total Accesses'] ?? 0,
                'total_kbytes' => $parsed['Total kBytes'] ?? 0,
                'busy' => $parsed['BusyWorkers'] ?? 0,
                'idle' => $parsed['IdleWorkers'] ?? 0,
            ];

            $connections = [
                'total' => $parsed['ConnsTotal'] ?? 0,
                'writing' => $parsed['ConnsAsyncWriting'] ?? 0,
                'keepalive' => $parsed['ConnsAsyncKeepAlive'] ?? 0,
                'closing' => $parsed['ConnsAsyncClosing'] ?? 0,
            ];

            $requests = [
                'per_sec' => $parsed['ReqPerSec'] ?? 0,
                'bytes_per_sec' => $parsed['BytesPerSec'] ?? 0,
                'bytes_per_req' => $parsed['BytesPerReq'] ?? 0,
            ];

            $load = [
                '1' => $parsed['Load1'] ?? 'N/A',
                '5' => $parsed['Load5'] ?? 'N/A',
                '15' => $parsed['Load15'] ?? 'N/A',
                'cpu_load' => $parsed['CPULoad'] ?? 'N/A',
            ];

            $scoreboard = $parsed['Scoreboard'] ?? '';
            if ($scoreboard) {
                $connections['scoreboard'] = [
                    'waiting' => substr_count($scoreboard, '_'),
                    'starting' => substr_count($scoreboard, 'S'),
                    'reading' => substr_count($scoreboard, 'R'),
                    'sending' => substr_count($scoreboard, 'W'),
                    'keepalive' => substr_count($scoreboard, 'K'),
                    'dns' => substr_count($scoreboard, 'D'),
                    'closing' => substr_count($scoreboard, 'C'),
                    'logging' => substr_count($scoreboard, 'L'),
                    'finishing' => substr_count($scoreboard, 'G'),
                    'idle_cleanup' => substr_count($scoreboard, 'I'),
                    'open' => substr_count($scoreboard, '.'),
                ];
            }
        } else {
            $error = 'Unable to connect to Apache status endpoint. Ensure mod_status is enabled and accessible.';
        }
    } catch (\Exception $e) {
        $error = $e->getMessage();
    }

    try {
        $vhostsOutput = @shell_exec('apachectl -S 2>&1');
        if ($vhostsOutput) {
            $vhostLines = explode("\n", trim($vhostsOutput));
            foreach ($vhostLines as $line) {
                if (str_contains($line, 'namevhost') || str_contains($line, 'default server')) {
                    $vhosts[] = trim($line);
                }
            }
        }
    } catch (\Exception $e) {
        $vhosts = [];
    }

    try {
        $logOutput = @shell_exec("tail -n 15 " . escapeshellarg($errorLogPath) . " 2>&1");
        if ($logOutput) {
            $errorLog = array_reverse(array_filter(explode("\n", trim($logOutput))));
        }
    } catch (\Exception $e) {
        $errorLog = [];
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Apache Dashboard') }}
            </h2>
            <a href="{{ route('apache.settings') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
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
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Apache Version</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $serverInfo['version'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Uptime</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $serverInfo['uptime'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/30">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Busy Workers</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $workers['busy'] }} <span class="text-sm font-normal text-gray-500">/ {{ $workers['busy'] + $workers['idle'] }}</span></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-900/30">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Requests/sec</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ number_format((float) $requests['per_sec'], 2) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Server Information</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between py-1">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Version</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $serverInfo['version'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">MPM</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $serverInfo['mpm'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Built</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $serverInfo['built'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Uptime</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $serverInfo['uptime'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Restart Time</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $serverInfo['restart_time'] }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Total Accesses</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ number_format((int) $workers['total_accesses']) }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Total Data</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">
                                @php
                                    $kb = (int) $workers['total_kbytes'];
                                    if ($kb >= 1048576) {
                                        echo number_format($kb / 1048576, 2) . ' TB';
                                    } elseif ($kb >= 1024) {
                                        echo number_format($kb / 1024, 2) . ' GB';
                                    } else {
                                        echo number_format($kb) . ' KB';
                                    }
                                @endphp
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Worker Pool</h3>
                    @php $total = (int) $workers['busy'] + (int) $workers['idle']; $busyPct = $total > 0 ? round(((int) $workers['busy'] / $total) * 100) : 0; @endphp
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-500 dark:text-gray-400">Busy</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $workers['busy'] }} / {{ $total }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div class="bg-blue-500 h-4 rounded-full transition-all" style="width: {{ $busyPct }}%"></div>
                        </div>
                    </div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-gray-500 dark:text-gray-400">Idle</span>
                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $workers['idle'] }}</span>
                    </div>

                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mt-6 mb-3">Connection States</h4>
                    @if (!empty($connections['scoreboard']))
                        <div class="space-y-2">
                            @foreach ([
                                'waiting' => ['label' => 'Waiting for Connection', 'color' => 'bg-gray-400'],
                                'starting' => ['label' => 'Starting Up', 'color' => 'bg-yellow-400'],
                                'reading' => ['label' => 'Reading Request', 'color' => 'bg-blue-400'],
                                'sending' => ['label' => 'Sending Reply', 'color' => 'bg-green-400'],
                                'keepalive' => ['label' => 'Keepalive', 'color' => 'bg-purple-400'],
                                'dns' => ['label' => 'DNS Lookup', 'color' => 'bg-orange-400'],
                                'closing' => ['label' => 'Closing', 'color' => 'bg-red-400'],
                                'logging' => ['label' => 'Logging', 'color' => 'bg-pink-400'],
                                'finishing' => ['label' => 'Finishing', 'color' => 'bg-indigo-400'],
                                'idle_cleanup' => ['label' => 'Idle Cleanup', 'color' => 'bg-teal-400'],
                                'open' => ['label' => 'Open Slot', 'color' => 'bg-gray-200 dark:bg-gray-600'],
                            ] as $key => $state)
                                @php $count = $connections['scoreboard'][$key] ?? 0; @endphp
                                @if ($count > 0)
                                    <div class="flex items-center">
                                        <span class="w-3 h-3 rounded-full {{ $state['color'] }} mr-2 shrink-0"></span>
                                        <span class="text-sm text-gray-600 dark:text-gray-400 flex-1">{{ $state['label'] }}</span>
                                        <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $count }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @elseif ($connections['total'] > 0)
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Total Connections</span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $connections['total'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Writing</span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $connections['writing'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Keepalive</span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $connections['keepalive'] }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500 dark:text-gray-400">Closing</span>
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $connections['closing'] }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No connection data available.</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Request Throughput</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ number_format((float) $requests['per_sec'], 2) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Requests/sec</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                                @php
                                    $bps = (float) $requests['bytes_per_sec'];
                                    if ($bps >= 1048576) {
                                        echo number_format($bps / 1048576, 2);
                                    } elseif ($bps >= 1024) {
                                        echo number_format($bps / 1024, 2);
                                    } else {
                                        echo number_format($bps, 2);
                                    }
                                @endphp
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                @php
                                    $bps = (float) $requests['bytes_per_sec'];
                                    if ($bps >= 1048576) echo 'MB/s';
                                    elseif ($bps >= 1024) echo 'KB/s';
                                    else echo 'bytes/s';
                                @endphp
                            </p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format((float) $requests['bytes_per_req'], 2) }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Bytes/request</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">System Load</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $load['1'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Load 1 min</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $load['5'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Load 5 min</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $load['15'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Load 15 min</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-2xl font-bold text-gray-800 dark:text-gray-200">{{ $load['cpu_load'] }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">CPU Load</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Error Log (recent)</h3>
                    @if (!empty($errorLog))
                        <div class="space-y-1">
                            @foreach ($errorLog as $logLine)
                                <div class="text-xs font-mono text-gray-600 dark:text-gray-400 truncate hover:text-clip" title="{{ $logLine }}">
                                    {{ $logLine }}
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No error log entries available or log file not readable.</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Virtual Hosts</h3>
                    @if (!empty($vhosts))
                        <div class="space-y-1">
                            @foreach ($vhosts as $vhost)
                                <div class="text-sm font-mono text-gray-600 dark:text-gray-400">{{ $vhost }}</div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No virtual host information available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
