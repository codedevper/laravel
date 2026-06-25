<?php

namespace App\Actions\Php;

class PhpInfo
{
    public function version(): array
    {
        return [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'os' => PHP_OS,
            'compiler' => 'N/A',
            'build_date' => php_uname('r'),
            'zend_version' => zend_version(),
            'ini_path' => php_ini_loaded_file(),
            'ini_scanned_path' => php_ini_scanned_files() ?: 'none',
            'arch' => PHP_INT_SIZE === 8 ? '64-bit' : '32-bit',
            'debug' => defined('PHP_DEBUG') && PHP_DEBUG,
        ];
    }

    public function iniSettings(): array
    {
        return [
            'Core' => [
                'memory_limit' => ['local' => ini_get('memory_limit'), 'global' => ini_get('memory_limit'), 'access' => 'PHP_INI_ALL'],
                'max_execution_time' => ['local' => ini_get('max_execution_time'), 'global' => ini_get('max_execution_time'), 'access' => 'PHP_INI_ALL'],
                'max_input_time' => ['local' => ini_get('max_input_time'), 'global' => ini_get('max_input_time'), 'access' => 'PHP_INI_PERDIR'],
                'max_input_vars' => ['local' => ini_get('max_input_vars'), 'global' => ini_get('max_input_vars'), 'access' => 'PHP_INI_PERDIR'],
                'post_max_size' => ['local' => ini_get('post_max_size'), 'global' => ini_get('post_max_size'), 'access' => 'PHP_INI_PERDIR'],
                'upload_max_filesize' => ['local' => ini_get('upload_max_filesize'), 'global' => ini_get('upload_max_filesize'), 'access' => 'PHP_INI_PERDIR'],
                'max_file_uploads' => ['local' => ini_get('max_file_uploads'), 'global' => ini_get('max_file_uploads'), 'access' => 'PHP_INI_SYSTEM'],
                'default_charset' => ['local' => ini_get('default_charset'), 'global' => ini_get('default_charset'), 'access' => 'PHP_INI_ALL'],
                'date.timezone' => ['local' => ini_get('date.timezone'), 'global' => ini_get('date.timezone'), 'access' => 'PHP_INI_ALL'],
            ],
            'Error Handling' => [
                'error_reporting' => ['local' => $this->errorLevelName((int) ini_get('error_reporting')), 'global' => $this->errorLevelName((int) ini_get('error_reporting')), 'access' => 'PHP_INI_ALL'],
                'display_errors' => ['local' => ini_get('display_errors') ?: 'Off', 'global' => ini_get('display_errors') ?: 'Off', 'access' => 'PHP_INI_ALL'],
                'display_startup_errors' => ['local' => ini_get('display_startup_errors') ?: 'Off', 'global' => ini_get('display_startup_errors') ?: 'Off', 'access' => 'PHP_INI_ALL'],
                'log_errors' => ['local' => ini_get('log_errors') ? 'On' : 'Off', 'global' => ini_get('log_errors') ? 'On' : 'Off', 'access' => 'PHP_INI_ALL'],
                'error_log' => ['local' => ini_get('error_log') ?: '(not set)', 'global' => ini_get('error_log') ?: '(not set)', 'access' => 'PHP_INI_ALL'],
                'ignore_repeated_errors' => ['local' => ini_get('ignore_repeated_errors') ? 'On' : 'Off', 'global' => ini_get('ignore_repeated_errors') ? 'On' : 'Off', 'access' => 'PHP_INI_ALL'],
            ],
            'Security' => [
                'disable_functions' => ['local' => ini_get('disable_functions') ?: '(none)', 'global' => ini_get('disable_functions') ?: '(none)', 'access' => 'PHP_INI_SYSTEM'],
                'disable_classes' => ['local' => ini_get('disable_classes') ?: '(none)', 'global' => ini_get('disable_classes') ?: '(none)', 'access' => 'PHP_INI_SYSTEM'],
                'allow_url_fopen' => ['local' => ini_get('allow_url_fopen') ? 'On' : 'Off', 'global' => ini_get('allow_url_fopen') ? 'On' : 'Off', 'access' => 'PHP_INI_SYSTEM'],
                'allow_url_include' => ['local' => ini_get('allow_url_include') ? 'On' : 'Off', 'global' => ini_get('allow_url_include') ? 'On' : 'Off', 'access' => 'PHP_INI_SYSTEM'],
                'expose_php' => ['local' => ini_get('expose_php') ? 'On' : 'Off', 'global' => ini_get('expose_php') ? 'On' : 'Off', 'access' => 'PHP_INI_SYSTEM'],
                'enable_dl' => ['local' => ini_get('enable_dl') ? 'On' : 'Off', 'global' => ini_get('enable_dl') ? 'On' : 'Off', 'access' => 'PHP_INI_SYSTEM'],
            ],
            'Performance' => [
                'opcache.enable' => ['local' => ini_get('opcache.enable') ? 'On' : 'Off', 'global' => ini_get('opcache.enable') ? 'On' : 'Off', 'access' => 'PHP_INI_SYSTEM'],
                'opcache.memory_consumption' => ['local' => ini_get('opcache.memory_consumption') ?: '128', 'global' => ini_get('opcache.memory_consumption') ?: '128', 'access' => 'PHP_INI_SYSTEM'],
                'opcache.interned_strings_buffer' => ['local' => ini_get('opcache.interned_strings_buffer') ?: '8', 'global' => ini_get('opcache.interned_strings_buffer') ?: '8', 'access' => 'PHP_INI_SYSTEM'],
                'opcache.max_accelerated_files' => ['local' => ini_get('opcache.max_accelerated_files') ?: '10000', 'global' => ini_get('opcache.max_accelerated_files') ?: '10000', 'access' => 'PHP_INI_SYSTEM'],
                'opcache.revalidate_freq' => ['local' => ini_get('opcache.revalidate_freq') ?: '2', 'global' => ini_get('opcache.revalidate_freq') ?: '2', 'access' => 'PHP_INI_ALL'],
                'opcache.validate_timestamps' => ['local' => ini_get('opcache.validate_timestamps') ? 'On' : 'Off', 'global' => ini_get('opcache.validate_timestamps') ? 'On' : 'Off', 'access' => 'PHP_INI_ALL'],
                'opcache.jit' => ['local' => ini_get('opcache.jit') ?: 'Off', 'global' => ini_get('opcache.jit') ?: 'Off', 'access' => 'PHP_INI_ALL'],
                'opcache.jit_buffer_size' => ['local' => ini_get('opcache.jit_buffer_size') ?: '64M', 'global' => ini_get('opcache.jit_buffer_size') ?: '64M', 'access' => 'PHP_INI_ALL'],
                'pcre.jit' => ['local' => ini_get('pcre.jit') ? 'On' : 'Off', 'global' => ini_get('pcre.jit') ? 'On' : 'Off', 'access' => 'PHP_INI_ALL'],
            ],
            'Session' => [
                'session.save_handler' => ['local' => ini_get('session.save_handler') ?: 'files', 'global' => ini_get('session.save_handler') ?: 'files', 'access' => 'PHP_INI_ALL'],
                'session.save_path' => ['local' => ini_get('session.save_path') ?: '(not set)', 'global' => ini_get('session.save_path') ?: '(not set)', 'access' => 'PHP_INI_ALL'],
                'session.gc_maxlifetime' => ['local' => ini_get('session.gc_maxlifetime') ?: '1440', 'global' => ini_get('session.gc_maxlifetime') ?: '1440', 'access' => 'PHP_INI_ALL'],
                'session.cookie_httponly' => ['local' => ini_get('session.cookie_httponly') ? 'On' : 'Off', 'global' => ini_get('session.cookie_httponly') ? 'On' : 'Off', 'access' => 'PHP_INI_ALL'],
                'session.use_strict_mode' => ['local' => ini_get('session.use_strict_mode') ? 'On' : 'Off', 'global' => ini_get('session.use_strict_mode') ? 'On' : 'Off', 'access' => 'PHP_INI_ALL'],
            ],
            'File Uploads' => [
                'file_uploads' => ['local' => ini_get('file_uploads') ? 'On' : 'Off', 'global' => ini_get('file_uploads') ? 'On' : 'Off', 'access' => 'PHP_INI_SYSTEM'],
                'upload_tmp_dir' => ['local' => ini_get('upload_tmp_dir') ?: '(not set)', 'global' => ini_get('upload_tmp_dir') ?: '(not set)', 'access' => 'PHP_INI_SYSTEM'],
                'post_max_size' => ['local' => ini_get('post_max_size'), 'global' => ini_get('post_max_size'), 'access' => 'PHP_INI_PERDIR'],
                'upload_max_filesize' => ['local' => ini_get('upload_max_filesize'), 'global' => ini_get('upload_max_filesize'), 'access' => 'PHP_INI_PERDIR'],
                'max_file_uploads' => ['local' => ini_get('max_file_uploads'), 'global' => ini_get('max_file_uploads'), 'access' => 'PHP_INI_SYSTEM'],
            ],
        ];
    }

