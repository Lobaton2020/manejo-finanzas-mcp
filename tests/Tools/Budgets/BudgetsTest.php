<?php

declare(strict_types=1);

namespace Tests\Tools\Budgets;

use Tests\TestCase;
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

class BudgetsTest extends TestCase
{
    public function test_get_monthly_budget(): void
    {
        $this->seedUser();
        $this->capsule->getConnection()->table('budget')->insert([
            'id_budget' => 1, 'id_user' => 1, 'total' => 1000.0, 'description' => 'test',
            'created_at' => '2026-08-01 00:00:00',
        ]);

        $tool = new GetMonthlyBudgetTool();
        $this->assertTrue($this->decode($tool->getMonthlyBudget(1))['data']['has_budget']);
        $this->assertFalse($this->decode($tool->getMonthlyBudget(999))['success']);

        $this->capsule->getConnection()->table('budget')->truncate();
        $this->assertFalse($this->decode($tool->getMonthlyBudget(1))['data']['has_budget']);
    }

    public function test_set_monthly_budget(): void
    {
        $this->seedUser();
        $tool = new SetMonthlyBudgetTool();

        $r1 = $tool->setMonthlyBudget(total: 1000.0, idUser: 1, description: 'A');
        $this->assertSame('created', $this->decode($r1)['data']['action']);

        $r2 = $tool->setMonthlyBudget(total: 2000.0, idUser: 1);
        $this->assertSame('updated', $this->decode($r2)['data']['action']);

        $this->assertFalse($this->decode($tool->setMonthlyBudget(total: 0, idUser: 1))['success']);
        $this->assertFalse($this->decode($tool->setMonthlyBudget(total: 100, idUser: 999))['success']);
    }

    public function test_list_temporal_budgets(): void
    {
        $this->seedUser();
        $this->seedTemporalBudget(1, 'B1');

        $tool = new ListTemporalBudgetsTool();
        $this->assertSame(1, $this->decode($tool->listTemporalBudgets(1))['count']);
        $this->assertFalse($this->decode($tool->listTemporalBudgets(999))['success']);
    }

    public function test_create_temporal_budget(): void
    {
        $this->seedUser();
        $tool = new CreateTemporalBudgetTool();
        $this->assertTrue($this->decode($tool->createTemporalBudget('B1', 1))['success']);
        $this->assertFalse($this->decode($tool->createTemporalBudget('   ', 1))['success']);
    }

