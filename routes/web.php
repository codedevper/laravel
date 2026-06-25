<?php

use App\Actions\Apache\VhostManager;
use App\Actions\Php\PhpPackageManager;
use App\Actions\Wp\WpCli;
use App\Models\WpSite;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/wp-toolkits/dashboard', function () {
        return view('vendor.wp-toolkits.dashboard');
    })->name('wp-toolkits.dashboard');

    Route::match(['get', 'post'], '/wp-toolkits/management', function () {
        if (request()->isMethod('post')) {
            $wp = app(WpCli::class);
            $tab = request('tab', 'wp-cli');
            $action = request('action');
            $siteId = request('site_id');
            $site = $siteId ? WpSite::find($siteId) : null;
            $result = null;

            try {
                if ($action === 'install-wp-cli') {
                    $r = $wp->install();
                } elseif ($action === 'update-wp-cli') {
                    $r = $wp->update();
                } elseif ($action === 'run-command') {
                    $cmd = request('command');
                    if ($site) {
                        $r = $wp->command($site->path, $cmd);
                    } else {
                        $r = $wp->command('', $cmd);
                    }
                } elseif ($action === 'add-site') {
                    WpSite::create([
                        'name' => request('name'),
                        'path' => request('path'),
                    ]);
                    $r = ['success' => true, 'output' => 'Site added successfully.'];
                } elseif ($action === 'remove-site') {
                    WpSite::destroy($siteId);
                    $r = ['success' => true, 'output' => 'Site removed successfully.'];
                } elseif ($action === 'update-core' && $site) {
                    $r = $wp->coreUpdate($site->path);
                } elseif ($action === 'plugin-activate' && $site) {
                    $r = $wp->pluginActivate($site->path, request('slug'));
                } elseif ($action === 'plugin-deactivate' && $site) {
                    $r = $wp->pluginDeactivate($site->path, request('slug'));
                } elseif ($action === 'plugin-update' && $site) {
                    $r = $wp->pluginUpdate($site->path, request('slug'));
                } elseif ($action === 'plugin-update-all' && $site) {
                    $r = $wp->pluginUpdate($site->path);
                } elseif ($action === 'plugin-install' && $site) {
                    $r = $wp->pluginInstall($site->path, request('slug'));
                } elseif ($action === 'theme-activate' && $site) {
                    $r = $wp->themeActivate($site->path, request('slug'));
                } elseif ($action === 'user-create' && $site) {
                    $r = $wp->userCreate(
                        $site->path,
                        request('username'),
                        request('email'),
                        request('role', 'subscriber'),
                        request('password') ?: null,
                    );
                } elseif ($action === 'db-optimize' && $site) {
                    $r = $wp->dbOptimize($site->path);
                } elseif ($action === 'db-repair' && $site) {
                    $r = $wp->dbRepair($site->path);
                } elseif ($action === 'db-export' && $site) {
                    $r = $wp->dbExport($site->path);
                }

                if (isset($r)) {
                    session()->flash('wp_result', $r['output'] ?? json_encode($r));
                }
            } catch (Exception $e) {
                session()->flash('wp_error', $e->getMessage());
            }

            return redirect()->route('wp-toolkits.management', ['tab' => $tab, 'site' => $siteId]);
        }

        return view('vendor.wp-toolkits.management');
    })->name('wp-toolkits.management');
    Route::get('/system/dashboard', function () {
        return view('vendor.system.dashboard');
    })->name('system.dashboard');

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/apache/dashboard', function () {
        return view('vendor.apache.dashboard');
    })->name('apache.dashboard');

    Route::match(['get', 'post'], '/apache/settings', function () {
        if (request()->isMethod('post')) {
            $vm = app(VhostManager::class);
            $tab = request('tab', 'vhosts');
            $action = request('action');
            $vhost = request('vhost');
            $result = null;

            try {
                if ($action === 'create-vhost') {
                    $r = $vm->createVhost(request()->all());
                } elseif ($action === 'update-vhost' && $vhost) {
                    $r = $vm->updateVhost($vhost, ['content' => request('content')]);
                } elseif ($action === 'delete-vhost' && $vhost) {
                    $r = $vm->deleteVhost($vhost);
                } elseif ($action === 'enable-site' && $vhost) {
                    $r = $vm->enableVhost($vhost);
                } elseif ($action === 'disable-site' && $vhost) {
                    $r = $vm->disableVhost($vhost);
                } elseif ($action === 'enable-module') {
                    $r = $vm->enableModule(request('module'));
                } elseif ($action === 'disable-module') {
                    $r = $vm->disableModule(request('module'));
                } elseif ($action === 'test-config') {
                    $r = $vm->testConfig();
                } elseif ($action === 'reload') {
                    $r = $vm->reload();
                } elseif ($action === 'restart') {
                    $r = $vm->restart();
                } elseif ($action === 'generate-cert') {
                    $r = $vm->generateSelfSignedCert(request('domain'), request('admin_email', 'admin@localhost'));
                }

                if (isset($r)) {
                    session()->flash('apache_result', json_encode($r, JSON_PRETTY_PRINT));
                }
            } catch (Exception $e) {
                session()->flash('apache_error', $e->getMessage());
            }

            return redirect()->route('apache.settings', ['tab' => $tab, 'edit' => request('edit')]);
        }

        return view('vendor.apache.settings');
    })->name('apache.settings');

    Route::get('/php/dashboard', function () {
        return view('vendor.php.dashboard');
    })->name('php.dashboard');

    Route::match(['get', 'post'], '/php/settings', function () {
        if (request()->isMethod('post')) {
            $pm = app(PhpPackageManager::class);
            $tab = request('tab', 'config');
            $result = null;

            if (request('action') === 'enable') {
                $result = $pm->enableExtension(request('extension'), request('sapi', 'cli'), request('version'));
            } elseif (request('action') === 'disable') {
                $result = $pm->disableExtension(request('extension'), request('sapi', 'cli'), request('version'));
            } elseif (request('pm_action') === 'install') {
                $result = $pm->install(request('package'));
            } elseif (request('pm_action') === 'remove') {
                $result = $pm->remove(request('package'));
            } elseif (request('pm_action') === 'update-cache') {
                $result = $pm->updatePackageCache();
            } elseif (request('pm_action') === 'switch-version') {
                $result = $pm->switchVersion(request('version'));
            } elseif (request('pm_action') === 'install-version') {
                $result = $pm->installVersionPackage(request('version'));
            }

            if ($result) {
                session()->flash('pm_result', json_encode($result, JSON_PRETTY_PRINT));
            }

            return redirect()->route('php.settings', ['tab' => $tab]);
        }

        return view('vendor.php.settings');
    })->name('php.settings');

    Route::get('/node/dashboard', function () {
        return view('vendor.node.dashboard');
    })->name('node.dashboard');

    Route::match(['get', 'post'], '/node/settings', function () {
        if (request()->isMethod('post')) {
            $tab = request('tab', 'general');
            $action = request('action');
            $result = null;

            try {
                if ($action === 'install-version') {
                    $version = escapeshellarg(request('version'));
                    $nvmDir = getenv('NVM_DIR') ?: '/home/master/.nvm';
                    $output = @shell_exec("export NVM_DIR=\"{$nvmDir}\" && source \"\${NVM_DIR}/nvm.sh\" && nvm install {$version} 2>&1");
                    $result = $output ?: "Installation initiated for version {$version}.";
                } elseif ($action === 'use-version') {
                    $version = escapeshellarg(request('version'));
                    $nvmDir = getenv('NVM_DIR') ?: '/home/master/.nvm';
                    $output = @shell_exec("export NVM_DIR=\"{$nvmDir}\" && source \"\${NVM_DIR}/nvm.sh\" && nvm use {$version} && nvm alias default {$version} 2>&1");
                    $result = $output ?: "Switched to Node.js {$version}.";
                } elseif ($action === 'install-package') {
                    $package = escapeshellarg(request('package'));
                    $output = @shell_exec("npm install -g {$package} 2>&1");
                    $result = $output ?: "Package {$package} installed globally.";
                } elseif ($action === 'remove-package') {
                    $package = escapeshellarg(request('package'));
                    $output = @shell_exec("npm uninstall -g {$package} 2>&1");
                    $result = $output ?: "Package {$package} removed from global packages.";
                } elseif ($action === 'pm2-install') {
                    $output = @shell_exec('npm install -g pm2 2>&1');
                    $result = $output ?: 'PM2 installed successfully.';
                } elseif (in_array($action, ['pm2-start', 'pm2-stop', 'pm2-restart', 'pm2-reload', 'pm2-delete', 'pm2-logs'])) {
                    $pmName = escapeshellarg(request('pm_name'));
                    $pmScript = escapeshellarg(request('pm_script', ''));
                    $pmArgs = escapeshellarg(request('pm_args', ''));
                    $pmWatch = request('pm_watch') ? '--watch' : '';

                    if ($action === 'pm2-start') {
                        if (! empty(request('pm_script'))) {
                            $cmd = "pm2 start {$pmScript} --name {$pmName} {$pmWatch}";
                            if (! empty(request('pm_args'))) {
                                $cmd .= " -- {$pmArgs}";
                            }
                        } else {
                            $cmd = "pm2 start {$pmName} 2>&1";
                        }
                    } elseif ($action === 'pm2-stop') {
                        $cmd = "pm2 stop {$pmName} 2>&1";
                    } elseif ($action === 'pm2-restart') {
                        $cmd = "pm2 restart {$pmName} 2>&1";
                    } elseif ($action === 'pm2-reload') {
                        $cmd = "pm2 reload {$pmName} 2>&1";
                    } elseif ($action === 'pm2-delete') {
                        $cmd = "pm2 delete {$pmName} 2>&1";
                    } elseif ($action === 'pm2-logs') {
                        $specificName = ! empty(request('pm_name')) ? escapeshellarg(request('pm_name')) : '';
                        $cmd = "pm2 logs {$specificName} --lines 50 --nostream 2>&1";
                    }

                    if (isset($cmd)) {
                        $output = @shell_exec($cmd);
                        $result = $output ?: ucfirst(str_replace('pm2-', '', $action)).' command executed.';
                    }
                } elseif ($action === 'pm2-save') {
                    $output = @shell_exec('pm2 save 2>&1');
                    $result = $output ?: 'Process list saved.';
                } elseif ($action === 'pm2-startup') {
                    $output = @shell_exec('pm2 startup 2>&1');
                    $result = $output ?: 'PM2 startup configured.';
                } elseif ($action === 'pm2-resurrect') {
                    $output = @shell_exec('pm2 resurrect 2>&1');
                    $result = $output ?: 'Processes resurrected.';
                } elseif ($action === 'pm2-kill') {
                    $output = @shell_exec('pm2 kill 2>&1');
                    $result = $output ?: 'PM2 daemon killed.';
                } elseif ($action === 'pm2-ping') {
                    $output = @shell_exec('pm2 ping 2>&1');
                    $result = $output ?: 'PM2 daemon is alive.';
                } elseif ($tab === 'general') {
                    $registry = request('registry');
                    $prefix = request('prefix');
                    $cache = request('cache');
                    $nodeEnv = request('node_env');

                    $cmd = '';
                    if (! empty($registry)) {
                        $cmd .= 'npm config set registry '.escapeshellarg($registry).' 2>&1 && ';
                    }
                    if (! empty($prefix)) {
                        $cmd .= 'npm config set prefix '.escapeshellarg($prefix).' 2>&1 && ';
                    }
                    if (! empty($cache)) {
                        $cmd .= 'npm config set cache '.escapeshellarg($cache).' 2>&1 && ';
                    }
                    if (! empty($nodeEnv)) {
                        $cmd .= "echo 'export NODE_ENV={$nodeEnv}' >> ~/.bashrc 2>&1 && export NODE_ENV={$nodeEnv}";
                    } elseif ($nodeEnv === '') {
                        $cmd .= "sed -i '/export NODE_ENV=/d' ~/.bashrc 2>&1 && unset NODE_ENV";
                    }

                    if (! empty($cmd)) {
                        $cmd = rtrim($cmd, ' && ');
                        $output = @shell_exec($cmd);
                        $result = $output ?: 'npm configuration updated successfully.';
                    } else {
                        $result = 'No changes to apply.';
                    }
                }

                if ($result) {
                    session()->flash('node_result', $result);
                }
            } catch (Exception $e) {
                session()->flash('node_error', $e->getMessage());
            }

            return redirect()->route('node.settings', ['tab' => $tab]);
        }

        return view('vendor.node.settings');
    })->name('node.settings');

    Route::match(['get', 'post'], '/quota/settings', function () {
        if (request()->isMethod('post')) {
            $tab = request('tab', 'users');
            $action = request('action');
            $result = null;

            try {
                if ($action === 'set-user-quota') {
                    $username = escapeshellarg(request('username'));
                    $filesystem = escapeshellarg(request('filesystem'));
                    $blockSoft = (int) request('block_soft', 0);
                    $blockHard = (int) request('block_hard', 0);
                    $inodeSoft = (int) request('inode_soft', 0);
                    $inodeHard = (int) request('inode_hard', 0);
                    $output = @shell_exec("sudo -n setquota -u {$username} {$blockSoft} {$blockHard} {$inodeSoft} {$inodeHard} {$filesystem} 2>&1");
                    $result = $output ?: "Quota set for user {$username} on {$filesystem}.";
                } elseif ($action === 'set-group-quota') {
                    $groupname = escapeshellarg(request('groupname'));
                    $filesystem = escapeshellarg(request('filesystem'));
                    $blockSoft = (int) request('block_soft', 0);
                    $blockHard = (int) request('block_hard', 0);
                    $inodeSoft = (int) request('inode_soft', 0);
                    $inodeHard = (int) request('inode_hard', 0);
                    $output = @shell_exec("sudo -n setquota -g {$groupname} {$blockSoft} {$blockHard} {$inodeSoft} {$inodeHard} {$filesystem} 2>&1");
                    $result = $output ?: "Quota set for group {$groupname} on {$filesystem}.";
                } elseif ($action === 'quotaon') {
                    $output = @shell_exec('sudo -n quotaon -a 2>&1');
                    $result = $output ?: 'Quotas enabled on all filesystems.';
                } elseif ($action === 'quotaoff') {
                    $output = @shell_exec('sudo -n quotaoff -a 2>&1');
                    $result = $output ?: 'Quotas disabled on all filesystems.';
                } elseif ($action === 'quotacheck') {
                    $output = @shell_exec('sudo -n quotacheck -augm 2>&1');
                    $result = $output ?: 'Quota check completed.';
                } elseif ($action === 'quotastats') {
                    $output = @shell_exec('quotastats 2>/dev/null');
                    $result = $output ?: 'No quota statistics available.';
                } elseif ($action === 'enable-fstab-quota') {
                    $mount = request('mount');
                    $device = request('device');
                    $fstab = @file_get_contents('/etc/fstab');
                    if ($fstab === false) {
                        throw new RuntimeException('Unable to read /etc/fstab');
                    }
                    $lines = explode("\n", $fstab);
                    $modified = false;
                    foreach ($lines as &$line) {
                        $trimmed = trim($line);
                        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                            continue;
                        }
                        $parts = preg_split('/\s+/', $trimmed);
                        if (count($parts) >= 4 && $parts[0] === $device && $parts[1] === $mount) {
                            $opts = explode(',', $parts[3]);
                            if (! in_array('usrquota', $opts)) {
                                $opts[] = 'usrquota';
                                $modified = true;
                            }
                            if (! in_array('grpquota', $opts)) {
                                $opts[] = 'grpquota';
                                $modified = true;
                            }
                            if ($modified) {
                                $parts[3] = implode(',', $opts);
                                $line = implode("\t", $parts);
                            }
                            break;
                        }
                    }
                    unset($line);
                    if ($modified) {
                        $tmpFile = tempnam(sys_get_temp_dir(), 'fstab_');
                        file_put_contents($tmpFile, implode("\n", $lines)."\n");
                        $output = @shell_exec("sudo -n cp {$tmpFile} /etc/fstab 2>&1");
                        @unlink($tmpFile);
                        $result = $output ?: "Quota options enabled on {$mount}.";
                    } else {
                        $result = "Quota options already enabled on {$mount}.";
                    }
                } elseif ($action === 'disable-fstab-quota') {
                    $mount = request('mount');
                    $device = request('device');
                    $fstab = @file_get_contents('/etc/fstab');
                    if ($fstab === false) {
                        throw new RuntimeException('Unable to read /etc/fstab');
                    }
                    $lines = explode("\n", $fstab);
                    $modified = false;
                    foreach ($lines as &$line) {
                        $trimmed = trim($line);
                        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                            continue;
                        }
                        $parts = preg_split('/\s+/', $trimmed);
                        if (count($parts) >= 4 && $parts[0] === $device && $parts[1] === $mount) {
                            $opts = explode(',', $parts[3]);
                            $original = $opts;
                            $opts = array_values(array_filter($opts, fn ($o) => ! in_array($o, ['usrquota', 'grpquota', 'quota'])));
                            if ($opts !== $original) {
                                $modified = true;
                            }
                            $parts[3] = implode(',', $opts);
                            $line = implode("\t", $parts);
                            break;
                        }
                    }
                    unset($line);
                    if ($modified) {
                        $tmpFile = tempnam(sys_get_temp_dir(), 'fstab_');
                        file_put_contents($tmpFile, implode("\n", $lines)."\n");
                        $output = @shell_exec("sudo -n cp {$tmpFile} /etc/fstab 2>&1");
                        @unlink($tmpFile);
                        $result = $output ?: "Quota options removed from {$mount}.";
                    } else {
                        $result = "No quota options found on {$mount}.";
                    }
                } elseif ($action === 'apply-fstab') {
                    $output = @shell_exec('sudo -n mount -a 2>&1 && sudo -n quotacheck -augm 2>&1 && sudo -n quotaon -a 2>&1');
                    $result = $output ?: 'Changes applied: filesystems remounted, quotas checked and enabled.';
                } elseif ($action === 'view-fstab') {
                    $output = @file_get_contents('/etc/fstab');
                    $result = $output ?: 'Unable to read /etc/fstab.';
                }

                if ($result) {
                    session()->flash('quota_result', $result);
                }
            } catch (Exception $e) {
                session()->flash('quota_error', $e->getMessage());
            }

            return redirect()->route('quota.settings', ['tab' => $tab]);
        }

        return view('vendor.quota.settings');
    })->name('quota.settings');

    Route::get('/quota/dashboard', function () {
        return view('vendor.quota.dashboard');
    })->name('quota.dashboard');

    Route::get('/firewall/dashboard', function () {
        return view('vendor.firewall.dashboard');
    })->name('firewall.dashboard');

    Route::match(['get', 'post'], '/firewall/settings', function () {
        if (request()->isMethod('post')) {
            $tab = request('tab', 'rules');
            $action = request('action');
            $result = null;

            try {
                if ($action === 'add-rule') {
                    $chain = escapeshellarg(request('chain', 'INPUT'));
                    $target = escapeshellarg(request('target', 'ACCEPT'));
                    $protocol = request('protocol', 'all');
                    $source = request('source');
                    $dport = request('dport');

                    $cmd = "sudo -n iptables -A {$chain} -j {$target}";
                    if ($protocol !== 'all') {
                        $cmd .= ' -p '.escapeshellarg($protocol);
                    }
                    if (! empty($source)) {
                        $cmd .= ' -s '.escapeshellarg($source);
                    }
                    if (! empty($dport) && $protocol !== 'all' && $protocol !== 'icmp') {
                        $cmd .= ' --dport '.escapeshellarg($dport);
                    }

                    $output = @shell_exec($cmd.' 2>&1');
                    $result = $output ?: 'Rule added successfully.';
                } elseif ($action === 'delete-rule') {
                    $chain = escapeshellarg(request('chain', 'INPUT'));
                    $num = (int) request('rule_num');
                    $output = @shell_exec("sudo -n iptables -D {$chain} {$num} 2>&1");
                    $result = $output ?: "Rule #{$num} deleted from {$chain}.";
                } elseif ($action === 'flush-chain') {
                    $chain = escapeshellarg(request('chain', 'INPUT'));
                    $output = @shell_exec("sudo -n iptables -F {$chain} 2>&1");
                    $result = $output ?: "Chain {$chain} flushed.";
                } elseif ($action === 'allow-port' || $action === 'block-port') {
                    $target = $action === 'allow-port' ? 'ACCEPT' : 'DROP';
                    $port = (int) request('port');
                    $protocol = escapeshellarg(request('protocol', 'tcp'));
                    $output = @shell_exec("sudo -n iptables -A INPUT -p {$protocol} --dport {$port} -j {$target} 2>&1");
                    $result = $output ?: "Port {$port} {$target} rule added to INPUT chain.";
                } elseif ($action === 'ufw-enable') {
                    $output = @shell_exec('sudo -n ufw --force enable 2>&1');
                    $result = $output ?: 'UFW enabled.';
                } elseif ($action === 'ufw-disable') {
                    $output = @shell_exec('sudo -n ufw disable 2>&1');
                    $result = $output ?: 'UFW disabled.';
                } elseif ($action === 'ufw-add' || $action === 'ufw-delete') {
                    $ufwCmd = $action === 'ufw-add' ? 'allow' : 'delete allow';
                    $dir = escapeshellarg(request('ufw_direction', 'allow'));
                    $io = escapeshellarg(request('ufw_io', 'in'));
                    $port = escapeshellarg(request('ufw_port'));
                    $proto = request('ufw_proto');

                    $cmd = "sudo -n ufw {$dir} {$io} {$port}";
                    if (! empty($proto)) {
                        $cmd .= '/'.escapeshellarg($proto);
                    }
                    $output = @shell_exec($cmd.' 2>&1');
                    $result = $output ?: "UFW rule {$dir}ed for port {$port}.";
                } elseif ($action === 'clear-dmesg') {
                    $output = @shell_exec('sudo -n dmesg -c 2>&1');
                    $result = 'Kernel ring buffer cleared.';
                }

                if ($result) {
                    session()->flash('firewall_result', $result);
                }
            } catch (Exception $e) {
                session()->flash('firewall_error', $e->getMessage());
            }

            return redirect()->route('firewall.settings', ['tab' => $tab]);
        }

        return view('vendor.firewall.settings');
    })->name('firewall.settings');
});

require __DIR__.'/agent/socialite.php';