    public function opcache(): array
    {
        if (! function_exists('opcache_get_status') || ! ini_get('opcache.enable')) {
            return ['enabled' => false, 'message' => 'OPcache is not enabled'];
        }

        $status = @opcache_get_status(false);
        $config = @opcache_get_configuration();

        return [
            'enabled' => true,
            'status' => $status,
            'configuration' => $config,
            'jit' => ini_get('opcache.jit') ?: 'Off',
            'memory_usage' => [
                'used' => $status['memory_usage']['used_memory'] ?? 0,
                'free' => $status['memory_usage']['free_memory'] ?? 0,
                'wasted' => $status['memory_usage']['wasted_memory'] ?? 0,
                'wasted_percentage' => $status['memory_usage']['current_wasted_percentage'] ?? 0,
            ],
            'stats' => [
                'hits' => $status['opcache_statistics']['hits'] ?? 0,
                'misses' => $status['opcache_statistics']['misses'] ?? 0,
                'cache_full' => $status['opcache_statistics']['oom_restarts'] ?? 0,
                'restart_pending' => $status['opcache_statistics']['restart_pending'] ?? false,
                'num_cached_scripts' => $status['opcache_statistics']['num_cached_scripts'] ?? 0,
                'num_cached_keys' => $status['opcache_statistics']['num_cached_keys'] ?? 0,
                'max_cached_keys' => $status['opcache_statistics']['max_cached_keys'] ?? 0,
                'hit_rate' => $status['opcache_statistics']['hit_rate'] ?? 0,
                'start_time' => $status['opcache_statistics']['start_time'] ?? null,
                'last_restart_time' => $status['opcache_statistics']['last_restart_time'] ?? null,
            ],
        ];
    }

