<?php

declare(strict_types=1);
/**
 * This file is part of Simps.
 *
 * @link     https://simps.io
 * @document https://doc.simps.io
 * @license  https://github.com/simple-swoole/simps/blob/master/LICENSE
 */

namespace Simps\Server\Protocol\HTTP;

class SimpleResponse
{
    /**
     * @var string
     */
    protected static $_version = '1.1';

    /**
     * @var array
     */
    protected static $_phrases = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /**
     * @param string $body
     * @param int $status
     * @return string
     */
    public static function build($body = '', $status = 200, array $headers = [])
    {
        $reason = static::$_phrases[$status];
        $body_len = \strlen($body);
        $version = static::$_version;
        if (empty($headers)) {
            return "HTTP/{$version} {$status} {$reason}\r\nServer: simps-http-server\r\nContent-Type: text/html;charset=utf-8\r\nContent-Length: {$body_len}\r\nConnection: keep-alive\r\n\r\n{$body}";
        }

        $head = "HTTP/{$version} {$status} {$reason}\r\n";
        $headers = $headers;
        if (! isset($headers['Server'])) {
            $head .= "Server: simps-http-server\r\n";
        }
        foreach ($headers as $name => $value) {
            if (\is_array($value)) {
                foreach ($value as $item) {
                    $head .= "{$name}: {$item}\r\n";
                }
                continue;
            }
            $head .= "{$name}: {$value}\r\n";
        }

        if (! isset($headers['Connection'])) {
            $head .= "Connection: keep-alive\r\n";
        }

        if (! isset($headers['Content-Type'])) {
            $head .= "Content-Type: text/html;charset=utf-8\r\n";
        } else {
            if ($headers['Content-Type'] === 'text/event-stream') {
                return $head . $body;
            }
        }

        if (! isset($headers['Transfer-Encoding'])) {
            $head .= "Content-Length: {$body_len}\r\n\r\n";
        } else {
            return "{$head}\r\n" . dechex($body_len) . "\r\n{$body}\r\n";
        }

        return $head . $body;
    }
}
