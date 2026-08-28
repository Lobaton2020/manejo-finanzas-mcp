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
            @mkdir($logDir, 0755, true);
        }

        $date = date('Y-m-d');
        $logFile = $logDir . '/' . $date . '.log';

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;

        @file_put_contents($logFile, $logMessage, FILE_APPEND);
    }

    protected function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    protected function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    protected function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    protected function executeWithLogging(callable $callback, string $toolName, array $params = []): array
    {
        $this->info("Tool '$toolName' started", $params);
        $this->debug("Tool '$toolName' entered", ['params_keys' => array_keys($params)]);

        try {
            $result = $callback();
            $this->info("Tool '$toolName' completed successfully", $params);
            $this->debug("Tool '$toolName' returned", [
                'has_content' => isset($result['content']),
                'text_len' => isset($result['content']['text']) ? strlen($result['content']['text']) : 0,
            ]);
            return $result;
        } catch (\Exception $e) {
            $this->error("Tool '$toolName' failed: " . $e->getMessage(), [
                'params' => $params,
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->debug("Tool '$toolName' exception", [
                'message' => $e->getMessage(),
                'trace_head' => array_slice(explode("\n", $e->getTraceAsString()), 0, 5),
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

    protected function validationError(string $message, ?string $hint = null): array
    {
        $response = [
            'success' => false,
            'error' => $message
        ];
        $auto = match(true) {
            str_contains($message, 'tipo') => 'Usa get_outflow_types o get_inflow_types para ver los disponibles.',
            str_contains($message, 'categor') => 'Usa get_categories para ver las disponibles.',
            str_contains($message, 'depósito') || str_contains($message, 'deposit') || str_contains($message, 'porcent') || str_contains($message, 'deposito') => 'Usa get_available_by_deposits para ver los depositos y sus balances.',
            str_contains($message, 'usuario') => 'Verifica que el usuario existe y esta activo.',
            str_contains($message, 'grupo') => 'Usa get_investment_groups para ver los grupos del usuario.',
            default => 'Revisa los parametros enviados.'
        };
        $response['hint'] = ($hint ? $hint . '. ' : '') . $auto;

        return [
            'content' => [
                'type' => 'text',
                'text' => json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            ]
        ];
    }

    protected function requireUser(int $idUser): array
    {
        $user = $this->table('users')
            ->where('id_user', $idUser)
            ->where('status', 1)
            ->first();
        return $user ? (array) $user : [];
    }

    protected function userNotFound(): array
    {
        return $this->validationError('El usuario no existe o esta inactivo.');
    }
}