    public function xdebug(): array
    {
        if (! extension_loaded('xdebug')) {
            return ['enabled' => false, 'message' => 'Xdebug is not loaded'];
        }

        return [
            'enabled' => true,
            'version' => phpversion('xdebug'),
            'mode' => ini_get('xdebug.mode'),
            'client_host' => ini_get('xdebug.client_host'),
            'client_port' => ini_get('xdebug.client_port'),
            'max_nesting_level' => ini_get('xdebug.max_nesting_level'),
            'start_with_request' => ini_get('xdebug.start_with_request'),
            'cli_color' => ini_get('xdebug.cli_color'),
            'idekey' => ini_get('xdebug.idekey'),
        ];
    }

    public function extensions(): array
    {
        $loaded = get_loaded_extensions();
        sort($loaded);

        $categories = [
            'Core' => ['Core', 'date', 'libxml', 'openssl', 'pcre', 'Reflection', 'SPL', 'standard', 'session', 'json', 'random', 'filter', 'hash', 'zlib', 'sodium'],
            'Database' => ['mysqlnd', 'PDO', 'mysqli', 'pdo_mysql', 'pdo_pgsql', 'pdo_sqlite', 'sqlite3', 'pgsql', 'mongodb', 'redis'],
            'Caching' => ['Zend OPcache', 'apcu', 'igbinary', 'msgpack', 'memcached'],
            'XML' => ['xml', 'dom', 'simplexml', 'xmlreader', 'xmlwriter', 'xsl', 'soap'],
            'Images & Media' => ['gd', 'imagick', 'exif'],
            'CLI & Process' => ['pcntl', 'posix', 'readline', 'shmop', 'sysvmsg', 'sysvsem', 'sysvshm', 'ftp'],
            'Text & Locale' => ['mbstring', 'intl', 'ctype', 'iconv', 'gettext', 'tokenizer', 'bcmath', 'calendar'],
            'Networking' => ['curl', 'sockets', 'ldap', 'imap', 'swoole'],
            'Security' => ['sodium', 'openssl'],
            'Debug' => ['Xdebug', 'pcov', 'fileinfo'],
            'File & Compression' => ['Phar', 'zip', 'FFI'],
        ];

        $categorized = [];
        $uncategorized = $loaded;

        foreach ($categories as $category => $exts) {
            $found = [];
            foreach ($exts as $ext) {
                $idx = array_search($ext, $uncategorized);
                if ($idx !== false) {
                    $found[] = $ext;
                    unset($uncategorized[$idx]);
                }
            }
            if (! empty($found)) {
                $categorized[$category] = $found;
            }
        }

        if (! empty($uncategorized)) {
            $categorized['Other'] = array_values($uncategorized);
        }

        return [
            'total' => count($loaded),
            'categorized' => $categorized,
            'list' => $loaded,
        ];
    }

