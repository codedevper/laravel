@php
    $error = null;
    $rawRules = null;
    $chainPolicies = [];
    $rules = [];
    $ufwStatus = null;
    $ufwActive = false;
    $ufwRules = [];
    $listeningPorts = [];
    $activeConns = 0;
    $dropLog = [];

    try {
        $rawRules = @shell_exec('sudo -n iptables -L -n --line-numbers 2>/dev/null');
    } catch (\Exception $e) {
        $error = 'Unable to read iptables rules. Ensure passwordless sudo is configured.';
    }

    if ($rawRules) {
        $currentChain = null;
        $lineCount = 0;
        foreach (explode("\n", trim($rawRules)) as $line) {
            if (preg_match('/^Chain\s+(\S+)\s+\(policy\s+(\S+)\)/', $line, $m)) {
                $currentChain = $m[1];
                $chainPolicies[$currentChain] = $m[2];
                $lineCount = 0;
                continue;
            }
            if ($currentChain && preg_match('/^num\s+target/', $line)) {
                $lineCount = 0;
                continue;
            }
            if ($currentChain && $lineCount >= 0 && trim($line) !== '') {
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 8 && is_numeric($parts[0])) {
                    $rules[] = [
                        'chain' => $currentChain,
                        'num' => (int) $parts[0],
                        'target' => $parts[1],
                        'prot' => $parts[2],
                        'opt' => $parts[3],
                        'source' => $parts[4],
                        'destination' => $parts[5],
                        'extra' => implode(' ', array_slice($parts, 6)),
                    ];
                }
                $lineCount++;
            }
        }
    }

    try {
        $ufwRaw = @shell_exec('sudo -n ufw status verbose 2>/dev/null');
        if ($ufwRaw && str_contains($ufwRaw, 'Status: active')) {
            $ufwActive = true;
            $ufwStatus = $ufwRaw;
        }
    } catch (\Exception $e) {
        $ufwActive = false;
    }

    if ($ufwActive) {
        try {
            $ufwRulesRaw = @shell_exec('sudo -n ufw show added 2>/dev/null');
            if ($ufwRulesRaw) {
                foreach (explode("\n", trim($ufwRulesRaw)) as $line) {
                    if (trim($line) !== '') {
                        $ufwRules[] = $line;
                    }
                }
            }
        } catch (\Exception $e) {
            $ufwRules = [];
        }
    }

    try {
        $ssOutput = @shell_exec('ss -tuln 2>/dev/null');
        if ($ssOutput) {
            foreach (explode("\n", trim($ssOutput)) as $i => $line) {
                if ($i === 0) continue;
                $parts = preg_split('/\s+/', trim($line));
                if (count($parts) >= 5) {
                    $addr = $parts[4];
                    if (str_ends_with($addr, ':*')) continue;
                    if (preg_match('/:(\d+)$/', $addr, $m)) {
                        $listeningPorts[] = [
                            'protocol' => strtoupper($parts[0]),
                            'port' => (int) $m[1],
                            'address' => $addr,
                        ];
                    }
                }
            }
        }
    } catch (\Exception $e) {
        $listeningPorts = [];
    }

    try {
        $connRaw = @shell_exec('sudo -n conntrack -C 2>/dev/null');
        $activeConns = $connRaw ? (int) trim($connRaw) : 0;
    } catch (\Exception $e) {
        $activeConns = 0;
    }

    try {
        $dmesgRaw = @shell_exec('sudo -n dmesg 2>/dev/null | grep "DPT=" | tail -20');
        if ($dmesgRaw) {
            $dropLog = array_reverse(array_filter(explode("\n", trim($dmesgRaw))));
        }
    } catch (\Exception $e) {
        $dropLog = [];
    }

    $blockedPorts = [];
    $openPorts = [];
    $blockedSources = [];
    foreach ($rules as $rule) {
        if (in_array($rule['target'], ['DROP', 'REJECT'])) {
            if (preg_match('/dpt[:=](\d+)/', $rule['extra'], $m)) {
                $blockedPorts[] = (int) $m[1];
            }
            if ($rule['source'] !== '0.0.0.0/0' && $rule['source'] !== '::/0') {
                $blockedSources[] = $rule['source'];
            }
        }
        if ($rule['target'] === 'ACCEPT' && $rule['chain'] !== 'OUTPUT') {
            if (preg_match('/dpt[:=](\d+)/', $rule['extra'], $m)) {
                $openPorts[] = (int) $m[1];
            }
        }
    }
    $blockedPorts = array_unique($blockedPorts);
    $openPorts = array_unique($openPorts);
    $blockedSources = array_unique($blockedSources);

    $totalRules = count($rules);
    $blockRulesCount = count(array_filter($rules, fn($r) => in_array($r['target'], ['DROP', 'REJECT'])));
    $acceptRulesCount = count(array_filter($rules, fn($r) => $r['target'] === 'ACCEPT'));

    $getBadgeColor = function ($target) {
        return match ($target) {
            'ACCEPT' => 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300',
            'DROP' => 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
            'REJECT' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300',
            'LOG' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
            'RETURN' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
            default => 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
        };
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Firewall Dashboard') }}
            </h2>
            <a href="{{ route('firewall.settings') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Rules</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $totalRules }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Accept Rules</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $acceptRulesCount }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-red-100 dark:bg-red-900/30">
                            <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Block Rules</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $blockRulesCount }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/30">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">UFW</p>
                            <p class="text-lg font-semibold {{ $ufwActive ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $ufwActive ? 'Active' : 'Inactive' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Chain Policies</h3>
                    @if (!empty($chainPolicies))
                        <div class="space-y-3">
                            @foreach (['INPUT', 'FORWARD', 'OUTPUT'] as $chain)
                                @php
                                    $policy = $chainPolicies[$chain] ?? 'N/A';
                                    $isDrop = $policy === 'DROP';
                                @endphp
                                <div class="flex items-center justify-between py-2 px-4 rounded-lg {{ $isDrop ? 'bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800' : 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' }}">
                                    <div class="flex items-center space-x-3">
                                        <span class="w-3 h-3 rounded-full {{ $isDrop ? 'bg-red-500' : 'bg-green-500' }}"></span>
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $chain }}</span>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <span class="text-sm font-mono {{ $isDrop ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">{{ $policy }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if ($activeConns > 0)
                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-500 dark:text-gray-400">Active Connections</span>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ number_format($activeConns) }}</span>
                                </div>
                            </div>
                        @endif
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No policy information available.</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Port Status</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h4 class="text-sm font-semibold text-green-600 dark:text-green-400 mb-2">Open Ports</h4>
                            @if (!empty($openPorts))
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($openPorts as $port)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                            {{ $port }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-gray-400 dark:text-gray-500 italic">No ACCEPT rules with ports.</p>
                            @endif
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold text-red-600 dark:text-red-400 mb-2">Blocked Ports</h4>
                            @if (!empty($blockedPorts))
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($blockedPorts as $port)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                            {{ $port }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-gray-400 dark:text-gray-500 italic">No DROP/REJECT rules with ports.</p>
                            @endif
                        </div>
                    </div>
                    @if (!empty($blockedSources))
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-red-600 dark:text-red-400 mb-2">Blocked Sources</h4>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($blockedSources as $src)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                        {{ $src }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Current iptables Rules</h3>
                @if (!empty($rules))
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-1.5 pr-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">#</th>
                                    <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Chain</th>
                                    <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Target</th>
                                    <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Prot</th>
                                    <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Source</th>
                                    <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Dest</th>
                                    <th class="text-left py-1.5 pl-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rules as $rule)
                                    <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="py-1 pr-2 text-xs text-gray-400 dark:text-gray-500">{{ $rule['num'] }}</td>
                                        <td class="py-1 px-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $rule['chain'] }}</td>
                                        <td class="py-1 px-2">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $getBadgeColor($rule['target']) }}">
                                                {{ $rule['target'] }}
                                            </span>
                                        </td>
                                        <td class="py-1 px-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $rule['prot'] }}</td>
                                        <td class="py-1 px-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $rule['source'] }}</td>
                                        <td class="py-1 px-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $rule['destination'] }}</td>
                                        <td class="py-1 pl-2 text-xs font-mono text-gray-500 dark:text-gray-500 truncate max-w-[250px]" title="{{ $rule['extra'] }}">{{ $rule['extra'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">No rules found or unable to read iptables.</p>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Listening Ports</h3>
                    @if (!empty($listeningPorts))
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-1.5 pr-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Protocol</th>
                                        <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Port</th>
                                        <th class="text-left py-1.5 pl-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($listeningPorts as $lp)
                                        <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                            <td class="py-1 pr-2 text-xs font-mono {{ $lp['protocol'] === 'TCP' ? 'text-blue-600 dark:text-blue-400' : 'text-purple-600 dark:text-purple-400' }}">{{ $lp['protocol'] }}</td>
                                            <td class="py-1 px-2 text-xs font-mono text-gray-800 dark:text-gray-200 font-medium">{{ $lp['port'] }}</td>
                                            <td class="py-1 pl-2 text-xs font-mono text-gray-600 dark:text-gray-400 truncate max-w-[200px]">{{ $lp['address'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No listening ports found.</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Recent Drops</h3>
                    @if (!empty($dropLog))
                        <div class="space-y-1 max-h-80 overflow-y-auto">
                            @foreach ($dropLog as $line)
                                <div class="text-xs font-mono text-gray-600 dark:text-gray-400 truncate hover:text-clip" title="{{ $line }}">
                                    {{ $line }}
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No recent dropped packets in kernel log.</p>
                    @endif

                    @if ($ufwActive)
                        <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">UFW Rules</h3>
                            @if (!empty($ufwRules))
                                <div class="space-y-1">
                                    @foreach ($ufwRules as $rule)
                                        <div class="text-xs font-mono text-gray-600 dark:text-gray-400">{{ $rule }}</div>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No UFW rules added.</p>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
