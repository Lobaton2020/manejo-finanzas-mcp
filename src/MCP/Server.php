<?php

declare(strict_types=1);

date_default_timezone_set('America/Bogota');

use Mcp\Server;
use Mcp\Server\Transport\StdioTransport;
use Mcp\Server\Transport\StreamableHttpTransport;
use Mcp\Server\Session\FileSessionStore;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

use Tools\EgressMoney\GetOutflowTypesTool;
use Tools\InflowMoney\GetInflowTypesTool;
use Tools\EgressMoney\GetCategoriesTool;
use Tools\EgressMoney\GetAvailableByDepositsTool;
use Tools\EgressMoney\OutflowMoneyTool;
use Tools\EgressMoney\GetDepositsHistoryTool;
use Tools\EgressMoney\GetOutflowsByMonthTool;
use Tools\EgressMoney\GetInvestmentGroupsTool;
use Tools\InflowMoney\InflowMoneyTool;
use Tools\EgressMoney\GetExpenseForecastTool;
use Tools\EgressMoney\ListOutflowsTool;
use Tools\EgressMoney\GetOutflowTool;
use Tools\EgressMoney\UpdateOutflowTool;
use Tools\InflowMoney\ListInflowsTool;
use Tools\InflowMoney\GetInflowTool;
use Tools\InflowMoney\UpdateInflowTool;
use Tools\Lookups\CreateOutflowTypeTool;
use Tools\Lookups\UpdateOutflowTypeTool;
use Tools\Lookups\DisableOutflowTypeTool;
use Tools\Lookups\EnableOutflowTypeTool;
use Tools\Lookups\CreateInflowTypeTool;
use Tools\Lookups\UpdateInflowTypeTool;
use Tools\Lookups\DisableInflowTypeTool;
use Tools\Lookups\EnableInflowTypeTool;
use Tools\Lookups\CreateCategoryTool;
use Tools\Lookups\UpdateCategoryTool;
use Tools\Lookups\DisableCategoryTool;
use Tools\Lookups\EnableCategoryTool;
use Tools\Lookups\CreateDepositTool;
use Tools\Lookups\ListDepositsTool;
use Tools\Lookups\UpdateDepositTool;
use Tools\Lookups\DisableDepositTool;
use Tools\Lookups\EnableDepositTool;
use Tools\Investments\ListInvestmentsTool;
use Tools\Investments\GetInvestmentTool;
use Tools\Investments\UpdateInvestmentTool;
use Tools\Investments\HideInvestmentTool;
use Tools\Investments\ListInvestmentRetirementsTool;
use Tools\Investments\CreateInvestmentRetirementTool;
use Tools\EgressMoney\CreateInvestmentGroupTool;
use Tools\EgressMoney\UpdateInvestmentGroupTool;
use Tools\Budgets\GetMonthlyBudgetTool;
use Tools\Budgets\SetMonthlyBudgetTool;
use Tools\Budgets\ListTemporalBudgetsTool;
use Tools\Budgets\CreateTemporalBudgetTool;
use Tools\Budgets\UpdateTemporalBudgetTool;
use Tools\Budgets\AddTemporalBudgetOutflowTool;
use Tools\Budgets\UpdateTemporalBudgetOutflowTool;
use Tools\Budgets\DisableTemporalBudgetOutflowTool;
use Tools\Budgets\EnableTemporalBudgetOutflowTool;
use Tools\Budgets\ExecuteTemporalBudgetTool;
use Tools\Budgets\ExecuteTemporalBudgetItemTool;
use Tools\Notes\ListNotesTool;
use Tools\Notes\CreateNoteTool;
use Tools\Notes\UpdateNoteTool;
use Tools\Notes\DisableNoteTool;
use Tools\Notifications\ListNotificationsTool;
use Tools\Notifications\MarkNotificationReadTool;
use Tools\Reports\GetNetWorthTool;
use Tools\Reports\GetNetWorthWithLoansTool;