    public function errorLog(int $lines = 20): array
    {
        $errorLogPath = ini_get('error_log');

        if (! $errorLogPath || ! file_exists($errorLogPath) || ! is_readable($errorLogPath)) {
            return ['available' => false, 'entries' => [], 'path' => $errorLogPath ?: '(not set)'];
        }

        $output = @shell_exec("tail -n {$lines} ".escapeshellarg($errorLogPath).' 2>&1');

        if (! $output) {
            return ['available' => true, 'entries' => [], 'path' => $errorLogPath];
        }

        $entries = array_reverse(array_filter(explode("\n", trim($output))));

        return ['available' => true, 'entries' => $entries, 'path' => $errorLogPath];
    }

    public function fpmStatus(): array
    {
        $phpVer = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

        $candidates = [
            "/usr/sbin/php-fpm{$phpVer}",
            "/usr/local/sbin/php-fpm{$phpVer}",
            '/usr/sbin/php-fpm',
            '/usr/local/sbin/php-fpm',
        ];

        $fpmBin = null;

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                $fpmBin = $candidate;
                break;
            }
        }

        if (! $fpmBin) {
            return ['installed' => false];
        }

        $poolDir = "/etc/php/{$phpVer}/fpm/pool.d";
        $pools = [];
        if (is_dir($poolDir)) {
            $poolFiles = glob($poolDir.'/*.conf');
            foreach ($poolFiles as $file) {
                $pools[] = ['file' => basename($file), 'path' => $file];
            }
        }

        $config = @shell_exec($fpmBin.' -tt 2>&1');
        $configOk = ! str_contains($config ?? '', 'ERROR');

        $iniPath = "/etc/php/{$phpVer}/fpm/php.ini";
        $confDDir = "/etc/php/{$phpVer}/fpm/conf.d";
        $confDFiles = [];
        if (is_dir($confDDir)) {
            $found = glob($confDDir.'/*.ini');
            sort($found);
            foreach ($found as $file) {
                $confDFiles[] = [
                    'path' => $file,
                    'exists' => true,
                    'readable' => is_readable($file),
                ];
            }
        }

        return [
            'installed' => true,
            'binary' => $fpmBin,
            'version' => trim($phpVer ?? ''),
            'pools' => $pools,
            'config_ok' => $configOk,
            'config_test' => $config,
            'ini_path' => file_exists($iniPath) ? $iniPath : null,
            'conf_d_dir' => is_dir($confDDir) ? $confDDir : null,
            'conf_d_files' => $confDFiles,
        ];
    }

    public function swoole(): array
    {
        if (! extension_loaded('swoole')) {
            return ['enabled' => false];
        }

        return [
            'enabled' => true,
            'version' => phpversion('swoole'),
            'server' => config('octane.server'),
            'workers' => config('octane.workers'),
            'max_requests' => config('octane.max_requests'),
        ];
    }

    public function all(): array
    {
        return [
            'version' => $this->version(),
            'ini' => $this->iniSettings(),
            'opcache' => $this->opcache(),
            'xdebug' => $this->xdebug(),
            'extensions' => $this->extensions(),
            'error_log' => $this->errorLog(),
            'fpm' => $this->fpmStatus(),
            'swoole' => $this->swoole(),
            'env' => [
                'APP_ENV' => config('app.env'),
                'APP_DEBUG' => config('app.debug'),
                'OCTANE_SERVER' => config('octane.server'),
            ],
        ];
    }

    private function errorLevelName(int $level): string
    {
        $levels = [
            E_ALL => 'E_ALL',
            E_ALL & ~E_DEPRECATED & ~E_STRICT => 'E_ALL & ~E_DEPRECATED & ~E_STRICT',
            E_DEPRECATED => 'E_DEPRECATED',
            E_WARNING => 'E_WARNING',
            E_NOTICE => 'E_NOTICE',
            E_ERROR => 'E_ERROR',
            E_PARSE => 'E_PARSE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        ];

        return $levels[$level] ?? (string) $level;
    }
}
