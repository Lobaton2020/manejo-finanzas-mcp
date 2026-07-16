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
            return $this->errorResponse($e->getMessage());
        }
    }

    protected function successResponse(array $data, string $message = ''): array
    {
        $response = ['success' => true];
        if ($message) {
            $response['message'] = $message;
        }
        $response['data'] = $data;

        return [
            'content' => [
                'type' => 'text',
                'text' => json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ]
        ];
    }

    protected function errorResponse(string $message, ?string $suggestion = null): array
    {
        $response = [
            'success' => false,
            'error' => $message
        ];
        if ($suggestion) {
            $response['suggestion'] = $suggestion;
        }

        return [
            'content' => [
                'type' => 'text',
                'text' => json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ]
        ];
    }

    protected function listResponse(array $items, string $itemLabel = 'items'): array
    {
        return [
            'content' => [
                'type' => 'text',
                'text' => json_encode([
                    'success' => true,
                    'count' => count($items),
                    $itemLabel => $items
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ]
        ];
    }

    protected function validationError(string $message, ?string $hint = null): array
    {
        $response = [
            'valid' => false,
            'error' => $message
        ];
        if ($hint) {
            $hint .= '. ';
            $hint .= match(true) {
                str_contains($message, 'tipo') => 'Usa get_outflow_types o get_inflow_types para ver los disponibles.',
                str_contains($message, 'categor') => 'Usa get_categories para ver las disponibles.',
                str_contains($message, 'depósito') || str_contains($message, 'deposit') => 'Usa get_available_by_deposits para ver los depósitos y sus balances.',
                str_contains($message, 'usuario') => 'Verifica que el usuario existe y está activo.',
                default => 'Revisa los parámetros enviados.'
            };
            $response['hint'] = $hint;
        }

        return [
            'content' => [
                'type' => 'text',
                'text' => json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ]
        ];
    }
}