try {
    require_once __DIR__ . '/../../vendor/autoload.php';

    require_once __DIR__ . '/../../src/Database/Connection.php';
    require_once __DIR__ . '/../../src/MCP/Tools/BaseTool.php';

    $sessionDir = '/tmp/finanzas-mcp-sessions';
    if (!is_dir($sessionDir)) {
        mkdir($sessionDir, 0755, true);
    }

    require_once __DIR__ . '/Tools/EgressMoney/OutflowMoneyTool.php';
    require_once __DIR__ . '/Tools/EgressMoney/GetDepositsHistoryTool.php';
    require_once __DIR__ . '/Tools/EgressMoney/GetOutflowsByMonthTool.php';
    require_once __DIR__ . '/Tools/EgressMoney/GetCategoriesTool.php';
    require_once __DIR__ . '/Tools/EgressMoney/GetAvailableByDepositsTool.php';
    require_once __DIR__ . '/Tools/EgressMoney/GetInvestmentGroupsTool.php';
    require_once __DIR__ . '/Tools/EgressMoney/GetExpenseForecastTool.php';
    require_once __DIR__ . '/Tools/EgressMoney/ListOutflowsTool.php';
    require_once __DIR__ . '/Tools/EgressMoney/GetOutflowTool.php';
    require_once __DIR__ . '/Tools/EgressMoney/UpdateOutflowTool.php';
    require_once __DIR__ . '/Tools/EgressMoney/CreateInvestmentGroupTool.php';
    require_once __DIR__ . '/Tools/EgressMoney/UpdateInvestmentGroupTool.php';
    require_once __DIR__ . '/Tools/InflowMoney/GetInflowTypesTool.php';
    require_once __DIR__ . '/Tools/InflowMoney/InflowMoneyTool.php';
    require_once __DIR__ . '/Tools/InflowMoney/ListInflowsTool.php';
    require_once __DIR__ . '/Tools/InflowMoney/GetInflowTool.php';
    require_once __DIR__ . '/Tools/InflowMoney/UpdateInflowTool.php';
    require_once __DIR__ . '/Tools/Lookups/CreateOutflowTypeTool.php';
    require_once __DIR__ . '/Tools/Lookups/UpdateOutflowTypeTool.php';
    require_once __DIR__ . '/Tools/Lookups/DisableOutflowTypeTool.php';
    require_once __DIR__ . '/Tools/Lookups/EnableOutflowTypeTool.php';
    require_once __DIR__ . '/Tools/Lookups/CreateInflowTypeTool.php';
    require_once __DIR__ . '/Tools/Lookups/UpdateInflowTypeTool.php';
    require_once __DIR__ . '/Tools/Lookups/DisableInflowTypeTool.php';
    require_once __DIR__ . '/Tools/Lookups/EnableInflowTypeTool.php';
    require_once __DIR__ . '/Tools/Lookups/CreateCategoryTool.php';
    require_once __DIR__ . '/Tools/Lookups/UpdateCategoryTool.php';
    require_once __DIR__ . '/Tools/Lookups/DisableCategoryTool.php';
    require_once __DIR__ . '/Tools/Lookups/EnableCategoryTool.php';
    require_once __DIR__ . '/Tools/Lookups/CreateDepositTool.php';
    require_once __DIR__ . '/Tools/Lookups/ListDepositsTool.php';
    require_once __DIR__ . '/Tools/Lookups/UpdateDepositTool.php';
    require_once __DIR__ . '/Tools/Lookups/DisableDepositTool.php';
    require_once __DIR__ . '/Tools/Lookups/EnableDepositTool.php';
    require_once __DIR__ . '/Tools/Investments/ListInvestmentsTool.php';
    require_once __DIR__ . '/Tools/Investments/GetInvestmentTool.php';
    require_once __DIR__ . '/Tools/Investments/UpdateInvestmentTool.php';
    require_once __DIR__ . '/Tools/Investments/HideInvestmentTool.php';
    require_once __DIR__ . '/Tools/Investments/ListInvestmentRetirementsTool.php';
    require_once __DIR__ . '/Tools/Investments/CreateInvestmentRetirementTool.php';
    require_once __DIR__ . '/Tools/Budgets/GetMonthlyBudgetTool.php';
    require_once __DIR__ . '/Tools/Budgets/SetMonthlyBudgetTool.php';
    require_once __DIR__ . '/Tools/Budgets/ListTemporalBudgetsTool.php';
    require_once __DIR__ . '/Tools/Budgets/CreateTemporalBudgetTool.php';
    require_once __DIR__ . '/Tools/Budgets/UpdateTemporalBudgetTool.php';
    require_once __DIR__ . '/Tools/Budgets/AddTemporalBudgetOutflowTool.php';
    require_once __DIR__ . '/Tools/Budgets/UpdateTemporalBudgetOutflowTool.php';
    require_once __DIR__ . '/Tools/Budgets/DisableTemporalBudgetOutflowTool.php';
    require_once __DIR__ . '/Tools/Budgets/EnableTemporalBudgetOutflowTool.php';
    require_once __DIR__ . '/Tools/Budgets/ExecuteTemporalBudgetTool.php';
    require_once __DIR__ . '/Tools/Budgets/ExecuteTemporalBudgetItemTool.php';
    require_once __DIR__ . '/Tools/Notes/ListNotesTool.php';
    require_once __DIR__ . '/Tools/Notes/CreateNoteTool.php';
    require_once __DIR__ . '/Tools/Notes/UpdateNoteTool.php';
    require_once __DIR__ . '/Tools/Notes/DisableNoteTool.php';
    require_once __DIR__ . '/Tools/Notifications/ListNotificationsTool.php';
    require_once __DIR__ . '/Tools/Notifications/MarkNotificationReadTool.php';
    require_once __DIR__ . '/Tools/Reports/GetNetWorthTool.php';
    require_once __DIR__ . '/Tools/Reports/GetNetWorthWithLoansTool.php';

    $server = Server::builder()
        ->setServerInfo('Finanzas MCP Server', '1.0.0')

        ->addTool([GetOutflowTypesTool::class, 'getOutflowTypes'], 'get_outflow_types')
        ->addTool([GetInflowTypesTool::class, 'getInflowTypes'], 'get_inflow_types')
        ->addTool([GetCategoriesTool::class, 'getCategories'], 'get_categories')
        ->addTool([GetAvailableByDepositsTool::class, 'getAvailableByDeposits'], 'get_available_by_deposits')
        ->addTool([OutflowMoneyTool::class, 'outflowMoney'], 'outflow_money')
        ->addTool([GetDepositsHistoryTool::class, 'getDepositsHistory'], 'get_deposits_history')
        ->addTool([GetOutflowsByMonthTool::class, 'getOutflowsByMonth'], 'get_outflows_by_month')
        ->addTool([GetInvestmentGroupsTool::class, 'getInvestmentGroups'], 'get_investment_groups')
        ->addTool([InflowMoneyTool::class, 'inflowMoney'], 'inflow_money')
        ->addTool([GetExpenseForecastTool::class, 'getExpenseForecast'], 'get_expense_forecast')

        ->addTool([ListOutflowsTool::class, 'listOutflows'], 'list_outflows')
        ->addTool([GetOutflowTool::class, 'getOutflow'], 'get_outflow')
        ->addTool([UpdateOutflowTool::class, 'updateOutflow'], 'update_outflow')
        ->addTool([ListInflowsTool::class, 'listInflows'], 'list_inflows')
        ->addTool([GetInflowTool::class, 'getInflow'], 'get_inflow')
        ->addTool([UpdateInflowTool::class, 'updateInflow'], 'update_inflow')

        ->addTool([CreateOutflowTypeTool::class, 'createOutflowType'], 'create_outflow_type')
        ->addTool([UpdateOutflowTypeTool::class, 'updateOutflowType'], 'update_outflow_type')
        ->addTool([DisableOutflowTypeTool::class, 'disableOutflowType'], 'disable_outflow_type')
        ->addTool([EnableOutflowTypeTool::class, 'enableOutflowType'], 'enable_outflow_type')
        ->addTool([CreateInflowTypeTool::class, 'createInflowType'], 'create_inflow_type')
        ->addTool([UpdateInflowTypeTool::class, 'updateInflowType'], 'update_inflow_type')
        ->addTool([DisableInflowTypeTool::class, 'disableInflowType'], 'disable_inflow_type')
        ->addTool([EnableInflowTypeTool::class, 'enableInflowType'], 'enable_inflow_type')
        ->addTool([CreateCategoryTool::class, 'createCategory'], 'create_category')
        ->addTool([UpdateCategoryTool::class, 'updateCategory'], 'update_category')
        ->addTool([DisableCategoryTool::class, 'disableCategory'], 'disable_category')
        ->addTool([EnableCategoryTool::class, 'enableCategory'], 'enable_category')
        ->addTool([CreateDepositTool::class, 'createDeposit'], 'create_deposit')
        ->addTool([ListDepositsTool::class, 'getDeposits'], 'get_deposits')
        ->addTool([UpdateDepositTool::class, 'updateDeposit'], 'update_deposit')
        ->addTool([DisableDepositTool::class, 'disableDeposit'], 'disable_deposit')
        ->addTool([EnableDepositTool::class, 'enableDeposit'], 'enable_deposit')

        ->addTool([ListInvestmentsTool::class, 'listInvestments'], 'list_investments')
        ->addTool([GetInvestmentTool::class, 'getInvestment'], 'get_investment')
        ->addTool([UpdateInvestmentTool::class, 'updateInvestment'], 'update_investment')
        ->addTool([HideInvestmentTool::class, 'hideInvestment'], 'hide_investment')
        ->addTool([ListInvestmentRetirementsTool::class, 'listInvestmentRetirements'], 'list_investment_retirements')
        ->addTool([CreateInvestmentRetirementTool::class, 'createInvestmentRetirement'], 'create_investment_retirement')
        ->addTool([CreateInvestmentGroupTool::class, 'createInvestmentGroup'], 'create_investment_group')
        ->addTool([UpdateInvestmentGroupTool::class, 'updateInvestmentGroup'], 'update_investment_group')

        ->addTool([GetMonthlyBudgetTool::class, 'getMonthlyBudget'], 'get_monthly_budget')
        ->addTool([SetMonthlyBudgetTool::class, 'setMonthlyBudget'], 'set_monthly_budget')
        ->addTool([ListTemporalBudgetsTool::class, 'listTemporalBudgets'], 'list_temporal_budgets')
        ->addTool([CreateTemporalBudgetTool::class, 'createTemporalBudget'], 'create_temporal_budget')
        ->addTool([UpdateTemporalBudgetTool::class, 'updateTemporalBudget'], 'update_temporal_budget')
        ->addTool([AddTemporalBudgetOutflowTool::class, 'addTemporalBudgetOutflow'], 'add_temporal_budget_outflow')
        ->addTool([UpdateTemporalBudgetOutflowTool::class, 'updateTemporalBudgetOutflow'], 'update_temporal_budget_outflow')
        ->addTool([DisableTemporalBudgetOutflowTool::class, 'disableTemporalBudgetOutflow'], 'disable_temporal_budget_outflow')
        ->addTool([EnableTemporalBudgetOutflowTool::class, 'enableTemporalBudgetOutflow'], 'enable_temporal_budget_outflow')
        ->addTool([ExecuteTemporalBudgetTool::class, 'executeTemporalBudget'], 'execute_temporal_budget')
        ->addTool([ExecuteTemporalBudgetItemTool::class, 'executeTemporalBudgetItem'], 'execute_temporal_budget_item')

        ->addTool([ListNotesTool::class, 'listNotes'], 'list_notes')
        ->addTool([CreateNoteTool::class, 'createNote'], 'create_note')
        ->addTool([UpdateNoteTool::class, 'updateNote'], 'update_note')
        ->addTool([DisableNoteTool::class, 'disableNote'], 'disable_note')

        ->addTool([ListNotificationsTool::class, 'listNotifications'], 'list_notifications')
        ->addTool([MarkNotificationReadTool::class, 'markNotificationRead'], 'mark_notification_read')

        ->addTool([GetNetWorthTool::class, 'getNetWorth'], 'get_net_worth')
        ->addTool([GetNetWorthWithLoansTool::class, 'getNetWorthWithLoans'], 'get_net_worth_with_loans')

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
} catch (Throwable $e) {
    file_put_contents('php://stderr', "ERROR: " . $e->getMessage() . "\n");
    file_put_contents('php://stderr', "FILE: " . $e->getFile() . ":" . $e->getLine() . "\n");
    file_put_contents('php://stderr', "TRACE: " . $e->getTraceAsString() . "\n");
    exit(1);
}