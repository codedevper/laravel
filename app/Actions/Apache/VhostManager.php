<?php

namespace App\Actions\Apache;

class VhostManager
{
    private string $sitesAvailable = '/etc/apache2/sites-available';

    private string $sitesEnabled = '/etc/apache2/sites-enabled';

    private string $modsAvailable = '/etc/apache2/mods-available';

    private string $modsEnabled = '/etc/apache2/mods-enabled';

    public function listVhosts(): array
    {
        if (! is_dir($this->sitesAvailable)) {
            return [];
        }

        $files = glob($this->sitesAvailable.'/*.conf');
        sort($files);

        $vhosts = [];

        foreach ($files as $file) {
            $name = basename($file, '.conf');
            $enabled = is_file($this->sitesEnabled.'/'.$name.'.conf');
            $content = @file_get_contents($file);

            if ($content === false) {
                continue;
            }

            $vhosts[] = [
                'name' => $name,
                'file' => $file,
                'enabled' => $enabled,
                'content' => $content,
                'size' => filesize($file),
                'modified' => filemtime($file),
                'parsed' => $this->parseVhostConfig($content),
            ];
        }

        return $vhosts;
    }

    public function getVhost(string $name): ?array
    {
        $file = $this->sitesAvailable.'/'.basename($name).'.conf';

        if (! file_exists($file)) {
            return null;
        }

        $content = @file_get_contents($file);

        if ($content === false) {
            return null;
        }

        $enabled = is_file($this->sitesEnabled.'/'.basename($name).'.conf');

        return [
            'name' => $name,
            'file' => $file,
            'enabled' => $enabled,
            'content' => $content,
            'size' => filesize($file),
            'modified' => filemtime($file),
            'parsed' => $this->parseVhostConfig($content),
        ];
    }

    public function parseVhostConfig(string $content): array
    {
        $vhosts = [];
        $directives = $this->extractAllDirectives($content);

        preg_match_all('/<VirtualHost\s+([^>]+)>(.*?)<\/VirtualHost>/is', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $addr = trim($match[1]);
            $body = $match[2];
            $block = $this->extractDirectives($body);

            $block['address'] = $addr;
            $block['ssl'] = $this->hasDirective($body, 'SSLEngine', 'on');

            if (! isset($block['servername'])) {
                $block['servername'] = '';
            }

            $vhosts[] = $block;
        }

        $global = [];
        foreach ($directives as $key => $value) {
            if (! str_starts_with($key, '_block_')) {
                $global[$key] = $value;
            }
        }

        return [
            'global' => $global,
            'vhosts' => $vhosts,
            'raw' => $content,
        ];
    }

    public function createVhost(array $data): array
    {
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '', $data['name'] ?? '');
        $serverName = $data['server_name'] ?? $name;
        $serverAdmin = $data['server_admin'] ?? 'admin@'.$serverName;
        $documentRoot = $data['document_root'] ?? '/var/www/'.$serverName.'/public';
        $serverAlias = $data['server_alias'] ?? '';
        $ssl = ! empty($data['ssl']);
        $extraConfig = $data['extra_config'] ?? '';

        if (empty($name)) {
            return ['success' => false, 'output' => 'A vhost name is required.'];
        }

        $file = $this->sitesAvailable.'/'.$name.'.conf';

        if (file_exists($file)) {
            return ['success' => false, 'output' => "Vhost '{$name}' already exists."];
        }

        $config = "<VirtualHost *:80>\n";
        $config .= "    ServerAdmin {$serverAdmin}\n";
        $config .= "    ServerName {$serverName}\n";

        if (! empty($serverAlias)) {
            $aliases = array_map('trim', explode(',', $serverAlias));
            foreach ($aliases as $alias) {
                if (! empty($alias)) {
                    $config .= "    ServerAlias {$alias}\n";
                }
            }
        }

        $config .= "\n";
        $config .= "    DocumentRoot {$documentRoot}\n";
        $config .= "\n";
        $config .= "    <Directory {$documentRoot}>\n";
        $config .= "        Options Indexes FollowSymLinks\n";
        $config .= "        AllowOverride All\n";
        $config .= "        Require all granted\n";
        $config .= "    </Directory>\n";
        $config .= "\n";
        $config .= "    ErrorLog \${APACHE_LOG_DIR}/{$name}_error.log\n";
        $config .= "    CustomLog \${APACHE_LOG_DIR}/{$name}_access.log combined\n";

