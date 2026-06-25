<?php

namespace App\Actions\Php;

class PhpPackageManager
{
    public function installedPackages(): array
    {
        $output = @shell_exec('dpkg -l 2>/dev/null | grep -E "^ii\s+.*php" | awk \'{print $2, $3}\' 2>/dev/null');

        if (! $output) {
            return [];
        }

        $packages = [];
        $lines = array_filter(explode("\n", trim($output)));

        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line), 2);
            if (count($parts) === 2) {
                $packages[] = [
                    'name' => $parts[0],
                    'version' => $parts[1],
                ];
            }
        }

        return $packages;
    }

    public function fpmInstalled(?string $version = null): bool
    {
        $version = $version ?? $this->currentDefaultVersion();

        return ! empty(trim((string) shell_exec("/usr/sbin/php-fpm{$version} -v 2>&1")));
    }

    public function availablePackages(string $search = 'php'): array
    {
        $output = @shell_exec('apt-cache search '.escapeshellarg($search).' 2>/dev/null | head -100');

        if (! $output) {
            return [];
        }

        $packages = [];
        $lines = array_filter(explode("\n", trim($output)));

        foreach ($lines as $line) {
            if (preg_match('/^(\S+)\s+-\s+(.+)$/', $line, $m)) {
                $packages[] = [
                    'name' => $m[1],
                    'description' => $m[2],
                    'installed' => $this->isPackageInstalled($m[1]),
                ];
            }
        }

        return $packages;
    }

    public function install(string $package): array
    {
        $command = 'sudo DEBIAN_FRONTEND=noninteractive apt-get install -y '.escapeshellarg($package).' 2>&1';
        $output = @shell_exec($command);

        return [
            'success' => str_contains($output ?? '', 'Setting up'),
            'output' => $output,
            'package' => $package,
        ];
    }

    public function remove(string $package): array
    {
        $command = 'sudo DEBIAN_FRONTEND=noninteractive apt-get remove -y '.escapeshellarg($package).' 2>&1';
        $output = @shell_exec($command);

        return [
            'success' => str_contains($output ?? '', 'Removing'),
            'output' => $output,
            'package' => $package,
        ];
    }

    public function enableExtension(string $extension, string $sapi = 'cli', ?string $version = null): array
    {
        $version = $version ?? $this->currentDefaultVersion();
        $command = 'sudo phpenmod -v '.escapeshellarg($version).' -s '.escapeshellarg($sapi).' '.escapeshellarg($extension).' 2>&1';
        $output = @shell_exec($command);

        return [
            'success' => empty(trim($output ?? '')),
            'output' => $output,
            'extension' => $extension,
            'sapi' => $sapi,
            'version' => $version,
        ];
    }

    public function disableExtension(string $extension, string $sapi = 'cli', ?string $version = null): array
    {
        $version = $version ?? $this->currentDefaultVersion();
        $command = 'sudo phpdismod -v '.escapeshellarg($version).' -s '.escapeshellarg($sapi).' '.escapeshellarg($extension).' 2>&1';
        $output = @shell_exec($command);

        return [
            'success' => empty(trim($output ?? '')),
            'output' => $output,
            'extension' => $extension,
            'sapi' => $sapi,
            'version' => $version,
        ];
    }

    public function extensionStatus(string $extension, ?string $version = null): array
    {
        $version = $version ?? $this->currentDefaultVersion();
        $base = $this->phpConfigDir($version);
        $loaded = extension_loaded($extension);
        $enabledCli = is_file("{$base}/cli/conf.d/{$extension}.ini");
        $enabledApache = is_file("{$base}/apache2/conf.d/{$extension}.ini");
        $enabledFpm = is_file("{$base}/fpm/conf.d/{$extension}.ini");

        return [
            'name' => $extension,
            'loaded' => $loaded,
            'version' => $version,
            'enabled' => [
                'cli' => $enabledCli,
                'apache2' => $enabledApache,
                'fpm' => $enabledFpm,
            ],
        ];
    }

    public function allExtensionsStatus(?string $version = null): array
    {
        $version = $version ?? $this->currentDefaultVersion();
        $modsDir = $this->phpConfigDir($version).'/mods-available';
        if (! is_dir($modsDir)) {
            return [];
        }

        $extensions = [];
        $files = glob($modsDir.'/*.ini');
        sort($files);

        foreach ($files as $file) {
            $name = basename($file, '.ini');
            $extensions[] = $this->extensionStatus($name, $version);
        }

        return $extensions;
    }

    private function phpConfigDir(string $version): string
    {
        return '/etc/php/'.$version;
    }

    public function availableVersions(): array
    {
        $output = @shell_exec('apt-cache search php8 2>/dev/null | grep -E "^php[0-9]+\.[0-9]+" | awk \'{print $1}\' | sort -u');

        if (! $output) {
            return ['8.4'];
        }

        $versions = array_filter(explode("\n", trim($output)));
        $parsed = [];

        foreach ($versions as $v) {
            if (preg_match('/^php(\d+\.\d+)$/', $v, $m)) {
                $parsed[] = $m[1];
            }
        }

        return array_unique($parsed);
    }

    public function updatePackageCache(): array
    {
        $output = @shell_exec('sudo apt-get update 2>&1 | tail -5');

        return [
            'success' => true,
            'output' => $output,
        ];
    }

    public function installedVersions(): array
    {
        $output = @shell_exec('update-alternatives --list php 2>/dev/null');

        if (! $output) {
            $bins = glob('/usr/bin/php[0-9]*');
            $output = implode("\n", $bins);
        }

        if (! $output) {
            return [
                'installed' => [],
                'default' => $this->currentDefaultVersion(),
            ];
        }

        $versions = [];
        $lines = array_filter(explode("\n", trim($output)));

        foreach ($lines as $line) {
            if (preg_match('/php(\d+\.\d+)$/', trim($line), $m)) {
                $versions[] = $m[1];
            }
        }

        $versions = array_unique($versions);
        sort($versions);

        return [
            'installed' => $versions,
            'default' => $this->currentDefaultVersion(),
        ];
    }

    public function currentDefaultVersion(): string
    {
        $link = @readlink('/usr/bin/php');

        if ($link && preg_match('/php(\d+\.\d+)$/', $link, $m)) {
            return $m[1];
        }

        $output = @shell_exec('php -v 2>/dev/null | head -1');
        if ($output && preg_match('/^PHP\s+(\d+\.\d+)/', $output, $m)) {
            return $m[1];
        }

        return PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;
    }

    public function hasVersion(string $version): bool
    {
        $bin = '/usr/bin/php'.$version;

        return file_exists($bin) || is_link($bin);
    }

    public function switchVersion(string $version): array
    {
        $bin = '/usr/bin/php'.$version;

        if (! $this->hasVersion($version)) {
            return [
                'success' => false,
                'output' => "PHP {$version} is not installed. Install it first.",
                'version' => $version,
            ];
        }

        $commands = [
            "sudo update-alternatives --set php {$bin} 2>&1",
            "sudo update-alternatives --set php-config /usr/bin/php-config{$version} 2>&1",
            "sudo update-alternatives --set phpize /usr/bin/phpize{$version} 2>&1",
        ];

        $allOutput = [];
        foreach ($commands as $cmd) {
            $allOutput[] = '$ '.$cmd;
            $allOutput[] = trim((string) @shell_exec($cmd));
        }

        $output = implode("\n", $allOutput);

        return [
            'success' => true,
            'output' => $output,
            'version' => $version,
        ];
    }

    public function installVersionPackage(string $version): array
    {
        $packages = [
            "php{$version}",
            "php{$version}-cli",
            "php{$version}-dev",
            "php{$version}-pgsql",
            "php{$version}-sqlite3",
            "php{$version}-gd",
            "php{$version}-curl",
            "php{$version}-mongodb",
            "php{$version}-imap",
            "php{$version}-mysql",
            "php{$version}-mbstring",
            "php{$version}-xml",
            "php{$version}-zip",
            "php{$version}-bcmath",
            "php{$version}-soap",
            "php{$version}-intl",
            "php{$version}-readline",
            "php{$version}-ldap",
            "php{$version}-msgpack",
            "php{$version}-igbinary",
            "php{$version}-redis",
            "php{$version}-swoole",
            "php{$version}-memcached",
            "php{$version}-pcov",
            "php{$version}-imagick",
            "php{$version}-xdebug",
        ];

        $packageStr = implode(' ', $packages);
        $command = 'sudo DEBIAN_FRONTEND=noninteractive apt-get install -y '.$packageStr.' 2>&1';
        $output = @shell_exec($command);

        return [
            'success' => str_contains($output ?? '', 'Setting up'),
            'output' => $output,
            'version' => $version,
            'packages' => $packages,
        ];
    }

    public function needsSudo(): bool
    {
        $test = @shell_exec('sudo -n true 2>&1');

        return $test === null;
    }

    private function isPackageInstalled(string $name): bool
    {
        $output = @shell_exec('dpkg -l '.escapeshellarg($name).' 2>/dev/null | grep "^ii"');

        return ! empty($output);
    }
}
