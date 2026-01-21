<?php namespace App\Lib;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;

class Logger extends MonoLogger
{
    private static array $loggers = [];

    private function __construct(string $key, int $level)
    {
        parent::__construct($key);

        // stdout works everywhere (local, App Platform, serverless)
        $this->pushHandler(
            new StreamHandler('php://stdout', $level)
        );
    }

    public static function getInstance(
        string $key = 'app',
        int $level = MonoLogger::INFO
    ): self {
        if (!isset(self::$loggers[$key])) {
            self::$loggers[$key] = new self($key, $level);
        }

        return self::$loggers[$key];
    }

    /**
     * Optional request logger
     */
    public static function logRequest(): void
    {
        $logger = self::getInstance('request');

        $logger->info('REQUEST', [
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'uri'    => $_SERVER['REQUEST_URI'] ?? null,
            'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
            'body'   => trim(file_get_contents('php://input'))
        ]);
    }
}