        if (! empty($extraConfig)) {
            $config .= "\n".$extraConfig."\n";
        }

        $config .= "</VirtualHost>\n";

        if ($ssl) {
            $config .= "\n<VirtualHost *:443>\n";
            $config .= "    ServerAdmin {$serverAdmin}\n";
            $config .= "    ServerName {$serverName}\n";

            if (! empty($serverAlias)) {
                $aliases = array_map('trim', explode(',', $serverAlias));
                foreach ($aliases as $alias) {
                    if (! empty($alias)) {
                        $config .= "    ServerAlias {$alias}\n";
                    }
                }
            }

            $config .= "\n";
            $config .= "    DocumentRoot {$documentRoot}\n";
            $config .= "\n";
            $config .= "    SSLEngine on\n";
            $config .= "    SSLCertificateFile /etc/letsencrypt/live/{$serverName}/fullchain.pem\n";
            $config .= "    SSLCertificateKeyFile /etc/letsencrypt/live/{$serverName}/privkey.pem\n";
            $config .= "\n";
            $config .= "    <Directory {$documentRoot}>\n";
            $config .= "        Options Indexes FollowSymLinks\n";
            $config .= "        AllowOverride All\n";
            $config .= "        Require all granted\n";
            $config .= "    </Directory>\n";
            $config .= "\n";
            $config .= "    ErrorLog \${APACHE_LOG_DIR}/{$name}_error.log\n";
            $config .= "    CustomLog \${APACHE_LOG_DIR}/{$name}_access.log combined\n";

            if (! empty($extraConfig)) {
                $config .= "\n".$extraConfig."\n";
            }

            $config .= "</VirtualHost>\n";
        }

        $written = @file_put_contents($file, $config);

        if ($written === false) {
            return ['success' => false, 'output' => "Failed to write vhost config to {$file}. Check permissions."];
        }

