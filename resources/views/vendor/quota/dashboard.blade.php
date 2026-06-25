@php
    $error = null;
    $userQuotas = [];
    $groupQuotas = [];
    $quotaStats = [];
    $quotaFilesystems = [];

    try {
        $repqUser = @shell_exec('repquota -au 2>/dev/null');
        if ($repqUser) {
            $lines = explode("\n", trim($repqUser));
            $inTable = false;
            foreach ($lines as $line) {
                if (preg_match('/^#\s+Block\s+limits/i', $line)) {
                    $inTable = true;
                    continue;
                }
                if ($inTable && preg_match('/^\*\*\s+(.+?)\s+--\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $line, $m)) {
                    $userQuotas[] = [
                        'name' => trim($m[1]),
                        'blocks_used' => (int) $m[2],
                        'blocks_soft' => (int) $m[3],
                        'blocks_hard' => (int) $m[4],
                        'blocks_grace' => trim($m[5]),
                        'inodes_used' => (int) $m[6],
                        'inodes_soft' => (int) $m[7],
                        'inodes_hard' => (int) $m[8],
                        'inodes_grace' => trim($m[9] ?? ''),
                    ];
                }
            }
        }
    } catch (\Exception $e) {
        $error = 'Unable to read user quota information.';
    }

    try {
        $repqGroup = @shell_exec('repquota -ag 2>/dev/null');
        if ($repqGroup) {
            $lines = explode("\n", trim($repqGroup));
            $inTable = false;
            foreach ($lines as $line) {
                if (preg_match('/^#\s+Block\s+limits/i', $line)) {
                    $inTable = true;
                    continue;
                }
                if ($inTable && preg_match('/^\*\*\s+(.+?)\s+--\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $line, $m)) {
                    $groupQuotas[] = [
                        'name' => trim($m[1]),
                        'blocks_used' => (int) $m[2],
                        'blocks_soft' => (int) $m[3],
                        'blocks_hard' => (int) $m[4],
                        'blocks_grace' => trim($m[5]),
                        'inodes_used' => (int) $m[6],
                        'inodes_soft' => (int) $m[7],
                        'inodes_hard' => (int) $m[8],
                        'inodes_grace' => trim($m[9] ?? ''),
                    ];
                }
            }
        }
    } catch (\Exception $e) {
        if (! $error) {
            $error = 'Unable to read group quota information.';
        }
    }

    try {
        $stats = @shell_exec('quotastats 2>/dev/null');
        if ($stats) {
            $lines = explode("\n", trim($stats));
            foreach ($lines as $line) {
                if (str_contains($line, ':')) {
                    [$key, $value] = explode(':', $line, 2);
                    $quotaStats[trim($key)] = trim($value);
                }
            }
        }
    } catch (\Exception $e) {
        $quotaStats = [];
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
                            'opts' => $parts[3],
                        ];
                    }
                }
            }
        }
    } catch (\Exception $e) {
        $quotaFilesystems = [];
    }

    $overSoft = 0;
    $overHard = 0;
    $totalWithQuota = 0;
    foreach ($userQuotas as $q) {
        if ($q['blocks_hard'] > 0 || $q['inodes_hard'] > 0) {
            $totalWithQuota++;
        }
        if ($q['blocks_hard'] > 0 && $q['blocks_used'] > $q['blocks_hard']) {
            $overHard++;
        } elseif ($q['blocks_soft'] > 0 && $q['blocks_used'] > $q['blocks_soft']) {
            $overSoft++;
        }
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

    $getLimitBadge = function ($used, $soft, $hard) {
        if ($hard > 0 && $used >= $hard) {
            return ['bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300', 'Exceeded'];
        }
        if ($soft > 0 && $used >= $soft) {
            return ['bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300', 'Over Soft'];
        }
        if ($hard > 0 || $soft > 0) {
            return ['bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300', 'Within'];
        }
        return ['bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400', 'Unlimited'];
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Quota Dashboard') }}
            </h2>
            <a href="{{ route('quota.settings') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
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
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Quota Filesystems</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ count($quotaFilesystems) }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Users with Quotas</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $totalWithQuota }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 dark:bg-yellow-900/30">
                            <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Over Soft Limit</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $overSoft }}</p>
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
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Over Hard Limit</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $overHard }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Quota-Enabled Filesystems</h3>
                    @if (!empty($quotaFilesystems))
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-gray-200 dark:border-gray-700">
                                        <th class="text-left py-1.5 pr-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Device</th>
                                        <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Mount</th>
                                        <th class="text-left py-1.5 px-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                        <th class="text-left py-1.5 pl-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Options</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($quotaFilesystems as $fs)
                                        <tr class="border-b border-gray-100 dark:border-gray-700/50">
                                            <td class="py-1.5 pr-2 text-xs font-mono text-gray-600 dark:text-gray-400">{{ $fs['device'] }}</td>
                                            <td class="py-1.5 px-2 text-xs font-mono text-gray-800 dark:text-gray-200 font-medium">{{ $fs['mount'] }}</td>
                                            <td class="py-1.5 px-2 text-xs text-gray-600 dark:text-gray-400">{{ $fs['fstype'] }}</td>
                                            <td class="py-1.5 pl-2 text-xs font-mono text-gray-500 dark:text-gray-500 truncate max-w-[200px]" title="{{ $fs['opts'] }}">{{ $fs['opts'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No filesystems with quota enabled found.</p>
                    @endif
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Quota Statistics</h3>
                    @if (!empty($quotaStats))
                        <dl class="space-y-2">
                            @php $relevantKeys = ['Kernel version', 'Dquot version', 'Number of dquots', 'Number of dquots in use', 'Number of dquots on free list', 'Number of allocated dquots hash entries', 'Number of warnings'] @endphp
                            @foreach ($relevantKeys as $key)
                                @if (isset($quotaStats[$key]))
                                    <div class="flex justify-between py-1 {{ !$loop->first ? 'border-t border-gray-100 dark:border-gray-700' : '' }}">
                                        <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $key }}</dt>
                                        <dd class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $quotaStats[$key] }}</dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No quota statistics available.</p>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">User Quotas</h3>
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
                                    <th class="text-left py-1.5 pl-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($userQuotas as $q)
                                    @php
                                        $badge = $getLimitBadge($q['blocks_used'], $q['blocks_soft'], $q['blocks_hard']);
                                    @endphp
                                    <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="py-1.5 pr-2 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $q['name'] }}</td>
                                        <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $formatBlocks($q['blocks_used']) }}</td>
                                        <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['blocks_soft'] > 0 ? $formatBlocks($q['blocks_soft']) : '-' }}</td>
                                        <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['blocks_hard'] > 0 ? $formatBlocks($q['blocks_hard']) : '-' }}</td>
                                        <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ number_format($q['inodes_used']) }}</td>
                                        <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['inodes_soft'] > 0 ? number_format($q['inodes_soft']) : '-' }}</td>
                                        <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['inodes_hard'] > 0 ? number_format($q['inodes_hard']) : '-' }}</td>
                                        <td class="py-1.5 pl-2">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $badge[0] }}">
                                                {{ $badge[1] }}
                                            </span>
                                            @if ($q['blocks_grace'])
                                                <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">{{ $q['blocks_grace'] }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">No user quota data available. Quotas may not be configured on this system.</p>
                @endif
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Group Quotas</h3>
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
                                    <th class="text-left py-1.5 pl-2 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($groupQuotas as $q)
                                    @php
                                        $badge = $getLimitBadge($q['blocks_used'], $q['blocks_soft'], $q['blocks_hard']);
                                    @endphp
                                    <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="py-1.5 pr-2 text-sm font-medium text-gray-800 dark:text-gray-200">{{ $q['name'] }}</td>
                                        <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $formatBlocks($q['blocks_used']) }}</td>
                                        <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['blocks_soft'] > 0 ? $formatBlocks($q['blocks_soft']) : '-' }}</td>
                                        <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['blocks_hard'] > 0 ? $formatBlocks($q['blocks_hard']) : '-' }}</td>
                                        <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ number_format($q['inodes_used']) }}</td>
                                        <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['inodes_soft'] > 0 ? number_format($q['inodes_soft']) : '-' }}</td>
                                        <td class="py-1.5 px-2 text-right text-xs font-mono text-gray-600 dark:text-gray-400">{{ $q['inodes_hard'] > 0 ? number_format($q['inodes_hard']) : '-' }}</td>
                                        <td class="py-1.5 pl-2">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $badge[0] }}">
                                                {{ $badge[1] }}
                                            </span>
                                            @if ($q['blocks_grace'])
                                                <span class="text-xs text-gray-400 dark:text-gray-500 ml-1">{{ $q['blocks_grace'] }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-400 dark:text-gray-500 italic">No group quota data available. Quotas may not be configured on this system.</p>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
