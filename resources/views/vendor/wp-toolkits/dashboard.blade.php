@php
    $wpCli = app(\App\Actions\Wp\WpCli::class);
    $wpStatus = $wpCli->installed();
    $sites = \App\Models\WpSite::all();
    $sitesInfo = [];
    foreach ($sites as $site) {
        $info = $wpStatus['installed'] ? $wpCli->siteInfo($site->path) : ['error' => 'WP-CLI not installed'];
        $sitesInfo[] = [
            'site' => $site,
            'info' => $info,
        ];
    }
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('WP Toolkits Dashboard') }}
            </h2>
            <a href="{{ route('wp-toolkits.management') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
                {{ __('Manage') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (!$wpStatus['installed'])
                <div class="mb-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-yellow-500 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <span class="text-yellow-700 dark:text-yellow-300 text-sm font-medium">WP-CLI is not installed</span>
                        </div>
                        <a href="{{ route('wp-toolkits.management', ['tab' => 'wp-cli']) }}" class="px-4 py-1.5 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-md transition">
                            Install WP-CLI
                        </a>
                    </div>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">WP-CLI</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                {{ $wpStatus['installed'] ? $wpStatus['version'] : 'Not Installed' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 dark:bg-green-900/30">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Sites</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ $sites->count() }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900/30">
                            <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Plugins</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                {{ $wpStatus['installed'] && $sites->count() > 0 ? 'Click site to view' : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-orange-100 dark:bg-orange-900/30">
                            <svg class="w-6 h-6 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Themes</p>
                            <p class="text-lg font-semibold text-gray-800 dark:text-gray-200">
                                {{ $wpStatus['installed'] && $sites->count() > 0 ? 'Click site to view' : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            @if ($sites->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Registered Sites</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Path</th>
                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Core Version</th>
                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Plugins</th>
                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Themes</th>
                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($sitesInfo as $entry)
                                    @php
                                        $site = $entry['site'];
                                        $info = $entry['info'];
                                        $plugins = isset($info['plugins']['success']) && $info['plugins']['success'] ? json_decode($info['plugins']['output'], true) : [];
                                        $themes = isset($info['themes']['success']) && $info['themes']['success'] ? json_decode($info['themes']['output'], true) : [];
                                        $coreVer = isset($info['core']['success']) && $info['core']['success'] ? $info['core']['output'] : 'N/A';
                                    @endphp
                                    <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="py-2 px-3 font-medium text-gray-800 dark:text-gray-200">{{ $site->name }}</td>
                                        <td class="py-2 px-3 font-mono text-xs text-gray-600 dark:text-gray-400">{{ $site->path }}</td>
                                        <td class="py-2 px-3 text-sm text-gray-800 dark:text-gray-200">{{ $coreVer }}</td>
                                        <td class="py-2 px-3 text-sm text-gray-800 dark:text-gray-200">{{ is_array($plugins) ? count($plugins) : 'N/A' }}</td>
                                        <td class="py-2 px-3 text-sm text-gray-800 dark:text-gray-200">{{ is_array($themes) ? count($themes) : 'N/A' }}</td>
                                        <td class="py-2 px-3">
                                            <a href="{{ route('wp-toolkits.management', ['tab' => 'plugins', 'site' => $site->id]) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-medium mr-2">Plugins</a>
                                            <a href="{{ route('wp-toolkits.management', ['tab' => 'themes', 'site' => $site->id]) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-medium mr-2">Themes</a>
                                            <a href="{{ route('wp-toolkits.management', ['tab' => 'users', 'site' => $site->id]) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-medium mr-2">Users</a>
                                            <a href="{{ route('wp-toolkits.management', ['tab' => 'database', 'site' => $site->id]) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-xs font-medium">DB</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-center py-8">
                        <svg class="w-16 h-16 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-500 dark:text-gray-400 mb-2">No WordPress Sites</h3>
                        <p class="text-sm text-gray-400 dark:text-gray-500 mb-4">Add your first WordPress site to get started.</p>
                        <a href="{{ route('wp-toolkits.management', ['tab' => 'sites']) }}" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                            Add Site
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