        return [
            'success' => true,
            'output' => "Vhost '{$name}' created at {$file}.",
            'file' => $file,
            'name' => $name,
        ];
    }

    public function updateVhost(string $name, array $data): array
    {
        $file = $this->sitesAvailable.'/'.basename($name).'.conf';

        if (! file_exists($file)) {
            return ['success' => false, 'output' => "Vhost '{$name}' not found."];
        }

        $content = $data['content'] ?? '';

        if (empty(trim($content))) {
            return ['success' => false, 'output' => 'Vhost content cannot be empty.'];
        }

        $written = @file_put_contents($file, $content);

        if ($written === false) {
            return ['success' => false, 'output' => "Failed to write vhost config to {$file}. Check permissions."];
        }

        return [
            'success' => true,
            'output' => "Vhost '{$name}' updated.",
            'file' => $file,
            'name' => $name,
        ];
    }

    public function deleteVhost(string $name): array
    {
        $file = $this->sitesAvailable.'/'.basename($name).'.conf';

        if (! file_exists($file)) {
            return ['success' => false, 'output' => "Vhost '{$name}' not found."];
        }

        if (is_file($this->sitesEnabled.'/'.basename($name).'.conf')) {
            $this->disableVhost($name);
        }

        $deleted = @unlink($file);

        if (! $deleted) {
            return ['success' => false, 'output' => "Failed to delete vhost config {$file}. Check permissions."];
        }

        return [
            'success' => true,
            'output' => "Vhost '{$name}' deleted.",
            'name' => $name,
        ];
    }

    public function enableVhost(string $name): array
    {
        $command = 'sudo -n a2ensite '.escapeshellarg(basename($name).'.conf').' 2>&1';
        $output = @shell_exec($command);

        $success = $output !== null && ! str_contains($output, 'ERROR') && ! str_contains($output, 'does not exist');

        return [
            'success' => $success,
            'output' => $output ? trim($output) : 'Site enabled.',
            'name' => $name,
        ];
    }

    public function disableVhost(string $name): array
    {
        $command = 'sudo -n a2dissite '.escapeshellarg(basename($name).'.conf').' 2>&1';
        $output = @shell_exec($command);

        $success = $output !== null && ! str_contains($output, 'ERROR');

        return [
            'success' => $success,
            'output' => $output ? trim($output) : 'Site disabled.',
            'name' => $name,
        ];
    }

    public function listModules(): array
    {
        if (! is_dir($this->modsAvailable)) {
            return [];
        }

        $files = glob($this->modsAvailable.'/*.load');
        sort($files);

        $modules = [];

        foreach ($files as $file) {
            $name = basename($file, '.load');
            $enabled = is_file($this->modsEnabled.'/'.$name.'.load');
            $content = @file_get_contents($file);

            $description = '';
            if ($content && preg_match('/^#\s*(.+)$/m', $content, $m)) {
                $description = trim($m[1]);
            }

            $modules[] = [
                'name' => $name,
                'file' => $file,
                'enabled' => $enabled,
                'description' => $description,
            ];
        }

        return $modules;
    }

    public function enableModule(string $name): array
    {
        $command = 'sudo -n a2enmod '.escapeshellarg(basename($name)).' 2>&1';
        $output = @shell_exec($command);

        $success = $output !== null && ! str_contains($output, 'ERROR') && ! str_contains($output, 'does not exist');

        return [
            'success' => $success,
            'output' => $output ? trim($output) : 'Module enabled.',
            'name' => $name,
        ];
    }

    public function disableModule(string $name): array
    {
        $command = 'sudo -n a2dismod '.escapeshellarg(basename($name)).' 2>&1';
        $output = @shell_exec($command);

        $success = $output !== null && ! str_contains($output, 'ERROR');

        return [
            'success' => $success,
            'output' => $output ? trim($output) : 'Module disabled.',
            'name' => $name,
        ];
    }

    public function testConfig(): array
    {
        $output = @shell_exec('apachectl configtest 2>&1');

        $success = $output !== null && str_contains($output, 'Syntax OK');

        return [
            'success' => $success,
            'output' => $output ? trim($output) : 'Config test completed.',
        ];
    }

    public function reload(): array
    {
        $output = @shell_exec('sudo -n systemctl reload apache2 2>&1');

        $success = $output === null || empty(trim($output));

        return [
            'success' => $success,
            'output' => $success ? 'Apache reloaded successfully.' : ($output ?: 'Reload command failed.'),
        ];
    }

    public function restart(): array
    {
        $output = @shell_exec('sudo -n systemctl restart apache2 2>&1');

        $success = $output === null || empty(trim($output));

        return [
            'success' => $success,
            'output' => $success ? 'Apache restarted successfully.' : ($output ?: 'Restart command failed.'),
        ];
    }

    public function status(): array
    {
        $version = '';
        $mpm = '';
        $modules = [];

        $vOutput = @shell_exec('apachectl -V 2>&1');
        if ($vOutput) {
            if (preg_match('/Server version:\s*Apache\/([\d.]+)/i', $vOutput, $m)) {
                $version = $m[1];
            }
            if (preg_match('/Server MPM:\s*(\S+)/i', $vOutput, $m)) {
                $mpm = $m[1];
            }
        }

        $mOutput = @shell_exec('apachectl -M 2>&1');
        if ($mOutput) {
            preg_match_all('/^\s*(\S+)_module\s/m', $mOutput, $m);
            $modules = $m[1] ?? [];
            sort($modules);
        }

        $active = @shell_exec('systemctl is-active apache2 2>&1');

        return [
            'version' => $version ?: 'N/A',
            'mpm' => $mpm ?: 'N/A',
            'active' => trim($active ?? 'unknown'),
            'module_count' => count($modules),
            'modules' => $modules,
        ];
    }

    public function listCertificates(): array
    {
        $certs = [];

        $leDir = '/etc/letsencrypt/live';
        if (is_dir($leDir)) {
            $domains = scandir($leDir);
            foreach ($domains as $domain) {
                if ($domain === '.' || $domain === '..') {
                    continue;
                }
                $certFile = $leDir.'/'.$domain.'/fullchain.pem';
                $keyFile = $leDir.'/'.$domain.'/privkey.pem';

                if (file_exists($certFile) && file_exists($keyFile)) {
                    $certData = @openssl_x509_parse(@file_get_contents($certFile));
                    $certs[] = [
                        'type' => 'letsencrypt',
                        'domain' => $domain,
                        'cert_file' => $certFile,
                        'key_file' => $keyFile,
                        'valid_from' => $certData['validFrom_time_t'] ?? null,
                        'valid_to' => $certData['validTo_time_t'] ?? null,
                        'subject' => $certData['subject']['CN'] ?? $domain,
                        'issuer' => $certData['issuer']['CN'] ?? 'Unknown',
                    ];
                }
            }
        }

        $sslDir = '/etc/ssl/certs';
        if (is_dir($sslDir)) {
            $files = glob($sslDir.'/*.pem');
            foreach ($files as $file) {
                $certData = @openssl_x509_parse(@file_get_contents($file));
                if ($certData) {
                    $cn = $certData['subject']['CN'] ?? basename($file, '.pem');
                    if (! $this->certExistsForDomain($certs, $cn)) {
                        $certs[] = [
                            'type' => 'self-signed',
                            'domain' => $cn,
                            'cert_file' => $file,
                            'key_file' => preg_replace('/\.pem$/', '.key', $file),
                            'valid_from' => $certData['validFrom_time_t'] ?? null,
                            'valid_to' => $certData['validTo_time_t'] ?? null,
                            'subject' => $cn,
                            'issuer' => $certData['issuer']['CN'] ?? 'Unknown',
                        ];
                    }
                }
            }
        }

        return $certs;
    }

    public function generateSelfSignedCert(string $domain, string $adminEmail = 'admin@localhost'): array
    {
        $keyFile = '/etc/ssl/private/'.$domain.'.key';
        $certFile = '/etc/ssl/certs/'.$domain.'.pem';
        $days = 365;

        $command = 'sudo -n openssl req -x509 -nodes -days '.$days.' -newkey rsa:2048 '
            .'-keyout '.escapeshellarg($keyFile).' '
            .'-out '.escapeshellarg($certFile).' '
            .'-subj '.escapeshellarg("/CN={$domain}/emailAddress={$adminEmail}").' 2>&1';

        $output = @shell_exec($command);

        $success = file_exists($keyFile) && file_exists($certFile);

        return [
            'success' => $success,
            'output' => $output ? trim($output) : ($success ? "Self-signed certificate generated for {$domain}." : 'Failed to generate certificate.'),
            'domain' => $domain,
            'cert_file' => $certFile,
            'key_file' => $keyFile,
        ];
    }

    public function needsSudo(): bool
    {
        $test = @shell_exec('sudo -n true 2>&1');

        return $test === null;
    }

    private function extractDirectives(string $body): array
    {
        $directives = [];

        $lines = explode("\n", $body);
        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^<(\/?)Directory/i', $line) || preg_match('/^<(\/?)Files/i', $line) || preg_match('/^<(\/?)If/i', $line) || preg_match('/^<(\/?)Location/i', $line) || preg_match('/^<(\/?)Proxy/i', $line) || preg_match('/^<(\/?)VirtualHost/i', $line)) {
                continue;
            }

            if (preg_match('/^(\w+)\s+(.+)$/', $line, $m)) {
                $key = strtolower($m[1]);
                $value = trim($m[2]);

                if ($key === 'sengine' || $key === 'sslengine') {
                    $key = 'sslengine';
                }

                if (isset($directives[$key])) {
                    if (! is_array($directives[$key])) {
                        $directives[$key] = [$directives[$key]];
                    }
                    $directives[$key][] = $value;
                } else {
                    $directives[$key] = $value;
                }
            }
        }

        return $directives;
    }

    private function extractAllDirectives(string $content): array
    {
        $directives = [];

        $lines = explode("\n", $content);
        foreach ($lines as $line) {
            $line = trim($line);

            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^<VirtualHost/i', $line)) {
                continue;
            }

            if (preg_match('/^<\//i', $line)) {
                continue;
            }

            if (preg_match('/^(\w+)\s+(.+)$/', $line, $m)) {
                $key = strtolower($m[1]);
                $value = trim($m[2]);

                if (isset($directives[$key])) {
                    if (! is_array($directives[$key])) {
                        $directives[$key] = [$directives[$key]];
                    }
                    $directives[$key][] = $value;
                } else {
                    $directives[$key] = $value;
                }
            }
        }

        return $directives;
    }

    private function hasDirective(string $body, string $directive, string $value): bool
    {
        return (bool) preg_match('/^\s*'.preg_quote($directive, '/').'\s+'.preg_quote($value, '/').'\s*$/im', $body);
    }

    private function certExistsForDomain(array $certs, string $domain): bool
    {
        foreach ($certs as $cert) {
            if ($cert['domain'] === $domain) {
                return true;
            }
        }

        return false;
    }
}
