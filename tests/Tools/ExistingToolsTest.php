<?php

declare(strict_types=1);

namespace Tests\Tools;

use Tests\TestCase;
use Tools\EgressMoney\OutflowMoneyTool;
use Tools\InflowMoney\InflowMoneyTool;
use Tools\EgressMoney\GetOutflowTypesTool;
use Tools\InflowMoney\GetInflowTypesTool;
use Tools\EgressMoney\GetCategoriesTool;
use Tools\EgressMoney\GetAvailableByDepositsTool;
use Tools\EgressMoney\GetDepositsHistoryTool;
use Tools\EgressMoney\GetOutflowsByMonthTool;
use Tools\EgressMoney\GetInvestmentGroupsTool;
use Tools\EgressMoney\GetExpenseForecastTool;

class ExistingToolsTest extends TestCase
{
    public function test_outflow_money_full_flow(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'Inversion Crypto');
        $cId = $this->seedCategory(1, $otId, 'BTC');
        $pId = $this->seedDeposit(1, 'Efectivo');
        $gId = $this->seedGroup(1, 'Cripto');
        $this->seedInflow(1, $this->seedInflowType(1, 'Sal'), 1000000.0);
        $this->capsule->getConnection()->table('inflow_porcent')->insert([
            'id_inflow' => 1, 'id_porcent' => $pId, 'porcent' => 100, 'status' => 1,
            'create_at' => date('Y-m-d H:i:s'),
        ]);

        $tool = new OutflowMoneyTool();

