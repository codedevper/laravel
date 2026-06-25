<?php

namespace App\Actions\Wp;

class WpCli
{
    protected function wpCommand(): string
    {
        return 'wp';
    }

    public function installed(): array
    {
        $output = @shell_exec('which wp 2>/dev/null');

        if (! $output) {
            return ['installed' => false];
        }

        $version = $this->version();

        return [
            'installed' => true,
            'path' => trim($output),
            'version' => $version['version'] ?? 'unknown',
        ];
    }

    public function install(): array
    {
        $commands = [
            'sudo curl -L -o /usr/local/bin/wp https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar 2>&1',
            'sudo chmod +x /usr/local/bin/wp 2>&1',
        ];

        $outputs = [];
        foreach ($commands as $cmd) {
            $outputs[] = '$ '.$cmd;
            $outputs[] = trim((string) @shell_exec($cmd));
        }

        return [
            'success' => $this->installed()['installed'],
            'output' => implode("\n", $outputs),
        ];
    }

    public function update(): array
    {
        return $this->runRaw('wp cli update --yes');
    }

    public function version(): array
    {
        $output = @shell_exec('wp cli version 2>&1');

        if (! $output) {
            return ['version' => 'unknown', 'raw' => ''];
        }

        return [
            'version' => trim($output),
            'raw' => trim($output),
        ];
    }

    public function siteInfo(string $path): array
    {
        if (! is_dir($path) || ! file_exists($path.'/wp-config.php')) {
            return ['error' => 'Not a valid WordPress installation'];
        }

        return [
            'core' => $this->runWpCommand($path, 'core version'),
            'plugins' => $this->runWpCommand($path, 'plugin list --format=json'),
            'themes' => $this->runWpCommand($path, 'theme list --format=json'),
            'users' => $this->runWpCommand($path, 'user list --format=json'),
        ];
    }

    public function coreVersion(string $path): array
    {
        return $this->runWpCommand($path, 'core version');
    }

    public function coreUpdate(string $path): array
    {
        return $this->runWpCommand($path, 'core update --yes');
    }

    public function coreUpdateDb(string $path): array
    {
        return $this->runWpCommand($path, 'core update-db');
    }

    public function pluginList(string $path): array
    {
        return $this->runWpCommand($path, 'plugin list --format=json');
    }

    public function pluginInstall(string $path, string $slug): array
    {
        return $this->runWpCommand($path, 'plugin install '.escapeshellarg($slug));
    }

    public function pluginUpdate(string $path, ?string $slug = null): array
    {
        $target = $slug ? escapeshellarg($slug) : '--all';

        return $this->runWpCommand($path, 'plugin update '.$target.' --yes');
    }

    public function pluginActivate(string $path, string $slug): array
    {
        return $this->runWpCommand($path, 'plugin activate '.escapeshellarg($slug));
    }

    public function pluginDeactivate(string $path, string $slug): array
    {
        return $this->runWpCommand($path, 'plugin deactivate '.escapeshellarg($slug));
    }

    public function themeList(string $path): array
    {
        return $this->runWpCommand($path, 'theme list --format=json');
    }

    public function themeActivate(string $path, string $slug): array
    {
        return $this->runWpCommand($path, 'theme activate '.escapeshellarg($slug));
    }

    public function userList(string $path): array
    {
        return $this->runWpCommand($path, 'user list --format=json');
    }

    public function userCreate(string $path, string $username, string $email, string $role, ?string $password = null): array
    {
        $pass = $password ?? bin2hex(random_bytes(16));
        $cmd = 'user create '.escapeshellarg($username).' '.escapeshellarg($email).
            ' --role='.escapeshellarg($role).
            ' --user_pass='.escapeshellarg($pass);

        return $this->runWpCommand($path, $cmd);
    }

    public function dbOptimize(string $path): array
    {
        return $this->runWpCommand($path, 'db optimize');
    }

    public function dbRepair(string $path): array
    {
        return $this->runWpCommand($path, 'db repair');
    }

    public function dbExport(string $path, ?string $filename = null): array
    {
        $file = $filename ?? 'export-'.date('Ymd-His').'.sql';

        return $this->runWpCommand($path, 'db export '.escapeshellarg($file));
    }

    public function checkUpdates(string $path): array
    {
        $core = $this->runWpCommand($path, 'core check-update --format=json');
        $plugins = $this->runWpCommand($path, 'plugin list --format=json --update=available');
        $themes = $this->runWpCommand($path, 'theme list --format=json --update=available');

        return [
            'core' => $core,
            'plugins' => $plugins,
            'themes' => $themes,
        ];
    }

    public function command(string $path, string $args): array
    {
        return $this->runWpCommand($path, $args);
    }

    protected function runWpCommand(string $path, string $args): array
    {
        $escapedPath = escapeshellarg($path);
        $command = "wp --path={$escapedPath} {$args} 2>&1";
        $output = @shell_exec($command);

        return [
            'success' => $output !== null,
            'output' => trim($output ?? ''),
            'command' => $command,
            'path' => $path,
        ];
    }

    protected function runRaw(string $command): array
    {
        $output = @shell_exec($command.' 2>&1');

        return [
            'success' => $output !== null,
            'output' => trim($output ?? ''),
            'command' => $command,
        ];
    }
}
