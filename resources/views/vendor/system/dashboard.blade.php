@php
    $error = null;
    $topOutput = null;
    $memInfo = null;
    $dfOutput = null;
    $psCpu = null;
    $psMem = null;
    $netDev = null;
    $ssOutput = null;
    $cpuInfo = null;
    $diskStats = null;
    $osRelease = null;

    try {
        $topOutput = @shell_exec('top -bn1 2>/dev/null');
    } catch (\Exception $e) {
        $error = 'Unable to read system metrics via top.';
    }

    try {
        $memInfo = @shell_exec('free -m 2>/dev/null');
    } catch (\Exception $e) {
        $memInfo = null;
    }

    try {
        $dfOutput = @shell_exec('df -h 2>/dev/null');
    } catch (\Exception $e) {
        $dfOutput = null;
    }

    try {
        $psCpu = @shell_exec('ps aux --sort=-%cpu 2>/dev/null');
    } catch (\Exception $e) {
        $psCpu = null;
    }

    try {
        $psMem = @shell_exec('ps aux --sort=-%mem 2>/dev/null');
    } catch (\Exception $e) {
        $psMem = null;
    }

    try {
        $netDev = @file_get_contents('/proc/net/dev');
    } catch (\Exception $e) {
        $netDev = null;
    }

    try {
        $ssOutput = @shell_exec('ss -tuln 2>/dev/null');
    } catch (\Exception $e) {
        $ssOutput = null;
    }

    try {
        $cpuInfo = @file_get_contents('/proc/cpuinfo');
    } catch (\Exception $e) {
        $cpuInfo = null;
    }

    try {
        $diskStats = @file_get_contents('/proc/diskstats');
    } catch (\Exception $e) {
        $diskStats = null;
    }

    try {
        $osRelease = @file_get_contents('/etc/os-release');
    } catch (\Exception $e) {
        $osRelease = null;
    }

    // Parse CPU
    $cpuUsage = ['us' => 0, 'sy' => 0, 'ni' => 0, 'id' => 100, 'wa' => 0, 'hi' => 0, 'si' => 0, 'st' => 0];
    $loadAvg = ['1' => 'N/A', '5' => 'N/A', '15' => 'N/A'];
    $cpuModel = 'N/A';
    $cpuCores = 0;
    $uptime = 'N/A';

    if ($topOutput) {
        $lines = explode("\n", trim($topOutput));
        foreach ($lines as $line) {
            if (str_starts_with($line, 'top -')) {
                preg_match('/up\s+(.*?),\s+/', $line, $m);
                $uptime = $m[1] ?? 'N/A';
                preg_match('/load average:\s+(.*)/', $line, $m);
                if (isset($m[1])) {
                    $parts = explode(', ', $m[1]);
                    $loadAvg['1'] = $parts[0] ?? 'N/A';
                    $loadAvg['5'] = $parts[1] ?? 'N/A';
                    $loadAvg['15'] = $parts[2] ?? 'N/A';
                }
            }
            if (str_starts_with($line, '%Cpu(s):')) {
                preg_match_all('/(\d+\.?\d*)\s*(us|sy|ni|id|wa|hi|si|st)/', $line, $m);
                foreach ($m[2] as $i => $key) {
                    $cpuUsage[$key] = (float) $m[1][$i];
                }
            }
        }
    }

    if ($cpuInfo) {
        preg_match('/model name\s+:\s+(.+)/', $cpuInfo, $m);
        $cpuModel = $m[1] ?? 'N/A';
        $cpuCores = (int) shell_exec('nproc 2>/dev/null') ?: substr_count($cpuInfo, "\nprocessor") + 1;
    }

    // Parse per-core CPU from /proc/stat
    $perCoreCpu = [];
    try {
        $statContent = @file_get_contents('/proc/stat');
        if ($statContent) {
            foreach (explode("\n", trim($statContent)) as $line) {
                if (preg_match('/^cpu(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $line, $m)) {
                    $core = (int) $m[1];
                    $user = (int) $m[2];
                    $nice = (int) $m[3];
                    $system = (int) $m[4];
                    $idle = (int) $m[5];
                    $total = $user + $nice + $system + $idle;
                    $perCoreCpu[$core] = $total > 0 ? [
                        'us' => round(($user / $total) * 100, 1),
                        'sy' => round(($system / $total) * 100, 1),
                        'id' => round(($idle / $total) * 100, 1),
                    ] : ['us' => 0, 'sy' => 0, 'id' => 100];
                }
            }
        }
    } catch (\Exception $e) {
        $perCoreCpu = [];
    }

    // Parse memory
    $memory = ['total' => 0, 'used' => 0, 'free' => 0, 'available' => 0, 'buff_cache' => 0];
    $swap = ['total' => 0, 'used' => 0, 'free' => 0];

    if ($memInfo) {
        $lines = explode("\n", trim($memInfo));
        if (count($lines) >= 2) {
            $parts = preg_split('/\s+/', trim($lines[1]));
            if (count($parts) >= 6) {
                $memory = [
                    'total' => (int) $parts[1],
                    'used' => (int) $parts[2],
                    'free' => (int) $parts[3],
                    'shared' => (int) $parts[4],
                    'buff_cache' => (int) $parts[5],
                    'available' => (int) $parts[6],
                ];
            }
        }
        if (count($lines) >= 3) {
            $parts = preg_split('/\s+/', trim($lines[2]));
            if (count($parts) >= 3) {
                $swap = [
                    'total' => (int) $parts[1],
                    'used' => (int) $parts[2],
                    'free' => (int) $parts[3],
                ];
            }
        }
    }

    // Parse disk
    $disks = [];
    if ($dfOutput) {
        $lines = explode("\n", trim($dfOutput));
        foreach ($lines as $i => $line) {
            if ($i === 0) continue;
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 6 && str_starts_with($parts[0], '/')) {
                $disks[] = [
                    'filesystem' => $parts[0],
                    'size' => $parts[1],
                    'used' => $parts[2],
                    'avail' => $parts[3],
                    'use_pct' => (int) $parts[4],
                    'mounted' => $parts[5],
                ];
            }
        }
    }

    // Parse network
    $interfaces = [];
    if ($netDev) {
        $lines = explode("\n", trim($netDev));
        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$iface, $data] = explode(':', $line, 2);
                $iface = trim($iface);
                if ($iface === 'lo') continue;
                $vals = preg_split('/\s+/', trim($data));
                if (count($vals) >= 9) {
                    $interfaces[] = [
                        'name' => $iface,
                        'rx_bytes' => (int) $vals[0],
                        'rx_packets' => (int) $vals[1],
                        'rx_errs' => (int) $vals[2],
                        'rx_drop' => (int) $vals[3],
                        'tx_bytes' => (int) $vals[8],
                        'tx_packets' => (int) $vals[9],
                        'tx_errs' => (int) $vals[10],
                        'tx_drop' => (int) $vals[11],
                    ];
                }
            }
        }
    }

    // Parse listening ports
    $listeningPorts = [];
    if ($ssOutput) {
        $lines = explode("\n", trim($ssOutput));
        foreach ($lines as $i => $line) {
            if ($i === 0) continue;
            $parts = preg_split('/\s+/', trim($line));
            if (count($parts) >= 5) {
                $addrPort = $parts[4];
                if (str_ends_with($addrPort, ':*')) continue;
                $listeningPorts[] = [
                    'protocol' => $parts[0],
                    'address' => $addrPort,
                ];
            }
        }
    }

    // Parse top processes by CPU
    $topCpu = [];
    if ($psCpu) {
        $lines = explode("\n", trim($psCpu));
        $count = 0;
        foreach ($lines as $i => $line) {
            if ($i === 0) continue;
            if ($count >= 10) break;
            $parts = preg_split('/\s+/', trim($line), 11);
            if (count($parts) >= 11) {
                $topCpu[] = [
                    'user' => $parts[0],
                    'pid' => $parts[1],
                    'cpu' => (float) $parts[2],
                    'mem' => (float) $parts[3],
                    'command' => strlen($parts[10]) > 60 ? substr($parts[10], 0, 60) . '...' : $parts[10],
                ];
                $count++;
            }
        }
    }

    // Parse top processes by MEM
    $topMem = [];
    if ($psMem) {
        $lines = explode("\n", trim($psMem));
        $count = 0;
        foreach ($lines as $i => $line) {
            if ($i === 0) continue;
            if ($count >= 10) break;
            $parts = preg_split('/\s+/', trim($line), 11);
            if (count($parts) >= 11) {
                $topMem[] = [
                    'user' => $parts[0],
                    'pid' => $parts[1],
                    'cpu' => (float) $parts[2],
                    'mem' => (float) $parts[3],
                    'command' => strlen($parts[10]) > 60 ? substr($parts[10], 0, 60) . '...' : $parts[10],
                ];
                $count++;
            }
        }
    }

    // Parse OS info
    $osName = 'Linux';
    if ($osRelease) {
        preg_match('/PRETTY_NAME="(.+)"/', $osRelease, $m);
        $osName = $m[1] ?? 'Linux';
    }

    // System info
    $hostname = trim(shell_exec('hostname 2>/dev/null') ?: '');
    $kernel = '';
    $arch = '';
    $uptimeSeconds = 0;
    try {
        $uname = @shell_exec('uname -r 2>/dev/null');
        $kernel = $uname ? trim($uname) : '';
        $arch = trim(shell_exec('uname -m 2>/dev/null') ?: '');
        $uptimeRaw = @file_get_contents('/proc/uptime');
        if ($uptimeRaw) {
            $uptimeSeconds = (int) explode(' ', $uptimeRaw)[0];
        }
    } catch (\Exception $e) {
    }

    // Format uptime from top output
    $formattedUptime = $uptime;
    if ($uptimeSeconds > 0 && $uptime === 'N/A') {
        $days = floor($uptimeSeconds / 86400);
        $hours = floor(($uptimeSeconds % 86400) / 3600);
        $mins = floor(($uptimeSeconds % 3600) / 60);
        $formattedUptime = $days > 0 ? "{$days} days, {$hours}:{$mins}" : "{$hours}:{$mins}";
    }

    $memPct = $memory['total'] > 0 ? round(($memory['used'] / $memory['total']) * 100) : 0;
    $swapPct = $swap['total'] > 0 ? round(($swap['used'] / $swap['total']) * 100) : 0;

    // Format bytes helper
    $formatBytes = function ($bytes) {
        if ($bytes >= 1099511627776) {
            return round($bytes / 1099511627776, 2) . ' TB';
        }
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2) . ' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('System Dashboard') }}
        </h2>
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">CPU Usage</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $cpuUsage['us'] + $cpuUsage['sy'] }}%</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Memory</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ number_format($memory['used']) }} <span class="text-sm font-normal text-gray-500">/ {{ number_format($memory['total']) }} MB</span></p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/30">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Uptime</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $formattedUptime }}</p>
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
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Load Average</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $loadAvg['1'] }} / {{ $loadAvg['5'] }} / {{ $loadAvg['15'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">CPU</h3>
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-500 dark:text-gray-400">Total Usage</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $cpuUsage['us'] + $cpuUsage['sy'] }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 flex overflow-hidden">
                            <div class="bg-blue-500 h-4 transition-all" style="width: {{ $cpuUsage['us'] }}%"></div>
                            <div class="bg-cyan-500 h-4 transition-all" style="width: {{ $cpuUsage['sy'] }}%"></div>
                            <div class="bg-yellow-500 h-4 transition-all" style="width: {{ $cpuUsage['ni'] > 0 ? $cpuUsage['ni'] . '%' : '0%' }}"></div>
                            <div class="bg-red-500 h-4 transition-all" style="width: {{ $cpuUsage['wa'] > 0 ? $cpuUsage['wa'] . '%' : '0%' }}"></div>
                            <div class="bg-purple-500 h-4 transition-all" style="width: {{ $cpuUsage['si'] > 0 ? $cpuUsage['si'] . '%' : '0%' }}"></div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 mb-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">User {{ $cpuUsage['us'] }}%</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-cyan-100 text-cyan-800 dark:bg-cyan-900/50 dark:text-cyan-300">System {{ $cpuUsage['sy'] }}%</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">Idle {{ $cpuUsage['id'] }}%</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">Nice {{ $cpuUsage['ni'] }}%</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">IOWait {{ $cpuUsage['wa'] }}%</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300">SoftIRQ {{ $cpuUsage['si'] }}%</span>
                    </div>
                    <div class="space-y-2">
                        @foreach ($perCoreCpu as $core => $usage)
                            <div>
                                <div class="flex justify-between text-xs mb-0.5">
                                    <span class="text-gray-500 dark:text-gray-400">Core {{ $core }}</span>
                                    <span class="font-medium text-gray-700 dark:text-gray-300">{{ $usage['us'] + $usage['sy'] }}%</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 flex overflow-hidden">
                                    <div class="bg-blue-500 h-2 transition-all" style="width: {{ $usage['us'] }}%"></div>
                                    <div class="bg-cyan-500 h-2 transition-all" style="width: {{ $usage['sy'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <dl class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 space-y-1">
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">Model</dt>
                            <dd class="font-medium text-gray-800 dark:text-gray-200 text-right max-w-[60%]">{{ $cpuModel }}</dd>
                        </div>
                        <div class="flex justify-between text-sm">
                            <dt class="text-gray-500 dark:text-gray-400">Cores</dt>
                            <dd class="font-medium text-gray-800 dark:text-gray-200">{{ $cpuCores }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Memory</h3>
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-500 dark:text-gray-400">RAM Usage</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ number_format($memory['used']) }} MB / {{ number_format($memory['total']) }} MB ({{ $memPct }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div class="bg-green-500 h-4 rounded-full transition-all" style="width: {{ min($memPct, 100) }}%"></div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-gray-500 dark:text-gray-400">Swap Usage</span>
                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ number_format($swap['used']) }} MB / {{ number_format($swap['total']) }} MB ({{ $swapPct }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4">
                            <div class="bg-red-500 h-4 rounded-full transition-all" style="width: {{ min($swapPct, 100) }}%"></div>
                        </div>
                    </div>
                    <dl class="space-y-2">
                        <div class="flex justify-between py-1">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Total</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ number_format($memory['total']) }} MB</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Used</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ number_format($memory['used']) }} MB</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Free</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ number_format($memory['free']) }} MB</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Available</dt>
                            <dd class="text-sm font-medium text-green-600 dark:text-green-400">{{ number_format($memory['available']) }} MB</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Buff/Cache</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ number_format($memory['buff_cache']) }} MB</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Swap Total</dt>
                            <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ number_format($swap['total']) }} MB</dd>
                        </div>
                        <div class="flex justify-between py-1 border-t border-gray-100 dark:border-gray-700">
                            <dt class="text-sm text-gray-500 dark:text-gray-400">Swap Used</dt>
                            <dd class="text-sm font-medium {{ $swapPct > 50 ? 'text-red-600 dark:text-red-400' : 'text-gray-800 dark:text-gray-200' }}">{{ number_format($swap['used']) }} MB</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Disk Usage</h3>
                    @if (!empty($disks))
                        <div class="space-y-4">
                            @foreach ($disks as $disk)
                                <div>
                                    <div class="flex justify-between text-sm mb-1">
                                        <span class="text-gray-500 dark:text-gray-400 font-mono text-xs">{{ $disk['filesystem'] }}</span>
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $disk['used'] }} / {{ $disk['size'] }}</span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                                            <div class="{{ $disk['use_pct'] > 90 ? 'bg-red-500' : ($disk['use_pct'] > 75 ? 'bg-yellow-500' : 'bg-blue-500') }} h-3 rounded-full transition-all" style="width: {{ $disk['use_pct'] }}%"></div>
                                        </div>
                                        <span class="text-xs font-medium {{ $disk['use_pct'] > 90 ? 'text-red-600 dark:text-red-400' : 'text-gray-600 dark:text-gray-400' }}">{{ $disk['use_pct'] }}%</span>
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Mounted on {{ $disk['mounted'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No disk information available.</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Network</h3>
                    @if (!empty($interfaces))
                        <div class="overflow-x-auto mb-4">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-1.5 pr-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Interface</th>
                                        <th class="text-right py-1.5 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Rx</th>
                                        <th class="text-right py-1.5 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Tx</th>
                                        <th class="text-right py-1.5 pl-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Errors</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($interfaces as $iface)
                                        <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                            <td class="py-1.5 pr-3 font-mono text-xs text-gray-800 dark:text-gray-200">{{ $iface['name'] }}</td>
                                            <td class="py-1.5 px-3 text-right text-xs text-gray-600 dark:text-gray-400">{{ $formatBytes($iface['rx_bytes']) }}</td>
                                            <td class="py-1.5 px-3 text-right text-xs text-gray-600 dark:text-gray-400">{{ $formatBytes($iface['tx_bytes']) }}</td>
                                            <td class="py-1.5 pl-3 text-right text-xs {{ ($iface['rx_errs'] + $iface['tx_errs']) > 0 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-gray-600 dark:text-gray-400' }}">{{ $iface['rx_errs'] + $iface['tx_errs'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic mb-4">No network interface data available.</p>
                    @endif

                    <h4 class="text-md font-semibold text-gray-800 dark:text-gray-200 mb-3">Listening Ports</h4>
                    @if (!empty($listeningPorts))
                        <div class="space-y-1">
                            @foreach ($listeningPorts as $port)
                                <div class="flex items-center text-sm">
                                    <span class="w-16 text-xs font-mono {{ $port['protocol'] === 'tcp' ? 'text-blue-600 dark:text-blue-400' : 'text-purple-600 dark:text-purple-400' }}">{{ strtoupper($port['protocol']) }}</span>
                                    <span class="font-mono text-xs text-gray-600 dark:text-gray-400">{{ $port['address'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No listening port data available.</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Top Processes by CPU</h3>
                    @if (!empty($topCpu))
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-1.5 pr-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">PID</th>
                                        <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">CPU%</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">MEM%</th>
                                        <th class="text-left py-1.5 pl-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Command</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topCpu as $proc)
                                        <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                            <td class="py-1 pr-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $proc['pid'] }}</td>
                                            <td class="py-1 px-2 text-xs text-gray-600 dark:text-gray-400">{{ $proc['user'] }}</td>
                                            <td class="py-1 px-2 text-right text-xs font-medium text-gray-800 dark:text-gray-200">{{ number_format($proc['cpu'], 1) }}</td>
                                            <td class="py-1 px-2 text-right text-xs font-medium text-gray-800 dark:text-gray-200">{{ number_format($proc['mem'], 1) }}</td>
                                            <td class="py-1 pl-2 text-xs font-mono text-gray-600 dark:text-gray-400 truncate max-w-[200px]">{{ $proc['command'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No process data available.</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Top Processes by Memory</h3>
                    @if (!empty($topMem))
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-1.5 pr-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">PID</th>
                                        <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">CPU%</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">MEM%</th>
                                        <th class="text-left py-1.5 pl-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Command</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topMem as $proc)
                                        <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                            <td class="py-1 pr-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $proc['pid'] }}</td>
                                            <td class="py-1 px-2 text-xs text-gray-600 dark:text-gray-400">{{ $proc['user'] }}</td>
                                            <td class="py-1 px-2 text-right text-xs font-medium text-gray-800 dark:text-gray-200">{{ number_format($proc['cpu'], 1) }}</td>
                                            <td class="py-1 px-2 text-right text-xs font-medium text-gray-800 dark:text-gray-200">{{ number_format($proc['mem'], 1) }}</td>
                                            <td class="py-1 pl-2 text-xs font-mono text-gray-600 dark:text-gray-400 truncate max-w-[200px]">{{ $proc['command'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No process data available.</p>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">System Information</h3>
                <dl class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Hostname</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $hostname }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Operating System</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $osName }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Kernel</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $kernel }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Architecture</dt>
                        <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $arch }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</x-app-layout>
