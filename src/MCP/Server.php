<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

require_once __DIR__ . '/../../src/Database/Connection.php';
require_once __DIR__ . '/Tools/BaseTool.php';

use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;
use Mcp\Server\Transport\StreamableHttpTransport;
use Mcp\Server\Session\FileSessionStore;
use Nyholm\Psr7\Factory\Psr17Factory;

$sessionDir = '/tmp/finanzas-mcp-sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0755, true);
}
use Nyholm\Psr7Server\ServerRequestCreator;
use Tools\EgressMoney\GetOutflowTypesTool;
use Tools\InflowMoney\GetInflowTypesTool;
use Tools\EgressMoney\GetCategoriesTool;
use Tools\EgressMoney\GetAvailableByDepositsTool;
use Tools\EgressMoney\OutflowMoneyTool;
use Tools\EgressMoney\GetDepositsHistoryTool;
use Tools\InflowMoney\InflowMoneyTool;

require_once __DIR__ . '/Tools/EgressMoney/OutflowMoneyTool.php';
require_once __DIR__ . '/Tools/EgressMoney/GetDepositsHistoryTool.php';
require_once __DIR__ . '/Tools/EgressMoney/GetCategoriesTool.php';
require_once __DIR__ . '/Tools/EgressMoney/GetAvailableByDepositsTool.php';
require_once __DIR__ . '/Tools/InflowMoney/GetInflowTypesTool.php';
require_once __DIR__ . '/Tools/InflowMoney/InflowMoneyTool.php';

$server = Server::builder()
    ->setServerInfo('Finanzas MCP Server', '1.0.0')
    ->addTool([GetOutflowTypesTool::class, 'getOutflowTypes'], 'get_outflow_types')
    ->addTool([GetInflowTypesTool::class, 'getInflowTypes'], 'get_inflow_types')
    ->addTool([GetCategoriesTool::class, 'getCategories'], 'get_categories')
    ->addTool([GetAvailableByDepositsTool::class, 'getAvailableByDeposits'], 'get_available_by_deposits')
    ->addTool([OutflowMoneyTool::class, 'outflowMoney'], 'outflow_money')
    ->addTool([GetDepositsHistoryTool::class, 'getDepositsHistory'], 'get_deposits_history')
    ->addTool([InflowMoneyTool::class, 'inflowMoney'], 'inflow_money')
    ->setSession(new FileSessionStore($sessionDir))
    ->build();

$isHttp = isset($_SERVER['REQUEST_METHOD']);

if ($isHttp) {
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, Mcp-Session-Id');

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode([
            'name' => 'Finanzas MCP Server',
            'version' => '1.0.0',
            'status' => 'running'
        ]);
        exit;
    }

    $psr17Factory = new Psr17Factory();
    $serverRequestCreator = new ServerRequestCreator($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
    $serverRequest = $serverRequestCreator->fromGlobals();

    $transport = new StreamableHttpTransport(
        $serverRequest,
        $psr17Factory,
        $psr17Factory
    );

    $response = $server->run($transport);

    http_response_code($response->getStatusCode());
    foreach ($response->getHeaders() as $name => $values) {
        foreach ($values as $value) {
            header("$name: $value");
        }
    }
    echo $response->getBody();
} else {
    $transport = new StdioTransport();
    $server->run($transport);
}
