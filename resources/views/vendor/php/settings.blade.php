@php
    use App\Actions\Php\PhpConfig;
    use App\Actions\Php\PhpPackageManager;
    use App\Actions\Php\PhpInfo;

    $config = app(PhpConfig::class);
    $pm = app(PhpPackageManager::class);
    $info = app(PhpInfo::class);

    $allSettings = $config->allSettings();
    $mods = $config->modsAvailable();
    $sapiPaths = $config->sapiIniPaths();

    $versionInfo = $pm->installedVersions();
    $currentDefault = $versionInfo['default'];
    $installedVersions = $versionInfo['installed'];
    $extVersion = request('ext_version', $currentDefault);
    $installedPackages = $pm->installedPackages();
    $allExtensions = $pm->allExtensionsStatus($extVersion);
    $fpmInstalled = $pm->fpmInstalled();
    $fpmExtInstalled = $pm->fpmInstalled($extVersion);
    $phpVersion = $info->version();
    $allAvailableVersions = $pm->availableVersions();
    $needsSudo = $pm->needsSudo();
    $tab = request('tab', 'config');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('PHP Settings') }}
            </h2>
            <a href="{{ route('php.dashboard') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
                {{ __('Back to Dashboard') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8">
                    <a href="{{ route('php.settings', ['tab' => 'config']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition
                        {{ $tab === 'config' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Configuration
                    </a>
                    <a href="{{ route('php.settings', ['tab' => 'extensions']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition
                        {{ $tab === 'extensions' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Extensions
                    </a>
                    <a href="{{ route('php.settings', ['tab' => 'package-manager']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition
                        {{ $tab === 'package-manager' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Package Manager
                    </a>
                    <a href="{{ route('php.settings', ['tab' => 'default-version']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition
                        {{ $tab === 'default-version' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Default Version
                    </a>
                    <a href="{{ route('php.settings', ['tab' => 'ini-files']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition
                        {{ $tab === 'ini-files' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        php.ini Files
                    </a>
                </nav>
            </div>

            @if ($tab === 'config')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">PHP Configuration</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Current runtime values for all PHP configuration directives.</p>

                    <div x-data="{ search: '' }">
                        <div class="mb-4">
                            <input x-model="search" type="text" placeholder="Search settings..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="space-y-4">
                            @foreach ($allSettings as $category => $settings)
                                <div x-data="{ open: @js($loop->first) }">
                                    <button @click="open = !open"
                                            class="w-full flex items-center justify-between px-4 py-2 bg-gray-50 dark:bg-gray-700/50 rounded-lg text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $category }}</span>
                                        <svg class="w-5 h-5 text-gray-500 transition" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>
                                    <div x-show="open" class="mt-2 overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead>
                                                <tr class="border-b border-gray-200 dark:border-gray-700">
                                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Directive</th>
                                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Value</th>
                                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($settings as $directive => $setting)
                                                    <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                        <td class="py-2 px-3 font-mono text-xs text-gray-800 dark:text-gray-200">{{ $directive }}</td>
                                                        <td class="py-2 px-3 font-mono text-xs text-gray-600 dark:text-gray-400">{{ $setting['local'] }}</td>
                                                        <td class="py-2 px-3 text-xs text-gray-400">{{ $setting['type'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

            @elseif ($tab === 'extensions')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Installed Extensions</h3>
                            <div class="flex items-center space-x-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400">PHP:</span>
                                <select onchange="window.location.href='{{ route('php.settings', ['tab' => 'extensions']) }}&ext_version=' + this.value"
                                        class="text-xs px-2 py-1 border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                    @foreach ($installedVersions as $v)
                                        <option value="{{ $v }}" {{ $v === $extVersion ? 'selected' : '' }}>PHP {{ $v }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">PHP {{ $extVersion }} extensions — loaded and enabled status by SAPI.</p>

                        @if (!empty($allExtensions))
                            <div class="space-y-2">
                                @foreach ($allExtensions as $ext)
                                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                        <div class="flex items-center space-x-3">
                                            <span class="w-2 h-2 rounded-full {{ $ext['loaded'] ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                            <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $ext['name'] }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2 text-xs">
                                            <span class="{{ $ext['enabled']['cli'] ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">CLI: {{ $ext['enabled']['cli'] ? 'On' : 'Off' }}</span>
                                            <span class="{{ $ext['enabled']['apache2'] ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">Apache: {{ $ext['enabled']['apache2'] ? 'On' : 'Off' }}</span>
                                            <span class="{{ isset($ext['enabled']['fpm']) && $ext['enabled']['fpm'] ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}">
                                                FPM: {{ isset($ext['enabled']['fpm']) && $ext['enabled']['fpm'] ? 'On' : 'Off' }}
                                            </span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">No extension information available for PHP {{ $extVersion }}.</p>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Enable / Disable Extensions</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Toggle extensions on/off for PHP {{ $extVersion }}. Requires passwordless sudo.</p>

                        <form method="POST" action="{{ route('php.settings') }}" class="space-y-3">
                            @csrf
                            <input type="hidden" name="tab" value="extensions">
                            <input type="hidden" name="ext_version" value="{{ $extVersion }}">

                            <div class="space-y-2">
                                <label class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-16">Extension</span>
                                    <select name="extension" class="flex-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                        @foreach ($allExtensions as $ext)
                                            <option value="{{ $ext['name'] }}">{{ $ext['name'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-16">SAPI</span>
                                    <select name="sapi" class="flex-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                        <option value="cli">CLI</option>
                                        <option value="apache2">Apache</option>
                                        @if ($fpmExtInstalled)
                                            <option value="fpm">FPM</option>
                                        @endif
                                    </select>
                                </label>
                                <label class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300 w-16">Version</span>
                                    <select name="version" class="flex-1 px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                        @foreach ($installedVersions as $v)
                                            <option value="{{ $v }}" {{ $v === $extVersion ? 'selected' : '' }}>PHP {{ $v }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            </div>
                            <div class="flex space-x-3">
                                <button type="submit" name="action" value="enable" class="px-4 py-1.5 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition">
                                    Enable
                                </button>
                                <button type="submit" name="action" value="disable" class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition">
                                    Disable
                                </button>
                            </div>
                        </form>

                        @if (session('pm_result'))
                            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <pre class="text-xs font-mono text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ session('pm_result') }}</pre>
                            </div>
                        @endif
                    </div>
                </div>

            @elseif ($tab === 'package-manager')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Installed PHP Packages</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">All PHP-related packages installed on the system via dpkg.</p>

                        @if (!empty($installedPackages))
                            <div class="space-y-1 max-h-96 overflow-y-auto">
                                @foreach ($installedPackages as $pkg)
                                    <div class="flex items-center justify-between py-1.5 px-2 hover:bg-gray-50 dark:hover:bg-gray-700/30 rounded">
                                        <span class="text-sm font-mono text-gray-800 dark:text-gray-200">{{ $pkg['name'] }}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $pkg['version'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">No package information available. Ensure dpkg is accessible.</p>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Package Manager</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Install or remove PHP packages via apt (requires passwordless sudo).</p>

                        <form method="POST" action="{{ route('php.settings') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="tab" value="package-manager">

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Action</label>
                                <select name="pm_action" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                    <option value="install">Install</option>
                                    <option value="remove">Remove</option>
                                    <option value="update-cache">Update Package Cache</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Package Name</label>
                                <input type="text" name="package" placeholder="e.g. php8.4-fpm" class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                <p class="text-xs text-gray-400 mt-1">Package name is only needed for install/remove actions.</p>
                            </div>

                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                Execute
                            </button>
                        </form>

                        @if (! $fpmInstalled)
                            <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                <p class="text-sm text-yellow-700 dark:text-yellow-300 font-medium mb-2">PHP-FPM is not installed</p>
                                <form method="POST" action="{{ route('php.settings') }}">
                                    @csrf
                                    <input type="hidden" name="tab" value="package-manager">
                                    <input type="hidden" name="pm_action" value="install">
                                    <input type="hidden" name="package" value="php8.4-fpm">
                                    <button type="submit" class="px-4 py-1.5 bg-yellow-600 hover:bg-yellow-700 text-white text-sm font-medium rounded-md transition">
                                        Install PHP-FPM
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if (session('pm_result'))
                            <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <pre class="text-xs font-mono text-gray-600 dark:text-gray-400 whitespace-pre-wrap">{{ session('pm_result') }}</pre>
                            </div>
                        @endif
                    </div>
                </div>

            @elseif ($tab === 'default-version')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Default PHP Version</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Current default is <strong class="text-gray-800 dark:text-gray-200">PHP {{ $currentDefault }}</strong></p>
                        </div>
                        <div class="px-4 py-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-center">
                            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ $currentDefault }}</p>
                            <p class="text-xs text-blue-500 dark:text-blue-400">/usr/bin/php</p>
                        </div>
                    </div>

                    @if (! $needsSudo)
                        <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg mb-4">
                            <p class="text-sm text-yellow-700 dark:text-yellow-300">Passwordless sudo is not configured. Version switching requires sudo access.</p>
                        </div>
                    @endif

                    @if (!empty($installedVersions))
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Installed Versions</h4>
                        <div class="space-y-2 mb-6">
                            @foreach ($installedVersions as $version)
                                @php $isCurrent = $version === $currentDefault; @endphp
                                <div class="flex items-center justify-between py-2 px-4 rounded-lg {{ $isCurrent ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-gray-700/30' }}">
                                    <div class="flex items-center space-x-3">
                                        <span class="w-3 h-3 rounded-full {{ $isCurrent ? 'bg-blue-500' : 'bg-gray-400' }}"></span>
                                        <span class="text-sm font-medium {{ $isCurrent ? 'text-blue-800 dark:text-blue-300' : 'text-gray-800 dark:text-gray-200' }}">
                                            PHP {{ $version }}
                                        </span>
                                        @if ($isCurrent)
                                            <span class="text-xs px-2 py-0.5 bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-full font-medium">Active</span>
                                        @endif
                                    </div>
                                    @if (! $isCurrent)
                                        <form method="POST" action="{{ route('php.settings') }}">
                                            @csrf
                                            <input type="hidden" name="tab" value="default-version">
                                            <input type="hidden" name="pm_action" value="switch-version">
                                            <input type="hidden" name="version" value="{{ $version }}">
                                            <button type="submit" class="px-3 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded-md transition">
                                                Switch
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic mb-6">No alternative PHP versions detected via update-alternatives.</p>
                    @endif

                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Install Additional PHP Versions</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Select a version to install its CLI and common packages.</p>

                        @php
                            $installable = array_diff($allAvailableVersions, $installedVersions);
                        @endphp

                        @if (!empty($installable))
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                @foreach ($installable as $version)
                                    <div class="flex items-center justify-between px-3 py-2 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                        <span class="text-sm font-mono text-gray-700 dark:text-gray-300">PHP {{ $version }}</span>
                                        <form method="POST" action="{{ route('php.settings') }}">
                                            @csrf
                                            <input type="hidden" name="tab" value="default-version">
                                            <input type="hidden" name="pm_action" value="install-version">
                                            <input type="hidden" name="version" value="{{ $version }}">
                                            <button type="submit" class="px-2 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded transition">
                                                Install
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-400 dark:text-gray-500 italic">All available PHP versions are already installed.</p>
                        @endif
                    </div>
                </div>

                @if (session('pm_result'))
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Command Result</h4>
                        <pre class="text-xs font-mono text-gray-600 dark:text-gray-400 whitespace-pre-wrap max-h-64 overflow-y-auto">{{ session('pm_result') }}</pre>
                    </div>
                @endif

            @elseif ($tab === 'ini-files')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">PHP .ini Files</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">php.ini files and scanned configuration directories for each SAPI.</p>

                    @foreach ($sapiPaths as $sapi => $paths)
                        <div class="mb-6">
                            <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-2 capitalize">{{ $sapi }}</h4>
                            <div class="space-y-1">
                                @foreach ($paths['files'] as $file)
                                    <div class="flex items-center space-x-2 py-1">
                                        <span class="w-2 h-2 rounded-full {{ $file['exists'] && $file['readable'] ? 'bg-green-500' : ($file['exists'] ? 'bg-yellow-500' : 'bg-red-500') }}"></span>
                                        <span class="text-sm font-mono text-gray-600 dark:text-gray-400">{{ $file['path'] }}</span>
                                        @if (!$file['exists'])
                                            <span class="text-xs text-red-500">(not found)</span>
                                        @elseif (!$file['readable'])
                                            <span class="text-xs text-yellow-500">(not readable)</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300 mb-2">Available Modules (mods-available)</h4>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">{{ count($mods) }} module ini files available</p>
                        @if (!empty($mods))
                            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                                @foreach ($mods as $mod)
                                    <div class="flex items-center space-x-2 px-3 py-2 bg-gray-50 dark:bg-gray-700/30 rounded-lg">
                                        <span class="w-2 h-2 rounded-full {{ $mod['enabled']['cli'] || $mod['enabled']['apache2'] || $mod['enabled']['fpm'] ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                        <span class="text-sm font-mono text-gray-700 dark:text-gray-300">{{ $mod['name'] }}</span>
                                        <div class="flex space-x-1 ml-auto">
                                            @if ($mod['enabled']['cli'])
                                                <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">cli</span>
                                            @endif
                                            @if ($mod['enabled']['apache2'])
                                                <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">apache2</span>
                                            @endif
                                            @if ($mod['enabled']['fpm'])
                                                <span class="text-xs px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-600 text-gray-600 dark:text-gray-300">fpm</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
