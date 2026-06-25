<?php

namespace App\Actions\Php;

class PhpConfig
{
    public function sapiIniPaths(): array
    {
        $base = '/etc/php/8.4';
        $sapis = [];

        foreach (['cli', 'apache2', 'fpm'] as $sapi) {
            $iniPath = "{$base}/{$sapi}/php.ini";
            $confDir = "{$base}/{$sapi}/conf.d";

            $sapis[$sapi] = [
                'ini_file' => file_exists($iniPath) ? $iniPath : null,
                'conf_dir' => is_dir($confDir) ? $confDir : null,
                'files' => [],
            ];

            $sapis[$sapi]['files'][] = [
                'path' => $iniPath,
                'exists' => file_exists($iniPath),
                'readable' => is_readable($iniPath),
            ];

            if (is_dir($confDir)) {
                $confFiles = glob($confDir.'/*.ini');
                sort($confFiles);
                foreach ($confFiles as $file) {
                    $sapis[$sapi]['files'][] = [
                        'path' => $file,
                        'exists' => true,
                        'readable' => is_readable($file),
                    ];
                }
            }
        }

        return $sapis;
    }

    public function modsAvailable(): array
    {
        $modsDir = '/etc/php/8.4/mods-available';
        if (! is_dir($modsDir)) {
            return [];
        }

        $mods = [];
        $files = glob($modsDir.'/*.ini');
        sort($files);

        foreach ($files as $file) {
            $name = basename($file, '.ini');
            $content = file_get_contents($file);
            $enabledInCli = is_file('/etc/php/8.4/cli/conf.d/'.basename($file));
            $enabledInApache = is_file('/etc/php/8.4/apache2/conf.d/'.basename($file));
            $enabledInFpm = is_file('/etc/php/8.4/fpm/conf.d/'.basename($file));

            preg_match('/^;\s*(.*?)$/m', $content, $titleMatch);

            $mods[$name] = [
                'name' => $name,
                'file' => $file,
                'content' => $content,
                'enabled' => [
                    'cli' => $enabledInCli,
                    'apache2' => $enabledInApache,
                    'fpm' => $enabledInFpm,
                ],
                'description' => $titleMatch[1] ?? '',
            ];
        }

        return $mods;
    }

