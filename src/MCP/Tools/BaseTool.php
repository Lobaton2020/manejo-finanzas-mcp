<?php

declare(strict_types=1);

namespace Tools;

use Illuminate\Database\Capsule\Manager as Capsule;

class BaseTool
{
    protected function getConnection(): Capsule
    {
        return \Connection::getCapsule();
    }

    protected function table(string $table)
    {
        return \Connection::table($table);
    }

    protected function transaction(callable $callback)
    {
        $capsule = $this->getConnection();
        return $capsule->connection()->transaction($callback);
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        $logDir = '/tmp/finanzas-mcp-logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $date = date('Y-m-d');
        $logFile = $logDir . '/' . $date . '.log';

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    protected function executeWithLogging(callable $callback, string $toolName, array $params = []): array
    {
        $this->log('INFO', "Tool '$toolName' started", $params);

        try {
            $result = $callback();
            $this->log('INFO', "Tool '$toolName' completed successfully", $params);
            return $result;
        } catch (\Exception $e) {
            $this->log('ERROR', "Tool '$toolName' failed: " . $e->getMessage(), [
                'params' => $params,
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return [
                'content' => [
                    'type' => 'text',
                    'text' => 'Error: ' . $e->getMessage()
                ]
            ];
        }
    }
}