    public function test_update_temporal_budget(): void
    {
        $this->seedUser();
        $id = $this->seedTemporalBudget(1, 'B1');
        $tool = new UpdateTemporalBudgetTool();

        $this->assertTrue($this->decode($tool->updateTemporalBudget($id, 1, 'New', 'D'))['success']);
        $this->assertFalse($this->decode($tool->updateTemporalBudget(999, 1, 'X'))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->updateTemporalBudget($id, 2, 'X'))['success']);
        $this->assertFalse($this->decode($tool->updateTemporalBudget($id, 1))['success']);
    }

    public function test_add_temporal_budget_outflow(): void
    {
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'Cat');
        $pId = $this->seedDeposit(1, 'D');
        $tool = new AddTemporalBudgetOutflowTool();

        $r = $tool->addTemporalBudgetOutflow(idTemporalBudget: $tbId, idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 100.0, isInBudget: true, idUser: 1);
        $this->assertTrue($this->decode($r)['success']);

        $this->assertFalse($this->decode($tool->addTemporalBudgetOutflow(idTemporalBudget: 999, idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 100.0, isInBudget: true))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->addTemporalBudgetOutflow(idTemporalBudget: $tbId, idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 100.0, isInBudget: true, idUser: 2))['success']);

        $this->assertFalse($this->decode($tool->addTemporalBudgetOutflow(idTemporalBudget: $tbId, idOutflowType: 999, idCategory: $cId, idPorcent: $pId, amount: 100.0, isInBudget: true))['success']);
        $this->assertFalse($this->decode($tool->addTemporalBudgetOutflow(idTemporalBudget: $tbId, idOutflowType: $otId, idCategory: 999, idPorcent: $pId, amount: 100.0, isInBudget: true))['success']);
        $this->assertFalse($this->decode($tool->addTemporalBudgetOutflow(idTemporalBudget: $tbId, idOutflowType: $otId, idCategory: $cId, idPorcent: 999, amount: 100.0, isInBudget: true))['success']);
        $this->assertFalse($this->decode($tool->addTemporalBudgetOutflow(idTemporalBudget: $tbId, idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 0, isInBudget: true))['success']);
    }

    public function test_update_temporal_budget_outflow(): void
    {
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $id = $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 100.0);

        $tool = new UpdateTemporalBudgetOutflowTool();
        $this->assertTrue($this->decode($tool->updateTemporalBudgetOutflow($id, 1, 200.0, 'D'))['success']);
        $this->assertFalse($this->decode($tool->updateTemporalBudgetOutflow(999, 1, 200.0))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->updateTemporalBudgetOutflow($id, 2, 200.0))['success']);
        $this->assertFalse($this->decode($tool->updateTemporalBudgetOutflow($id, 1, 0))['success']);
        $this->assertFalse($this->decode($tool->updateTemporalBudgetOutflow($id, 1))['success']);
    }

    public function test_disable_enable_temporal_budget_outflow(): void
    {
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $id = $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 100.0, ['status' => 1]);

        $this->assertTrue($this->decode((new DisableTemporalBudgetOutflowTool())->disableTemporalBudgetOutflow($id, 1))['success']);
        $this->assertFalse($this->decode((new DisableTemporalBudgetOutflowTool())->disableTemporalBudgetOutflow(999, 1))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode((new DisableTemporalBudgetOutflowTool())->disableTemporalBudgetOutflow($id, 2))['success']);
        $this->assertTrue($this->decode((new EnableTemporalBudgetOutflowTool())->enableTemporalBudgetOutflow($id, 1))['success']);
        $this->assertFalse($this->decode((new EnableTemporalBudgetOutflowTool())->enableTemporalBudgetOutflow(999, 1))['success']);
        $this->assertFalse($this->decode((new EnableTemporalBudgetOutflowTool())->enableTemporalBudgetOutflow($id, 2))['success']);
    }

    public function test_execute_temporal_budget(): void
    {
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $cId = $this->seedCategory(1, $otId, 'Cat');
        $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 100.0, ['id_category' => $cId]);

        $this->seedInflow(1, $this->seedInflowType(1, 'Sal'), 1000000.0);
        $this->capsule->getConnection()->table('inflow_porcent')->insert([
            'id_inflow' => 1, 'id_porcent' => $pId, 'porcent' => 100, 'status' => 1,
            'create_at' => date('Y-m-d H:i:s'),
        ]);

        $tool = new ExecuteTemporalBudgetTool();
        $this->assertTrue($this->decode($tool->executeTemporalBudget($tbId, 1))['success']);

        $tbId2 = $this->seedTemporalBudget(1, 'Empty');
        $res = $tool->executeTemporalBudget($tbId2, 1);
        $this->assertSame(0, $this->decode($res)['data']['executed_count']);

        $this->assertFalse($this->decode($tool->executeTemporalBudget(999, 1))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->executeTemporalBudget($tbId, 2))['success']);

        $tbId3 = $this->seedTemporalBudget(1, 'Broken');
        $this->seedTemporalBudgetOutflow(1, $tbId3, $otId, 999, 1.0);
        $this->assertFalse($this->decode($tool->executeTemporalBudget($tbId3, 1))['success']);
    }

    public function test_execute_temporal_budget_insufficient(): void
    {
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $cId = $this->seedCategory(1, $otId, 'Cat');
        $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 9999999.0, ['id_category' => $cId]);

        $tool = new ExecuteTemporalBudgetTool();
        $res = $tool->executeTemporalBudget($tbId, 1);
        $this->assertFalse($this->decode($res)['success']);
    }

    public function test_execute_temporal_budget_item(): void
    {
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $cId = $this->seedCategory(1, $otId, 'Cat');
        $id = $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 100.0, ['id_category' => $cId, 'status' => 1]);

        $this->seedInflow(1, $this->seedInflowType(1, 'Sal'), 1000000.0);
        $this->capsule->getConnection()->table('inflow_porcent')->insert([
            'id_inflow' => 1, 'id_porcent' => $pId, 'porcent' => 100, 'status' => 1,
            'create_at' => date('Y-m-d H:i:s'),
        ]);

        $tool = new ExecuteTemporalBudgetItemTool();
        $this->assertTrue($this->decode($tool->executeTemporalBudgetItem($id, 1))['success']);

        $this->assertFalse($this->decode($tool->executeTemporalBudgetItem(999, 1))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->executeTemporalBudgetItem($id, 2))['success']);

        $idInactive = $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 50.0, ['id_category' => $cId, 'status' => 0]);
        $this->assertFalse($this->decode($tool->executeTemporalBudgetItem($idInactive, 1))['success']);

        $idInsufficient = $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 9999999.0, ['id_category' => $cId, 'status' => 1]);
        $this->assertFalse($this->decode($tool->executeTemporalBudgetItem($idInsufficient, 1))['success']);

        $idBroken = $this->seedTemporalBudgetOutflow(1, $tbId, $otId, 999, 1.0, ['id_category' => $cId, 'status' => 1]);
        $this->assertFalse($this->decode($tool->executeTemporalBudgetItem($idBroken, 1))['success']);
    }
}