    public function allSettings(): array
    {
        $categories = [];

        $settings = [
            'Core' => [
                'engine' => 'On|Off',
                'short_open_tag' => 'On|Off',
                'precision' => 'integer',
                'output_buffering' => 'integer|On|Off',
                'zlib.output_compression' => 'On|Off|integer',
                'implicit_flush' => 'On|Off',
                'serialize_precision' => 'integer',
                'zend.enable_gc' => 'On|Off',
                'expose_php' => 'On|Off',
                'max_execution_time' => 'integer',
                'max_input_time' => 'integer',
                'memory_limit' => 'size',
                'default_charset' => 'string',
                'default_mimetype' => 'string',
                'date.timezone' => 'string',
            ],
            'Error handling and logging' => [
                'error_reporting' => 'integer|constants',
                'display_errors' => 'On|Off|stderr',
                'display_startup_errors' => 'On|Off',
                'log_errors' => 'On|Off',
                'log_errors_max_len' => 'integer',
                'ignore_repeated_errors' => 'On|Off',
                'ignore_repeated_source' => 'On|Off',
                'report_memleaks' => 'On|Off',
                'error_log' => 'string',
                'error_log_mode' => 'octal',
                'syslog.facility' => 'string',
                'syslog.ident' => 'string',
            ],
            'Data Handling' => [
                'variables_order' => 'string',
                'request_order' => 'string',
                'register_argc_argv' => 'On|Off',
                'auto_globals_jit' => 'On|Off',
                'enable_post_data_reading' => 'On|Off',
                'post_max_size' => 'size',
                'auto_prepend_file' => 'string',
                'auto_append_file' => 'string',
                'default_mimetype' => 'string',
                'default_charset' => 'string',
                'always_populate_raw_post_data' => 'On|Off|integer',
                'arg_separator.output' => 'string',
                'arg_separator.input' => 'string',
            ],
            'Security' => [
                'allow_url_fopen' => 'On|Off',
                'allow_url_include' => 'On|Off',
                'disable_functions' => 'string',
                'disable_classes' => 'string',
                'enable_dl' => 'On|Off',
                'max_file_uploads' => 'integer',
                'session.use_strict_mode' => 'On|Off',
                'session.cookie_httponly' => 'On|Off',
                'session.cookie_secure' => 'On|Off',
                'session.cookie_samesite' => 'string',
                'session.use_only_cookies' => 'On|Off',
                'session.sid_length' => 'integer',
                'session.sid_bits_per_character' => 'integer',
                'session.hash_function' => 'integer|string',
            ],
            'File Uploads' => [
                'file_uploads' => 'On|Off',
                'upload_tmp_dir' => 'string',
                'upload_max_filesize' => 'size',
                'max_file_uploads' => 'integer',
            ],
            'Paths and Directories' => [
                'doc_root' => 'string',
                'user_dir' => 'string',
                'extension_dir' => 'string',
                'sys_temp_dir' => 'string',
                'include_path' => 'string',
            ],
            'Performance' => [
                'opcache.enable' => 'On|Off',
                'opcache.memory_consumption' => 'integer',
                'opcache.interned_strings_buffer' => 'integer',
                'opcache.max_accelerated_files' => 'integer',
                'opcache.revalidate_freq' => 'integer',
                'opcache.validate_timestamps' => 'On|Off',
                'opcache.jit' => 'string',
                'opcache.jit_buffer_size' => 'size',
                'pcre.jit' => 'On|Off',
            ],
            'Session' => [
                'session.save_handler' => 'string',
                'session.save_path' => 'string',
                'session.name' => 'string',
                'session.auto_start' => 'On|Off',
                'session.gc_probability' => 'integer',
                'session.gc_divisor' => 'integer',
                'session.gc_maxlifetime' => 'integer',
                'session.serialize_handler' => 'string',
                'session.cookie_lifetime' => 'integer',
                'session.cookie_path' => 'string',
                'session.cookie_domain' => 'string',
                'session.cookie_secure' => 'On|Off',
                'session.cookie_httponly' => 'On|Off',
                'session.cookie_samesite' => 'string',
                'session.use_strict_mode' => 'On|Off',
                'session.use_cookies' => 'On|Off',
                'session.use_only_cookies' => 'On|Off',
                'session.referer_check' => 'string',
                'session.cache_limiter' => 'string',
                'session.cache_expire' => 'integer',
                'session.sid_length' => 'integer',
                'session.sid_bits_per_character' => 'integer',
            ],
            'Mail' => [
                'SMTP' => 'string',
                'smtp_port' => 'integer',
                'sendmail_from' => 'string',
                'sendmail_path' => 'string',
                'mail.log' => 'string',
                'mail.mixed_lf_and_crlf' => 'On|Off',
            ],
        ];

        foreach ($settings as $category => $directives) {
            $items = [];
            foreach ($directives as $directive => $type) {
                $local = ini_get($directive);
                $items[$directive] = [
                    'local' => $local !== false ? ($local === '' ? '(empty)' : $local) : '(not set)',
                    'type' => $type,
                    'changeable' => $this->isChangeable($directive),
                ];
            }
            $categories[$category] = $items;
        }

        return $categories;
    }

    public function isChangeable(string $directive): bool
    {
        $access = @ini_get($directive);

        if ($access === false) {
            return false;
        }

        $changeableIni = @ini_get_all($directive);

        if ($changeableIni === false) {
            return true;
        }

        $accessLevel = $changeableIni[$directive]['access'] ?? 0;

        return $accessLevel === 1 || $accessLevel === 7;
    }

    public function phpInfo(): string
    {
        ob_start();
        phpinfo();

        return ob_get_clean();
    }
}
