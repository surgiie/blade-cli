<?php

namespace BladeCLI\Tests\Files;

use BladeCLI\Tests\Support\Contracts\TestableFile;

class TestNginxFile implements TestableFile
{
    /**
     * The filename.
     *
     * @return string
     */
    public function filename(): string
    {
        return 'test_nginx.conf';
    }

    /**
     * The content of the file.
     *
     * @return string
     */
    public function content(): string
    {
        return <<<EOL
        server {
            server_name   {{\$serverName}};

            access_log   {{\$accessLogPath}}  main;

            location /{{\$endpoint}} {
                @if(\$production ?? false)
                    root /data/www/production
                @else
                    root /data/www/staging
                @endif
            }
        }
        EOL;
    }

    /**
     * The data options for rendering.
     *
     * @return array
     */
    public function options(): array
    {
        return [
            '--server-name=example.com',
            '--endpoint=example',
            '--access-log-path=/var/log/nginx.access_log'
        ];
    }

    /**
     * The expected rendered content of the file.
     *
     * @return string
     */
    public function expectedContent(): string
    {
        return <<<EOL
        server {
            server_name   example.com;

            access_log   /var/log/nginx.access_log  main;

            location /example {
                    root /data/www/staging
            }
        }
        EOL;
    }
}
