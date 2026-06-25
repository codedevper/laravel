@php
    $tab = request('tab', 'rules');

    // Collect iptables rules for display
    $rules = [];
    $chainPolicies = [];
    $ufwActive = false;
    $listeningPorts = [];
    $dropLog = [];

    try {
        $rawRules = @shell_exec('sudo -n iptables -L -n --line-numbers 2>/dev/null');
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
    } catch (\Exception $e) {
    }

    try {
        $ufwRaw = @shell_exec('sudo -n ufw status verbose 2>/dev/null');
        if ($ufwRaw && str_contains($ufwRaw, 'Status: active')) {
            $ufwActive = true;
        }
    } catch (\Exception $e) {
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
                        $listeningPorts[] = (int) $m[1];
                    }
                }
            }
        }
    } catch (\Exception $e) {
    }

    try {
        $dmesgRaw = @shell_exec('sudo -n dmesg 2>/dev/null | grep "DPT=" | tail -50');
        if ($dmesgRaw) {
            $dropLog = array_reverse(array_filter(explode("\n", trim($dmesgRaw))));
        }
    } catch (\Exception $e) {
    }

    $result = session('firewall_result');
    $error = session('firewall_error');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Firewall Settings') }}
            </h2>
            <a href="{{ route('firewall.dashboard') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
                {{ __('Back to Dashboard') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8">
                    <a href="{{ route('firewall.settings', ['tab' => 'rules']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition
                        {{ $tab === 'rules' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Rules
                    </a>
                    <a href="{{ route('firewall.settings', ['tab' => 'ports']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition
                        {{ $tab === 'ports' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Ports
                    </a>
                    <a href="{{ route('firewall.settings', ['tab' => 'ufw']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition
                        {{ $tab === 'ufw' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        UFW
                    </a>
                    <a href="{{ route('firewall.settings', ['tab' => 'logging']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition
                        {{ $tab === 'logging' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Logging
                    </a>
                </nav>
            </div>

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

            @if ($result)
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <pre class="text-sm text-green-700 dark:text-green-300 whitespace-pre-wrap font-sans">{{ $result }}</pre>
                    </div>
                </div>
            @endif

            @if ($tab === 'rules')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Add Rule</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Add a new iptables rule. Requires passwordless sudo.</p>

                        <form method="POST" action="{{ route('firewall.settings') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="tab" value="rules">

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chain</label>
                                <select name="chain" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                    <option value="INPUT">INPUT</option>
                                    <option value="FORWARD">FORWARD</option>
                                    <option value="OUTPUT">OUTPUT</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Target</label>
                                <select name="target" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                    <option value="ACCEPT">ACCEPT</option>
                                    <option value="DROP">DROP</option>
                                    <option value="REJECT">REJECT</option>
                                    <option value="LOG">LOG</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Protocol</label>
                                <select name="protocol" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                    <option value="all">All</option>
                                    <option value="tcp">TCP</option>
                                    <option value="udp">UDP</option>
                                    <option value="icmp">ICMP</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source IP (optional)</label>
                                <input type="text" name="source" placeholder="e.g. 192.168.1.0/24" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Destination Port (optional)</label>
                                <input type="number" name="dport" placeholder="e.g. 80, 443" min="1" max="65535" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                            </div>

                            <div class="flex space-x-3">
                                <button type="submit" name="action" value="add-rule" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                    Add Rule
                                </button>
                                <button type="submit" name="action" value="flush-chain" class="px-4 py-1.5 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-md transition"
                                        onclick="return confirm('Are you sure you want to flush ALL rules from this chain?')">
                                    Flush Chain
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Delete Rule</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Select a rule number to remove it from a chain.</p>

                        <form method="POST" action="{{ route('firewall.settings') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="tab" value="rules">

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Chain</label>
                                <select name="chain" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                    <option value="INPUT">INPUT</option>
                                    <option value="FORWARD">FORWARD</option>
                                    <option value="OUTPUT">OUTPUT</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rule Number</label>
                                <input type="number" name="rule_num" placeholder="e.g. 1" min="1" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                            </div>

                            <button type="submit" name="action" value="delete-rule" class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition"
                                    onclick="return confirm('Delete this rule?')">
                                Delete Rule
                            </button>
                        </form>

                        <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Current Counts</h4>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">INPUT rules</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ count(array_filter($rules, fn($r) => $r['chain'] === 'INPUT')) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">FORWARD rules</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ count(array_filter($rules, fn($r) => $r['chain'] === 'FORWARD')) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-500 dark:text-gray-400">OUTPUT rules</span>
                                    <span class="font-medium text-gray-800 dark:text-gray-200">{{ count(array_filter($rules, fn($r) => $r['chain'] === 'OUTPUT')) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Current Rules Reference</h3>
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
                                        <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                            <td class="py-1 pr-2 text-xs text-gray-400 dark:text-gray-500">{{ $rule['num'] }}</td>
                                            <td class="py-1 px-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $rule['chain'] }}</td>
                                            <td class="py-1 px-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $rule['target'] }}</td>
                                            <td class="py-1 px-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $rule['prot'] }}</td>
                                            <td class="py-1 px-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $rule['source'] }}</td>
                                            <td class="py-1 px-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $rule['destination'] }}</td>
                                            <td class="py-1 pl-2 text-xs font-mono text-gray-500 dark:text-gray-500 truncate max-w-[200px]" title="{{ $rule['extra'] }}">{{ $rule['extra'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No rules found.</p>
                    @endif
                </div>

            @elseif ($tab === 'ports')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Quick Block/Unblock Ports</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Quickly allow or deny access to common ports via iptables.</p>

                        <form method="POST" action="{{ route('firewall.settings') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="tab" value="ports">

                            <div class="grid grid-cols-2 gap-3">
                                @foreach ([22 => 'SSH', 80 => 'HTTP', 443 => 'HTTPS', 3306 => 'MySQL', 5432 => 'PostgreSQL', 6379 => 'Redis', 8080 => 'HTTP Alt', 8443 => 'HTTPS Alt', 25 => 'SMTP', 587 => 'SMTP Submission', 993 => 'IMAP SSL', 6001 => 'WebSocket'] as $port => $label)
                                    @php
                                        $isListening = in_array($port, $listeningPorts);
                                        $isBlocked = !empty(array_filter($rules, fn($r) => $r['chain'] === 'INPUT' && $r['target'] === 'DROP' && str_contains($r['extra'], "dpt:=${port}")));
                                    @endphp
                                    <div class="flex items-center justify-between px-3 py-2 rounded-lg {{ $isListening ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-700/30' }}">
                                        <div>
                                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $label }}</span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">({{ $port }})</span>
                                        </div>
                                        <div class="flex space-x-1">
                                            <button type="submit" name="action" value="allow-port" onclick="this.form.elements['port'].value={{ $port }}"
                                                    class="px-2 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded transition">
                                                Allow
                                            </button>
                                            <button type="submit" name="action" value="block-port" onclick="this.form.elements['port'].value={{ $port }}"
                                                    class="px-2 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded transition">
                                                Block
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <input type="hidden" name="port" value="">
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Custom Port Rule</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Allow or block a custom port number on the INPUT chain.</p>

                        <form method="POST" action="{{ route('firewall.settings') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="tab" value="ports">

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Port Number</label>
                                <input type="number" name="port" placeholder="e.g. 8080" min="1" max="65535" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Protocol</label>
                                <select name="protocol" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                    <option value="tcp">TCP</option>
                                    <option value="udp">UDP</option>
                                </select>
                            </div>

                            <div class="flex space-x-3">
                                <button type="submit" name="action" value="allow-port" class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition">
                                    Allow Port
                                </button>
                                <button type="submit" name="action" value="block-port" class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition">
                                    Block Port
                                </button>
                            </div>
                        </form>

                        <div class="mt-6 pt-6 border-t border-gray-100 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Listening Services</h4>
                            @if (!empty($listeningPorts))
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($listeningPorts as $p)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                            Port {{ $p }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <p class="text-xs text-gray-400 dark:text-gray-500 italic">No listening services detected.</p>
                            @endif
                        </div>
                    </div>
                </div>

            @elseif ($tab === 'ufw')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">UFW Status</h3>
                        <div class="flex items-center space-x-3 mb-6">
                            <span class="w-4 h-4 rounded-full {{ $ufwActive ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                            <span class="text-lg font-semibold {{ $ufwActive ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                                UFW is {{ $ufwActive ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('firewall.settings') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="tab" value="ufw">

                            @if ($ufwActive)
                                <button type="submit" name="action" value="ufw-disable" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition"
                                        onclick="return confirm('Disable UFW? This may expose the system.')">
                                    Disable UFW
                                </button>
                            @else
                                <button type="submit" name="action" value="ufw-enable" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition">
                                    Enable UFW
                                </button>
                            @endif
                        </form>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">UFW Rules</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Add or remove UFW rules.</p>

                        <form method="POST" action="{{ route('firewall.settings') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="tab" value="ufw">

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Direction</label>
                                <select name="ufw_direction" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                    <option value="allow">Allow</option>
                                    <option value="deny">Deny</option>
                                    <option value="reject">Reject</option>
                                    <option value="limit">Limit</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Direction (in/out)</label>
                                <select name="ufw_io" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                    <option value="in">Incoming</option>
                                    <option value="out">Outgoing</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Port / Service</label>
                                <input type="text" name="ufw_port" placeholder="e.g. 80, 443, ssh" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Protocol</label>
                                <select name="ufw_proto" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                    <option value="">Any</option>
                                    <option value="tcp">TCP</option>
                                    <option value="udp">UDP</option>
                                </select>
                            </div>

                            <div class="flex space-x-3">
                                <button type="submit" name="action" value="ufw-add" class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                    Add Rule
                                </button>
                                <button type="submit" name="action" value="ufw-delete" class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition"
                                        onclick="return confirm('Delete this UFW rule?')">
                                    Delete Rule
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

            @elseif ($tab === 'logging')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Recent Dropped Packets</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Last 50 dropped packet entries from kernel ring buffer.</p>

                    @if (!empty($dropLog))
                        <div class="space-y-1 max-h-96 overflow-y-auto bg-gray-50 dark:bg-gray-700/30 p-4 rounded-lg">
                            @foreach ($dropLog as $line)
                                <div class="text-xs font-mono text-gray-600 dark:text-gray-400 truncate hover:text-clip whitespace-pre-wrap" title="{{ $line }}">
                                    {{ $line }}
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No dropped packet entries found. Ensure logging rules are in place (e.g., `-j LOG`).</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Kernel Logs</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">View recent firewall-related kernel messages.</p>

                    <form method="POST" action="{{ route('firewall.settings') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="tab" value="logging">
                        <button type="submit" name="action" value="clear-dmesg" class="px-4 py-1.5 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-md transition"
                                onclick="return confirm('Clear kernel ring buffer?')">
                            Clear Kernel Log
                        </button>
                    </form>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
