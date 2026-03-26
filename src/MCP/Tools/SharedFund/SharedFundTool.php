<?php

declare(strict_types=1);

namespace Tools\SharedFund;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class SharedFundTool extends BaseTool
{
    private const DATA_FILE = '/var/www/manejo-finanzas-mcp/data/shared_fund_data.json';
    
    private function loadData(): array {
        if (!file_exists(self::DATA_FILE)) {
            return [];
        }
        $content = file_get_contents(self::DATA_FILE);
        return json_decode($content, true) ?? [];
    }
    
    private function saveData(array $data): void {
        file_put_contents(self::DATA_FILE, json_encode($data, JSON_PRETTY_PRINT));
    }
    
    #[McpTool(name: 'shared_fund_add')]
    public function addContribution(
        float $amount,
        int $month,
        int $year,
        string $who  // 'andres' o 'ivan'
    ): array {
        $data = $this->loadData();
        $key = "$year-$month";
        
        if (!isset($data[$key])) {
            $data[$key] = [
                'andres' => 0,
                'ivan' => 0,
                'total' => 0
            ];
        }
        
        $data[$key][$who] += $amount;
        $data[$key]['total'] = $data[$key]['andres'] + $data[$key]['ivan'];
        
        $this->saveData($data);
        
        return [
            'content' => [
                'type' => 'text',
                'text' => json_encode([
                    'success' => true,
                    'message' => "Contribución agregada. {$who} aportó $" . number_format($amount) . " al fondo compartido ($key)",
                    'data' => $data[$key]
                ], JSON_PRETTY_PRINT)
            ]
        ];
    }
    
    #[McpTool(name: 'shared_fund_summary')]
    public function getSummary(): array {
        $data = $this->loadData();
        
        $totalAndres = 0;
        $totalIvan = 0;
        
        foreach ($data as $period => $values) {
            $totalAndres += $values['andres'];
            $totalIvan += $values['ivan'];
        }
        
        return [
            'content' => [
                'type' => 'text',
                'text' => json_encode([
                    'total_acumulado' => $totalAndres + $totalIvan,
                    'andres' => $totalAndres,
                    'ivan' => $totalIvan,
                    'meses' => $data
                ], JSON_PRETTY_PRINT)
            ]
        ];
    }
}