        $r = $tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 500.0,
            isInBudget: false, description: 'compra btc', setDate: '2026-08-15',
            idUser: 1, idGroupInvestment: $gId, dryRun: false,
        );
        $this->assertTrue($this->decode($r)['success']);

        $row = $this->capsule->getConnection()->table('investments')->first();
        $this->assertSame($gId, (int) $row->id_group_investment);
        $this->assertSame('Creado', $row->state);
        $this->assertSame('Conservador', $row->risk_level);

        $r2 = $tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 500.0,
            isInBudget: false, description: 'compra btc2', setDate: '2026-08-15',
            idUser: 1, idGroupInvestment: null, dryRun: false,
        );
        $this->assertTrue($this->decode($r2)['success']);
        $this->assertNull($this->capsule->getConnection()->table('investments')->orderBy('id_investment', 'DESC')->first()->id_group_investment);

        $nonInv = $this->seedOutflowType(1, 'Comida');
        $cCat = $this->seedCategory(1, $nonInv, 'Resto');
        $this->assertFalse($this->decode($tool->outflowMoney(
            idOutflowType: $nonInv, idCategory: $cCat, idPorcent: $pId, amount: 10.0,
            isInBudget: false, description: 'x', idUser: 1, idGroupInvestment: $gId,
        ))['success']);

        $this->assertFalse($this->decode($tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 100.0,
            isInBudget: false, description: 'x', idUser: 1, idGroupInvestment: 999,
        ))['success']);

        $this->assertFalse($this->decode($tool->outflowMoney(
            idOutflowType: 999, idCategory: $cId, idPorcent: $pId, amount: 100.0,
            isInBudget: false, description: 'x', idUser: 1,
        ))['success']);

        $this->assertFalse($this->decode($tool->outflowMoney(
            idOutflowType: $otId, idCategory: 999, idPorcent: $pId, amount: 100.0,
            isInBudget: false, description: 'x', idUser: 1,
        ))['success']);

        $this->assertFalse($this->decode($tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: 999, amount: 100.0,
            isInBudget: false, description: 'x', idUser: 1,
        ))['success']);

        $this->assertFalse($this->decode($tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 9999999999.0,
            isInBudget: false, description: 'x', idUser: 1,
        ))['success']);

        $this->assertFalse($this->decode($tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 0,
            isInBudget: false, description: 'x', idUser: 1,
        ))['success']);

        $this->assertFalse($this->decode($tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 100.0,
            isInBudget: false, description: 'x', idUser: 999,
        ))['success']);

        $dry = $tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 100.0,
            isInBudget: false, description: 'x', idUser: 1, idGroupInvestment: $gId, dryRun: true,
        );
        $this->assertStringContainsString('"valid": true', $dry['content']['text']);
    }

    public function test_outflow_money_transaction_rollback_on_insufficient_balance(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'Inversion');
        $cId = $this->seedCategory(1, $otId, 'Cat');
        $pId = $this->seedDeposit(1, 'D');

        $tool = new OutflowMoneyTool();
        $res = $tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 9999999999.0,
            isInBudget: false, description: 'x', idUser: 1,
        );
        $this->assertFalse($this->decode($res)['success']);
        $this->assertSame(0, $this->capsule->getConnection()->table('outflows')->count());
        $this->assertSame(0, $this->capsule->getConnection()->table('investments')->count());
    }

    public function test_inflow_money(): void
    {
        $this->seedUser();
        $itId = $this->seedInflowType(1, 'Sal');
        $pId = $this->seedDeposit(1, 'D');

        $tool = new InflowMoneyTool();
        $r = $tool->inflowMoney(idInflowType: $itId, total: 1000.0, porcents: [['idPorcent' => $pId, 'porcent' => 100]], description: 'X', setDate: '2026-08-01', idUser: 1);
        $this->assertTrue($this->decode($r)['success']);

        $bad1 = $tool->inflowMoney(idInflowType: $itId, total: 0, porcents: [['idPorcent' => $pId, 'porcent' => 100]], description: 'x', idUser: 1);
        $this->assertFalse($this->decode($bad1)['success']);

        $bad2 = $tool->inflowMoney(idInflowType: 999, total: 100, porcents: [['idPorcent' => $pId, 'porcent' => 100]], description: 'x', idUser: 1);
        $this->assertFalse($this->decode($bad2)['success']);

        $bad3 = $tool->inflowMoney(idInflowType: $itId, total: 100, porcents: [['idPorcent' => 999, 'porcent' => 100]], description: 'x', idUser: 1);
        $this->assertFalse($this->decode($bad3)['success']);

        $bad4 = $tool->inflowMoney(idInflowType: $itId, total: 100, porcents: [['idPorcent' => $pId, 'porcent' => 100]], description: 'x', idUser: 999);
        $this->assertFalse($this->decode($bad4)['success']);

        $bad5 = $tool->inflowMoney(idInflowType: $itId, total: 100, porcents: [['idPorcent' => $pId, 'porcent' => 50]], description: 'x', idUser: 1);
        $this->assertFalse($this->decode($bad5)['success']);

        $dry = $tool->inflowMoney(idInflowType: $itId, total: 100, porcents: [['idPorcent' => $pId, 'porcent' => 100]], description: 'x', idUser: 1, dryRun: true);
        $this->assertStringContainsString('"valid": true', $dry['content']['text']);

        $bad6 = $tool->inflowMoney(idInflowType: $itId, total: 100, porcents: [], description: 'x', idUser: 1);
        $this->assertFalse($this->decode($bad6)['success']);

        $bad7 = $tool->inflowMoney(idInflowType: $itId, total: 100, porcents: [['foo' => 'bar']], description: 'x', idUser: 1);
        $this->assertFalse($this->decode($bad7)['success']);
    }

    public function test_get_outflow_types(): void
    {
        $this->seedUser();
        $this->seedOutflowType(1, 'A');
        $this->seedOutflowType(1, 'B', 0);
        $tool = new GetOutflowTypesTool();
        $arr = json_decode($tool->getOutflowTypes(1)['content']['text'], true);
        $this->assertCount(1, $arr);

        $this->assertStringContainsString('No hay', $tool->getOutflowTypes(999)['content']['text']);
    }

    public function test_get_inflow_types(): void
    {
        $this->seedUser();
        $this->seedInflowType(1, 'A');
        $tool = new GetInflowTypesTool();
        $arr = json_decode($tool->getInflowTypes(1)['content']['text'], true);
        $this->assertCount(1, $arr);

        $this->assertStringContainsString('No hay', $tool->getInflowTypes(999)['content']['text']);
    }

    public function test_get_categories(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $this->seedCategory(1, $otId, 'Cat1');
        $this->seedCategory(1, $otId, 'Cat2', 0);
        $tool = new GetCategoriesTool();
        $arr = json_decode($tool->getCategories($otId)['content']['text'], true);
        $this->assertCount(1, $arr);

        $this->assertStringContainsString('No hay', $tool->getCategories(999)['content']['text']);
    }

    public function test_get_available_by_deposits(): void
    {
        $this->seedUser();
        $this->seedDeposit(1, 'D1', 1);
        $this->seedDeposit(1, 'D2', 0);
        $this->seedInflow(1, $this->seedInflowType(1, 'S'), 1000.0);
        $this->capsule->getConnection()->table('inflow_porcent')->insert([
            'id_inflow' => 1, 'id_porcent' => 1, 'porcent' => 100, 'status' => 1,
            'create_at' => date('Y-m-d H:i:s'),
        ]);
        $tool = new GetAvailableByDepositsTool();
        $arr = json_decode($tool->getAvailableByDeposits(1)['content']['text'], true);
        $this->assertNotEmpty($arr);
        $this->assertStringContainsString('No hay', $tool->getAvailableByDeposits(999)['content']['text']);
    }

    public function test_get_deposits_history(): void
    {
        $this->seedUser();
        $pId = $this->seedDeposit(1, 'D');
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'C');
        $itId = $this->seedInflowType(1, 'S');
        $this->seedInflow(1, $itId, 1000.0, '2026-01-15');
        $this->seedOutflow(1, $otId, $pId, 200.0, ['id_category' => $cId, 'set_date' => '2026-01-20', 'is_in_budget' => 1]);
        $this->seedOutflow(1, $otId, $pId, 100.0, ['id_category' => $cId, 'set_date' => '2026-02-10', 'is_in_budget' => 1]);

        $tool = new GetDepositsHistoryTool();
        $history = json_decode($tool->getDepositsHistory(1)['content']['text'], true);
        $this->assertCount(2, $history);
        $this->assertEquals(1000.0, $history[0]['income']);
        $this->assertEquals(200.0, $history[0]['expense']);
        $this->assertEquals(800.0, $history[0]['balance']);
        $this->assertEquals(0.0, $history[1]['income']);
        $this->assertEquals(100.0, $history[1]['expense']);
        $this->assertEquals(700.0, $history[1]['balance']);

        $this->assertSame([], json_decode($tool->getDepositsHistory(999)['content']['text'], true));
    }

    public function test_get_outflows_by_month(): void
    {
        $this->seedUser();
        $pId = $this->seedDeposit(1, 'D');
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'C');
        $this->seedOutflow(1, $otId, $pId, 200.0, ['id_category' => $cId, 'set_date' => '2026-08-15']);

        $tool = new GetOutflowsByMonthTool();
        $this->assertStringContainsString('formato', $tool->getOutflowsByMonth(yearMonth: 'bad', idUser: 1)['content']['text']);

        $arr = json_decode($tool->getOutflowsByMonth(yearMonth: '2026-08', idUser: 1)['content']['text'], true);
        $this->assertSame(1, $arr['count']);
        $this->assertEquals(200.0, $arr['total_outflows']);

        $empty = json_decode($tool->getOutflowsByMonth(yearMonth: '2026-01', idUser: 1)['content']['text'], true);
        $this->assertStringContainsString('No se encontraron', $empty['message']);
    }

    public function test_get_expense_forecast(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $cId = $this->seedCategory(1, $otId, 'C');

        $this->seedOutflow(1, $otId, $pId, 100.0, ['id_category' => $cId, 'set_date' => '2025-08-15', 'is_in_budget' => 1]);
        $this->seedOutflow(1, $otId, $pId, 200.0, ['id_category' => $cId, 'set_date' => '2026-08-15', 'is_in_budget' => 1]);

        $tool = new GetExpenseForecastTool();
        $arr = json_decode($tool->getExpenseForecast(1)['content']['text'], true);
        $this->assertCount(6, $arr['forecast']);
        $this->assertSame('seasonal_avg', $arr['method']);

        $empty = $tool->getExpenseForecast(999);
        $this->assertSame('No hay datos', $empty['content']['text']);
    }

    public function test_get_investment_groups(): void
    {
        $this->seedUser();
        $this->seedGroup(1, 'G1');
        $tool = new GetInvestmentGroupsTool();
        $this->assertEquals(1, $this->decode($tool->getInvestmentGroups(1))['count']);
        $this->assertEquals(1, $this->decode($tool->getInvestmentGroups(1, includeInvestmentCount: true))['count']);
        $this->assertFalse($this->decode($tool->getInvestmentGroups(999))['success']);
    }
}