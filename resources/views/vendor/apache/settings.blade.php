@php
    use App\Actions\Apache\VhostManager;

    $vm = app(VhostManager::class);

    $vhosts = $vm->listVhosts();
    $modules = $vm->listModules();
    $certs = $vm->listCertificates();
    $apacheStatus = $vm->status();
    $needsSudo = $vm->needsSudo();
    $tab = request('tab', 'vhosts');
    $result = session('apache_result');
    $error = session('apache_error');

    $editVhost = request('edit');
    $editData = $editVhost ? $vm->getVhost($editVhost) : null;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Apache Settings') }}
            </h2>
            <a href="{{ route('apache.dashboard') }}" class="inline-flex items-center px-3 py-1.5 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
                {{ __('Back to Dashboard') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="mb-6 border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-8 overflow-x-auto">
                    <a href="{{ route('apache.settings', ['tab' => 'vhosts']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'vhosts' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Virtual Hosts
                    </a>
                    <a href="{{ route('apache.settings', ['tab' => 'modules']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'modules' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Modules
                    </a>
                    <a href="{{ route('apache.settings', ['tab' => 'actions']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'actions' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        Actions
                    </a>
                    <a href="{{ route('apache.settings', ['tab' => 'ssl']) }}"
                       class="pb-3 px-1 text-sm font-medium border-b-2 transition whitespace-nowrap
                        {{ $tab === 'ssl' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300' }}">
                        SSL Certificates
                    </a>
                </nav>
            </div>

            @if ($result)
                <div class="mb-6 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Command Result</h4>
                    <pre class="text-xs font-mono text-gray-600 dark:text-gray-400 whitespace-pre-wrap max-h-96 overflow-y-auto">{{ $result }}</pre>
                </div>
            @endif

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

            @if ($tab === 'vhosts')
                <div class="grid grid-cols-1 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Virtual Hosts</h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($vhosts) }} config(s)</span>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Virtual host configuration files from <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">{{ $vm->sitesAvailable ?? '/etc/apache2/sites-available' }}</code>.
                        </p>

                        @if (! empty($vhosts))
                            <div class="overflow-x-auto">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-700">
                                            <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Name</th>
                                            <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">ServerName</th>
                                            <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">DocumentRoot</th>
                                            <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Port</th>
                                            <th class="text-center py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">SSL</th>
                                            <th class="text-center py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Status</th>
                                            <th class="text-right py-2 px-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($vhosts as $vhost)
                                            @php
                                                $firstVhost = $vhost['parsed']['vhosts'][0] ?? null;
                                                $serverName = $firstVhost['servername'] ?? $vhost['name'];
                                                $docRoot = $firstVhost['documentroot'] ?? 'N/A';
                                                $address = $firstVhost['address'] ?? '*:80';
                                                $hasSsl = ! empty($vhost['parsed']['vhosts']) && collect($vhost['parsed']['vhosts'])->contains('ssl', true);
                                                $port = '80';
                                                if (preg_match('/:(\d+)$/', $address, $m)) {
                                                    $port = $m[1];
                                                }
                                            @endphp
                                            <tr class="border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                <td class="py-2 px-3 font-mono text-xs text-gray-800 dark:text-gray-200">{{ $vhost['name'] }}</td>
                                                <td class="py-2 px-3 text-xs text-gray-600 dark:text-gray-400">{{ $serverName }}</td>
                                                <td class="py-2 px-3 text-xs font-mono text-gray-600 dark:text-gray-400 max-w-[200px] truncate" title="{{ $docRoot }}">{{ $docRoot }}</td>
                                                <td class="py-2 px-3 text-xs text-gray-600 dark:text-gray-400">{{ $port }}</td>
                                                <td class="py-2 px-3 text-center">
                                                    @if ($hasSsl)
                                                        <span class="text-xs px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full font-medium">SSL</span>
                                                    @else
                                                        <span class="text-xs text-gray-400 dark:text-gray-500">-</span>
                                                    @endif
                                                </td>
                                                <td class="py-2 px-3 text-center">
                                                    @if ($vhost['enabled'])
                                                        <span class="text-xs px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded-full font-medium">Enabled</span>
                                                    @else
                                                        <span class="text-xs px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded-full font-medium">Disabled</span>
                                                    @endif
                                                </td>
                                                <td class="py-2 px-3 text-right">
                                                    <div class="flex items-center justify-end space-x-1">
                                                        <form method="POST" action="{{ route('apache.settings') }}" class="inline">
                                                            @csrf
                                                            <input type="hidden" name="tab" value="vhosts">
                                                            <input type="hidden" name="vhost" value="{{ $vhost['name'] }}">
                                                            @if ($vhost['enabled'])
                                                                <input type="hidden" name="action" value="disable-site">
                                                                <button type="submit" class="px-2 py-1 text-xs bg-yellow-500 hover:bg-yellow-600 text-white rounded transition" title="Disable">
                                                                    Disable
                                                                </button>
                                                            @else
                                                                <input type="hidden" name="action" value="enable-site">
                                                                <button type="submit" class="px-2 py-1 text-xs bg-green-600 hover:bg-green-700 text-white rounded transition" title="Enable">
                                                                    Enable
                                                                </button>
                                                            @endif
                                                        </form>
                                                        <a href="{{ route('apache.settings', ['tab' => 'vhosts', 'edit' => $vhost['name']]) }}"
                                                           class="px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                                                            Edit
                                                        </a>
                                                        <form method="POST" action="{{ route('apache.settings') }}" class="inline"
                                                              onsubmit="return confirm('Delete vhost \'{{ $vhost['name'] }}\'? This cannot be undone.')">
                                                            @csrf
                                                            <input type="hidden" name="tab" value="vhosts">
                                                            <input type="hidden" name="vhost" value="{{ $vhost['name'] }}">
                                                            <input type="hidden" name="action" value="delete-vhost">
                                                            <button type="submit" class="px-2 py-1 text-xs bg-red-600 hover:bg-red-700 text-white rounded transition">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic mb-4">No virtual host configuration files found.</p>
                            </div>
                        @endif
                    </div>

                    @if ($editData)
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Edit Vhost: {{ $editData['name'] }}</h3>
                            <form method="POST" action="{{ route('apache.settings') }}">
                                @csrf
                                <input type="hidden" name="tab" value="vhosts">
                                <input type="hidden" name="vhost" value="{{ $editData['name'] }}">
                                <input type="hidden" name="action" value="update-vhost">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Configuration</label>
                                    <textarea name="content" rows="20" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm font-mono text-gray-800 dark:text-gray-200">{{ $editData['content'] }}</textarea>
                                </div>
                                <div class="flex space-x-3">
                                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                        Save Changes
                                    </button>
                                    <a href="{{ route('apache.settings', ['tab' => 'vhosts']) }}" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-sm font-medium rounded-md transition">
                                        Cancel
                                    </a>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Create Virtual Host</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Generate a new Apache virtual host configuration file.</p>

                            <form method="POST" action="{{ route('apache.settings') }}" class="space-y-4">
                                @csrf
                                <input type="hidden" name="tab" value="vhosts">
                                <input type="hidden" name="action" value="create-vhost">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Config Name <span class="text-red-500">*</span></label>
                                        <input type="text" name="name" placeholder="e.g. example.com" required
                                               class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                        <p class="text-xs text-gray-400 mt-1">Filename without .conf extension.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ServerName <span class="text-red-500">*</span></label>
                                        <input type="text" name="server_name" placeholder="e.g. example.com" required
                                               class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ServerAdmin</label>
                                        <input type="email" name="server_admin" placeholder="admin@example.com"
                                               class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ServerAlias</label>
                                        <input type="text" name="server_alias" placeholder="www.example.com, *.example.com"
                                               class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                        <p class="text-xs text-gray-400 mt-1">Comma-separated aliases.</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">DocumentRoot</label>
                                        <input type="text" name="document_root" placeholder="/var/www/example.com/public"
                                               class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                                    </div>
                                    <div>
                                        <label class="flex items-center space-x-2 pt-6">
                                            <input type="checkbox" name="ssl" value="1"
                                                   class="rounded border-gray-300 dark:border-gray-600 text-blue-600 shadow-sm focus:ring-blue-500">
                                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Include SSL vhost (port 443)</span>
                                        </label>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Extra Configuration</label>
                                    <textarea name="extra_config" rows="4" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm font-mono text-gray-800 dark:text-gray-200" placeholder="Additional directives to include in the vhost block (e.g., ProxyPass rules)"></textarea>
                                </div>

                                <button type="submit" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md transition">
                                    Create Vhost
                                </button>
                            </form>
                        </div>
                    @endif
                </div>

            @elseif ($tab === 'modules')
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Apache Modules</h3>
                        <span class="text-sm text-gray-500 dark:text-gray-400">{{ count($modules) }} total</span>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Enable or disable Apache modules using <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">a2enmod</code> and <code class="text-xs bg-gray-100 dark:bg-gray-700 px-1 py-0.5 rounded">a2dismod</code>.</p>

                    @if (! empty($modules))
                        <div x-data="{ search: '' }">
                            <div class="mb-4">
                                <input x-model="search" type="text" placeholder="Search modules..." class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach ($modules as $mod)
                                    <div x-show="!search || '{{ $mod['name'] }}'.includes(search.toLowerCase()) || '{{ $mod['description'] }}'.toLowerCase().includes(search.toLowerCase())"
                                         class="flex items-center justify-between px-3 py-2 rounded-lg {{ $mod['enabled'] ? 'bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-700/30' }}">
                                        <div class="flex items-center space-x-3 min-w-0">
                                            <span class="w-2 h-2 rounded-full shrink-0 {{ $mod['enabled'] ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                                            <div class="min-w-0">
                                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200 block truncate">{{ $mod['name'] }}</span>
                                                @if ($mod['description'])
                                                    <span class="text-xs text-gray-500 dark:text-gray-400 block truncate">{{ $mod['description'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('apache.settings') }}" class="shrink-0 ml-2">
                                            @csrf
                                            <input type="hidden" name="tab" value="modules">
                                            <input type="hidden" name="module" value="{{ $mod['name'] }}">
                                            <input type="hidden" name="action" value="{{ $mod['enabled'] ? 'disable-module' : 'enable-module' }}">
                                            <button type="submit" class="px-2 py-1 text-xs rounded transition {{ $mod['enabled'] ? 'bg-red-500 hover:bg-red-600 text-white' : 'bg-green-600 hover:bg-green-700 text-white' }}">
                                                {{ $mod['enabled'] ? 'Disable' : 'Enable' }}
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-400 dark:text-gray-500 italic">No module information available. Ensure Apache is installed.</p>
                    @endif
                </div>

            @elseif ($tab === 'actions')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Apache Control</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Test configuration, reload, or restart Apache.</p>

                        @if (! $needsSudo)
                            <div class="p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg mb-4">
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">Passwordless sudo is not configured. Reload and restart require sudo access.</p>
                            </div>
                        @endif

                        <div class="space-y-3">
                            <form method="POST" action="{{ route('apache.settings') }}">
                                @csrf
                                <input type="hidden" name="tab" value="actions">
                                <input type="hidden" name="action" value="test-config">
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                    Test Configuration
                                </button>
                            </form>

                            <form method="POST" action="{{ route('apache.settings') }}" onsubmit="return confirm('Reload Apache? This will cause a brief interruption.')">
                                @csrf
                                <input type="hidden" name="tab" value="actions">
                                <input type="hidden" name="action" value="reload">
                                <button type="submit" class="w-full px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded-md transition">
                                    Reload Apache
                                </button>
                            </form>

                            <form method="POST" action="{{ route('apache.settings') }}" onsubmit="return confirm('Restart Apache? This will cause a brief interruption.')">
                                @csrf
                                <input type="hidden" name="tab" value="actions">
                                <input type="hidden" name="action" value="restart">
                                <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md transition">
                                    Restart Apache
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Server Status</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between py-2">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Version</span>
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $apacheStatus['version'] }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-t border-gray-100 dark:border-gray-700">
                                <span class="text-sm text-gray-500 dark:text-gray-400">MPM</span>
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $apacheStatus['mpm'] }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-t border-gray-100 dark:border-gray-700">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Service Status</span>
                                <span class="text-sm font-medium">
                                    @if ($apacheStatus['active'] === 'active')
                                        <span class="text-green-600 dark:text-green-400">Active</span>
                                    @else
                                        <span class="text-red-600 dark:text-red-400">{{ $apacheStatus['active'] }}</span>
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-between py-2 border-t border-gray-100 dark:border-gray-700">
                                <span class="text-sm text-gray-500 dark:text-gray-400">Loaded Modules</span>
                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $apacheStatus['module_count'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            @elseif ($tab === 'ssl')
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">SSL Certificates</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Detected SSL certificates from Let's Encrypt and self-signed sources.</p>

                        @if (! empty($certs))
                            <div class="space-y-3">
                                @foreach ($certs as $cert)
                                    <div class="p-3 rounded-lg {{ $cert['type'] === 'letsencrypt' ? 'bg-green-50 dark:bg-green-900/10 border border-green-200 dark:border-green-800' : 'bg-gray-50 dark:bg-gray-700/30' }}">
                                        <div class="flex items-center justify-between mb-2">
                                            <div class="flex items-center space-x-2">
                                                <span class="w-2 h-2 rounded-full {{ $cert['type'] === 'letsencrypt' ? 'bg-green-500' : 'bg-blue-500' }}"></span>
                                                <span class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $cert['domain'] }}</span>
                                            </div>
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $cert['type'] === 'letsencrypt' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300' }}">
                                                {{ $cert['type'] === 'letsencrypt' ? 'Let\'s Encrypt' : 'Self-Signed' }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 space-y-1">
                                            @if ($cert['valid_to'])
                                                @php
                                                    $expiry = \Carbon\Carbon::createFromTimestamp($cert['valid_to']);
                                                    $daysLeft = now()->diffInDays($expiry, false);
                                                @endphp
                                                <div class="flex justify-between">
                                                    <span>Expires</span>
                                                    <span class="{{ $daysLeft < 30 ? 'text-red-500 font-medium' : '' }}">{{ $expiry->format('M d, Y') }} ({{ round($daysLeft) }} days)</span>
                                                </div>
                                            @endif
                                            <div class="flex justify-between">
                                                <span>Issuer</span>
                                                <span>{{ $cert['issuer'] }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span>Certificate</span>
                                                <span class="font-mono">{{ $cert['cert_file'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <p class="text-sm text-gray-400 dark:text-gray-500 italic">No SSL certificates detected.</p>
                            </div>
                        @endif
                    </div>

                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Generate Self-Signed Certificate</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Create a self-signed SSL certificate for development or internal use.</p>

                        <form method="POST" action="{{ route('apache.settings') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="tab" value="ssl">
                            <input type="hidden" name="action" value="generate-cert">

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Domain <span class="text-red-500">*</span></label>
                                <input type="text" name="domain" placeholder="example.com" required
                                       class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                                <input type="email" name="admin_email" placeholder="admin@example.com"
                                       class="w-full px-3 py-1.5 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm text-gray-800 dark:text-gray-200">
                            </div>

                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition">
                                Generate Certificate
                            </button>
                        </form>

                        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Let's Encrypt</h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-3">For production certificates, use Certbot to obtain Let's Encrypt certificates.</p>
                            <pre class="text-xs font-mono text-gray-600 dark:text-gray-400 whitespace-pre-wrap bg-gray-50 dark:bg-gray-700/50 p-3 rounded-lg">sudo certbot --apache -d example.com -d www.example.com</pre>
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
