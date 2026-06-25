@php
    $tab = request('tab', 'users');
    $result = session('quota_result');
    $error = session('quota_error');

    $systemUsers = [];
    $systemGroups = [];
    $quotaFilesystems = [];
    $userQuotas = [];
    $groupQuotas = [];
    $fstabEntries = [];
    $fstabRaw = '';

    try {
        $passwd = @file_get_contents('/etc/passwd');
        if ($passwd) {
            foreach (explode("\n", trim($passwd)) as $line) {
                $parts = explode(':', $line);
                if (count($parts) >= 3 && (int) $parts[2] >= 1000 && $parts[0] !== 'nobody') {
                    $systemUsers[] = ['name' => $parts[0], 'uid' => $parts[2], 'home' => $parts[5]];
                }
            }
            usort($systemUsers, fn($a, $b) => $a['uid'] <=> $b['uid']);
        }
    } catch (\Exception $e) {
        $systemUsers = [];
    }

    try {
        $groupRaw = @file_get_contents('/etc/group');
        if ($groupRaw) {
            foreach (explode("\n", trim($groupRaw)) as $line) {
                $parts = explode(':', $line);
                if (count($parts) >= 3 && (int) $parts[2] >= 1000) {
                    $systemGroups[] = ['name' => $parts[0], 'gid' => $parts[2]];
                }
            }
            usort($systemGroups, fn($a, $b) => $a['gid'] <=> $b['gid']);
        }
    } catch (\Exception $e) {
        $systemGroups = [];
    }

    try {
        $mounts = @file_get_contents('/proc/mounts');
        if ($mounts) {
            foreach (explode("\n", trim($mounts)) as $line) {
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 4) {
                    $opts = explode(',', $parts[3]);
                    if (in_array('usrquota', $opts) || in_array('grpquota', $opts) || in_array('quota', $opts)) {
                        $quotaFilesystems[] = [
                            'device' => $parts[0],
                            'mount' => $parts[1],
                            'fstype' => $parts[2],
                        ];
                    }
                }
            }
        }
    } catch (\Exception $e) {
        $quotaFilesystems = [];
    }

    try {
        $fstabRaw = @file_get_contents('/etc/fstab');
        if ($fstabRaw) {
            foreach (explode("\n", trim($fstabRaw)) as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                $parts = preg_split('/\s+/', $line);
                if (count($parts) >= 4) {
                    $opts = explode(',', $parts[3]);
                    $hasQuota = in_array('usrquota', $opts) || in_array('grpquota', $opts) || in_array('quota', $opts);
                    $fstabEntries[] = [
                        'device' => $parts[0],
                        'mount' => $parts[1],
                        'fstype' => $parts[2],
                        'opts' => $parts[3],
                        'dump' => $parts[4] ?? '0',
                        'pass' => $parts[5] ?? '0',
                        'has_quota' => $hasQuota,
                        'raw' => $line,
                    ];
                }
            }
        }
    } catch (\Exception $e) {
        $fstabEntries = [];
        $fstabRaw = '';
    }

    $parseRepquota = function ($output) {
        $result = [];
        $lines = explode("\n", trim($output));
        $inTable = false;
        foreach ($lines as $line) {
            if (preg_match('/^#\s+Block\s+limits/i', $line)) {
                $inTable = true;
                continue;
            }
            if ($inTable && preg_match('/^\*\*\s+(.+?)\s+--\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $line, $m)) {
                $result[] = [
                    'name' => trim($m[1]),
                    'blocks_used' => (int) $m[2],
                    'blocks_soft' => (int) $m[3],
                    'blocks_hard' => (int) $m[4],
                    'blocks_grace' => trim($m[5]),
                    'inodes_used' => (int) $m[6],
                    'inodes_soft' => (int) $m[7],
                    'inodes_hard' => (int) $m[8],
                ];
            }
        }
        return $result;
    };

    try {
        $repqUser = @shell_exec('repquota -au 2>/dev/null');
        if ($repqUser) {
            $userQuotas = $parseRepquota($repqUser);
        }
    } catch (\Exception $e) {
        $userQuotas = [];
    }

    try {
        $repqGroup = @shell_exec('repquota -ag 2>/dev/null');
        if ($repqGroup) {
            $groupQuotas = $parseRepquota($repqGroup);
        }
    } catch (\Exception $e) {
        $groupQuotas = [];
    }

    $formatBlocks = function ($blocks) {
        if ($blocks >= 1048576) {
            return round($blocks / 1048576, 2) . ' GB';
        }
        if ($blocks >= 1024) {
            return round($blocks / 1024, 2) . ' MB';
        }
        return $blocks . ' KB';
    };

    $quotaToMap = function ($quotas) {
        $map = [];
        foreach ($quotas as $q) {
            $map[$q['name']] = $q;
        }
        return $map;
    };

    $userQuotaMap = $quotaToMap($userQuotas);
    $groupQuotaMap = $quotaToMap($groupQuotas);
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Quota Settings') }}
            </h2>
            <a href="{{ route('quota.dashboard') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
                {{ __('Back to Dashboard') }}
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

            @if ($result)
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-green-500 mr-2 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                        <pre class="text-sm text-green-700 dark:text-green-300 whitespace-pre-wrap font-sans">{{ $result }}</pre>
                    </div>
                </div>
            @endif

            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8 overflow-x-auto">
                    <a href="{{ route('quota.settings', ['tab' => 'users']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'users' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Users
                    </a>
                    <a href="{{ route('quota.settings', ['tab' => 'groups']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'groups' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Groups
                    </a>
                    <a href="{{ route('quota.settings', ['tab' => 'management']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'management' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Management
                    </a>
                    <a href="{{ route('quota.settings', ['tab' => 'fstab']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'fstab' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        fstab
                    </a>
                </nav>
            </div>

            @if ($tab === 'users')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Set User Quota</h3>
                    <form method="POST" action="{{ route('quota.settings') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="tab" value="users">
                        <input type="hidden" name="action" value="set-user-quota">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">User</label>
                                <select name="username" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select a user...</option>
                                    @foreach ($systemUsers as $u)
                                        <option value="{{ $u['name'] }}" {{ request('username') === $u['name'] ? 'selected' : '' }}>
                                            {{ $u['name'] }} (UID {{ $u['uid'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filesystem</label>
                                <select name="filesystem" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    @if (!empty($quotaFilesystems))
                                        @foreach ($quotaFilesystems as $fs)
                                            <option value="{{ $fs['mount'] }}">{{ $fs['mount'] }} ({{ $fs['device'] }})</option>
                                        @endforeach
                                    @else
                                        <option value="">No quota-enabled filesystems found</option>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Soft Block Limit (KB)</label>
                                <input type="number" name="block_soft" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0 = unlimited">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hard Block Limit (KB)</label>
                                <input type="number" name="block_hard" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0 = unlimited">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Soft Inode Limit</label>
                                <input type="number" name="inode_soft" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0 = unlimited">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hard Inode Limit</label>
                                <input type="number" name="inode_hard" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0 = unlimited">
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                Set Quota
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Current User Quotas</h3>
                    @if (!empty($userQuotas))
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-1.5 pr-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">User</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Used</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Soft</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Hard</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Inodes</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">I-Soft</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">I-Hard</th>
                                        <th class="text-left py-1.5 pl-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Grace</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($userQuotas as $q)
                                        <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                            <td class="py-1.5 pr-2 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $q['name'] }}</td>
                                            <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $formatBlocks($q['blocks_used']) }}</td>
                                            <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['blocks_soft'] > 0 ? $formatBlocks($q['blocks_soft']) : '-' }}</td>
                                            <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['blocks_hard'] > 0 ? $formatBlocks($q['blocks_hard']) : '-' }}</td>
                                            <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ number_format($q['inodes_used']) }}</td>
                                            <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['inodes_soft'] > 0 ? number_format($q['inodes_soft']) : '-' }}</td>
                                            <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['inodes_hard'] > 0 ? number_format($q['inodes_hard']) : '-' }}</td>
                                            <td class="py-1.5 pl-2 text-xs text-gray-500 dark:text-gray-400">{{ $q['blocks_grace'] ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No user quota data available.</p>
                    @endif
                </div>

            @elseif ($tab === 'groups')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Set Group Quota</h3>
                    <form method="POST" action="{{ route('quota.settings') }}" class="space-y-4">
                        @csrf
                        <input type="hidden" name="tab" value="groups">
                        <input type="hidden" name="action" value="set-group-quota">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Group</label>
                                <select name="groupname" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    <option value="">Select a group...</option>
                                    @foreach ($systemGroups as $g)
                                        <option value="{{ $g['name'] }}">
                                            {{ $g['name'] }} (GID {{ $g['gid'] }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Filesystem</label>
                                <select name="filesystem" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    @if (!empty($quotaFilesystems))
                                        @foreach ($quotaFilesystems as $fs)
                                            <option value="{{ $fs['mount'] }}">{{ $fs['mount'] }} ({{ $fs['device'] }})</option>
                                        @endforeach
                                    @else
                                        <option value="">No quota-enabled filesystems found</option>
                                    @endif
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Soft Block Limit (KB)</label>
                                <input type="number" name="block_soft" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0 = unlimited">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hard Block Limit (KB)</label>
                                <input type="number" name="block_hard" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0 = unlimited">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Soft Inode Limit</label>
                                <input type="number" name="inode_soft" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0 = unlimited">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Hard Inode Limit</label>
                                <input type="number" name="inode_hard" min="0" value="0" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="0 = unlimited">
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                Set Quota
                            </button>
                        </div>
                    </form>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Current Group Quotas</h3>
                    @if (!empty($groupQuotas))
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-1.5 pr-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Group</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Used</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Soft</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Hard</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Inodes</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">I-Soft</th>
                                        <th class="text-right py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">I-Hard</th>
                                        <th class="text-left py-1.5 pl-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Grace</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($groupQuotas as $q)
                                        <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                            <td class="py-1.5 pr-2 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $q['name'] }}</td>
                                            <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $formatBlocks($q['blocks_used']) }}</td>
                                            <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['blocks_soft'] > 0 ? $formatBlocks($q['blocks_soft']) : '-' }}</td>
                                            <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['blocks_hard'] > 0 ? $formatBlocks($q['blocks_hard']) : '-' }}</td>
                                            <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ number_format($q['inodes_used']) }}</td>
                                            <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['inodes_soft'] > 0 ? number_format($q['inodes_soft']) : '-' }}</td>
                                            <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['inodes_hard'] > 0 ? number_format($q['inodes_hard']) : '-' }}</td>
                                            <td class="py-1.5 pl-2 text-xs text-gray-500 dark:text-gray-400">{{ $q['blocks_grace'] ?: '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No group quota data available.</p>
                    @endif
                </div>

            @elseif ($tab === 'management')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Quota Service</h3>
                        <div class="space-y-3">
                            <form method="POST" action="{{ route('quota.settings') }}" class="inline-block w-full">
                                @csrf
                                <input type="hidden" name="tab" value="management">
                                <input type="hidden" name="action" value="quotaon">
                                <button type="submit" class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition text-left flex items-center">
                                    <svg class="w-5 h-5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Enable Quotas (quotaon -a)
                                </button>
                            </form>
                            <form method="POST" action="{{ route('quota.settings') }}" class="inline-block w-full"
                                  onsubmit="return confirm('Disable quotas on all filesystems?')">
                                @csrf
                                <input type="hidden" name="tab" value="management">
                                <input type="hidden" name="action" value="quotaoff">
                                <button type="submit" class="w-full px-4 py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition text-left flex items-center">
                                    <svg class="w-5 h-5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                    </svg>
                                    Disable Quotas (quotaoff -a)
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Maintenance</h3>
                        <div class="space-y-3">
                            <form method="POST" action="{{ route('quota.settings') }}" class="inline-block w-full"
                                  onsubmit="return confirm('Run quotacheck? This may take a while on large filesystems.')">
                                @csrf
                                <input type="hidden" name="tab" value="management">
                                <input type="hidden" name="action" value="quotacheck">
                                <button type="submit" class="w-full px-4 py-3 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-md transition text-left flex items-center">
                                    <svg class="w-5 h-5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                                    </svg>
                                    Check & Repair Quotas (quotacheck -augm)
                                </button>
                            </form>
                            <form method="POST" action="{{ route('quota.settings') }}" class="inline-block w-full">
                                @csrf
                                <input type="hidden" name="tab" value="management">
                                <input type="hidden" name="action" value="quotastats">
                                <button type="submit" class="w-full px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition text-left flex items-center">
                                    <svg class="w-5 h-5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                    </svg>
                                    View Quota Statistics
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Quota-Enabled Filesystems</h3>
                        @if (!empty($quotaFilesystems))
                            <div class="space-y-2">
                                @foreach ($quotaFilesystems as $fs)
                                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                        <div>
                                            <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $fs['mount'] }}</p>
                                            <p class="text-xs font-mono text-gray-500 dark:text-gray-400">{{ $fs['device'] }} ({{ $fs['fstype'] }})</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">No filesystems with quota enabled.</p>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Active Quota Users</h3>
                        @php
                            $activeUsers = array_filter($userQuotas, fn($q) => $q['blocks_hard'] > 0 || $q['inodes_hard'] > 0);
                            $activeGroups = array_filter($groupQuotas, fn($q) => $q['blocks_hard'] > 0 || $q['inodes_hard'] > 0);
                        @endphp
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ count($activeUsers) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Users with Limits</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ count($activeGroups) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Groups with Limits</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ count($quotaFilesystems) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Quota Filesystems</p>
                            </div>
                            <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <p class="text-2xl font-bold text-orange-600 dark:text-orange-400">{{ count($systemUsers) }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">System Users (UID>=1000)</p>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif ($tab === 'fstab')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Filesystem Entries</h3>
                        @if (!empty($fstabEntries))
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <th class="text-left py-1.5 pr-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Device</th>
                                            <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Mount</th>
                                            <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                            <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Options</th>
                                            <th class="text-center py-1.5 pl-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quota</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($fstabEntries as $entry)
                                            <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                <td class="py-1.5 pr-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $entry['device'] }}</td>
                                                <td class="py-1.5 px-2 text-xs font-mono text-gray-800 dark:text-gray-200 font-medium">{{ $entry['mount'] }}</td>
                                                <td class="py-1.5 px-2 text-xs text-gray-600 dark:text-gray-400">{{ $entry['fstype'] }}</td>
                                                <td class="py-1.5 px-2 text-xs font-mono text-gray-500 dark:text-gray-500 truncate max-w-[160px]" title="{{ $entry['opts'] }}">{{ $entry['opts'] }}</td>
                                                <td class="py-1.5 pl-2 text-center">
                                                    @if ($entry['has_quota'])
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                                            Enabled
                                                        </span>
                                                        <form method="POST" action="{{ route('quota.settings') }}" class="inline ml-1"
                                                              onsubmit="return confirm('Remove quota options from {{ $entry['mount'] }}?')">
                                                            @csrf
                                                            <input type="hidden" name="tab" value="fstab">
                                                            <input type="hidden" name="action" value="disable-fstab-quota">
                                                            <input type="hidden" name="mount" value="{{ $entry['mount'] }}">
                                                            <input type="hidden" name="device" value="{{ $entry['device'] }}">
                                                            <button type="submit" class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 hover:underline">Disable</button>
                                                        </form>
                                                    @else
                                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                            Disabled
                                                        </span>
                                                        <form method="POST" action="{{ route('quota.settings') }}" class="inline ml-1"
                                                              onsubmit="return confirm('Add quota options (usrquota,grpquota) to {{ $entry['mount'] }}?')">
                                                            @csrf
                                                            <input type="hidden" name="tab" value="fstab">
                                                            <input type="hidden" name="action" value="enable-fstab-quota">
                                                            <input type="hidden" name="mount" value="{{ $entry['mount'] }}">
                                                            <input type="hidden" name="device" value="{{ $entry['device'] }}">
                                                            <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 hover:underline">Enable</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">No fstab entries found.</p>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Apply Changes</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            After modifying fstab entries, apply changes to remount filesystems and activate quotas.
                        </p>
                        <div class="space-y-3">
                            <form method="POST" action="{{ route('quota.settings') }}" class="inline-block w-full">
                                @csrf
                                <input type="hidden" name="tab" value="fstab">
                                <input type="hidden" name="action" value="apply-fstab">
                                <button type="submit" class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition text-left flex items-center"
                                        onclick="return confirm('Remount filesystems, check quotas, and enable quotas?')">
                                    <svg class="w-5 h-5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Apply & Remount All
                                </button>
                            </form>
                            <form method="POST" action="{{ route('quota.settings') }}" class="inline-block w-full">
                                @csrf
                                <input type="hidden" name="tab" value="fstab">
                                <input type="hidden" name="action" value="view-fstab">
                                <button type="submit" class="w-full px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-md transition text-left flex items-center">
                                    <svg class="w-5 h-5 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    View Raw /etc/fstab
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Raw /etc/fstab</h3>
                    <pre class="text-xs font-mono text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-700/50 p-4 rounded-lg overflow-x-auto whitespace-pre-wrap max-h-64">{{ $fstabRaw ?: 'No fstab content available.' }}</pre>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
