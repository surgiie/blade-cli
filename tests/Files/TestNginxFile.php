<?php

namespace Surgiie\BladeCLI\Tests\Files;

use Surgiie\BladeCLI\Tests\Support\TestingFile;

class TestNginxFile extends TestingFile
{
    /**
     * The filename.
     */
    public function filename(): string
    {
        return 'test_nginx.conf';
    }

    /**
     * The content of the file.
     */
    public function content(): string
    {
        return <<<EOL
        server {
            server_name   {{\$serverName}};

            access_log   {{\$accessLogPath}}  main;

            location /{{\$mainEndpoint}} {
                @if(\$production ?? false)
                root /data/www/production
                @else
                root /data/www/staging
                @endif
            }

            @foreach(\$apiEndpoint as \$endpoint)
            location {{\$endpoint}} {
                proxy_pass  api.com{{\$endpoint}};
            }
            @endforeach
        }
        EOL;
    }

    /**
     * The data to write to test loading data from env|json files.
     */
    public function fileData(): array
    {
        return [
            'server-name' => 'example.com',
            'main-endpoint' => 'example',
            'access-log-path' => '/var/log/nginx.access_log',
            'api-endpoint' => [
                '/api/v1/foo',
                '/api/v1/bar',
                '/api/v1/baz',
            ],
        ];
    }

    /**
     * The data options for rendering.
     */
    public function options(): array
    {
        return [
            '--server-name=example.com',
            '--main-endpoint=example',
            '--access-log-path=/var/log/nginx.access_log',
            '--api-endpoint=/api/v1/foo',
            '--api-endpoint=/api/v1/bar',
            '--api-endpoint=/api/v1/baz',
        ];
    }

    /**
     * The expected rendered content of the file.
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
            location /api/v1/foo {
                proxy_pass  api.com/api/v1/foo;
            }
            location /api/v1/bar {
                proxy_pass  api.com/api/v1/bar;
            }
            location /api/v1/baz {
                proxy_pass  api.com/api/v1/baz;
            }
        }
        EOL;
    }
}
