<?php

declare(strict_types=1);

namespace Tests\Tools\Mutation;

use Tests\TestCase;
use Tools\EgressMoney\OutflowMoneyTool;
use Tools\InflowMoney\InflowMoneyTool;
use Tools\EgressMoney\UpdateOutflowTool;
use Tools\EgressMoney\GetOutflowTool;
use Tools\InflowMoney\UpdateInflowTool;
use Tools\InflowMoney\GetInflowTool;
use Tools\Lookups\UpdateOutflowTypeTool;
use Tools\Lookups\UpdateCategoryTool;
use Tools\Lookups\DisableOutflowTypeTool;
use Tools\Lookups\EnableOutflowTypeTool;
use Tools\Lookups\DisableCategoryTool;
use Tools\Lookups\EnableCategoryTool;
use Tools\Lookups\UpdateDepositTool;
use Tools\Lookups\DisableDepositTool;
use Tools\Lookups\EnableDepositTool;
use Tools\EgressMoney\ListOutflowsTool;
use Tools\InflowMoney\ListInflowsTool;
use Tools\EgressMoney\CreateInvestmentGroupTool;
use Tools\EgressMoney\UpdateInvestmentGroupTool;
use Tools\Investments\UpdateInvestmentTool;
use Tools\Investments\HideInvestmentTool;
use Tools\Investments\CreateInvestmentRetirementTool;
use Tools\Investments\ListInvestmentRetirementsTool;
use Tools\Budgets\ExecuteTemporalBudgetTool;
use Tools\Budgets\ExecuteTemporalBudgetItemTool;
use Tools\Budgets\AddTemporalBudgetOutflowTool;
use Tools\Budgets\UpdateTemporalBudgetOutflowTool;
use Tools\Budgets\DisableTemporalBudgetOutflowTool;
use Tools\Budgets\EnableTemporalBudgetOutflowTool;
use Tools\Budgets\SetMonthlyBudgetTool;
use Tools\Budgets\GetMonthlyBudgetTool;
use Tools\Budgets\CreateTemporalBudgetTool;
use Tools\Budgets\UpdateTemporalBudgetTool;
use Tools\Budgets\ListTemporalBudgetsTool;
use Tools\Notes\CreateNoteTool;
use Tools\Notes\DisableNoteTool;
use Tools\Notes\UpdateNoteTool;
use Tools\Notes\ListNotesTool;
use Tools\Notifications\ListNotificationsTool;
use Tools\Notifications\MarkNotificationReadTool;
use Tools\Reports\GetNetWorthTool;
use Tools\Reports\GetNetWorthWithLoansTool;

/**
 * Tests specifically designed to "kill" common mutations:
 *  - Off-by-one: amount=0 vs negative
 *  - Negation: ! condition
 *  - Wrong operator: > vs >=, == vs ===
 *  - Wrong constant: 1 vs 0 (active status flag)
 *  - Empty check removal: trim() === '' vs just empty()
 *  - Missing ownership check
 *  - Wrong branch in transaction (no rollback)
 *  - Wrong column in INSERT
 *
 * Each test verifies DB STATE (not just response.success) and
 * uses exact equality + boundary values.
 */
class MutationTest extends TestCase
{
    // ============================================================
    // OUTFLOW_MONEY — mutaciones típicas
    // ============================================================

    public function test_outflow_money_amount_zero_kills_off_by_one(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'C');
        $pId = $this->seedDeposit(1, 'D');

        $tool = new OutflowMoneyTool();
        $r = $tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 0.0,
            isInBudget: false, description: 'x', idUser: 1,
        );

        // < vs <= mutation: if amount <= 0 → if amount < 0, amount=0 would pass
        // Verifying exact error message kills the mutation
        $this->assertFalse($this->decode($r)['success']);
        $this->assertSame('El monto debe ser mayor a 0.', $this->decode($r)['error']);
        $this->assertSame(0, (int) $this->capsule->getConnection()->table('outflows')->count());
    }

    public function test_outflow_money_amount_negative_kills_off_by_one(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'C');
        $pId = $this->seedDeposit(1, 'D');

        $tool = new OutflowMoneyTool();
        $r = $tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: -100.0,
            isInBudget: false, description: 'x', idUser: 1,
        );

        $this->assertFalse($this->decode($r)['success']);
        $this->assertSame('El monto debe ser mayor a 0.', $this->decode($r)['error']);
        $this->assertSame(0, (int) $this->capsule->getConnection()->table('outflows')->count());
    }

    public function test_outflow_money_transaction_rollback_when_investment_invalid(): void
    {
        // Mutante: si se quita el throw dentro del transaction, el outflow queda persistido
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'Inversion');
        $cId = $this->seedCategory(1, $otId, 'BTC');
        $pId = $this->seedDeposit(1, 'D');
        $this->seedInflow(1, $this->seedInflowType(1, 'Sal'), 1000.0);

        $tool = new OutflowMoneyTool();
        // Forzar idGroupInvestment = 999 (no existe) — el transaction debería rollback
        $r = $tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 100.0,
            isInBudget: false, description: 'x', idUser: 1, idGroupInvestment: 999,
        );

        $this->assertFalse($this->decode($r)['success']);
        $this->assertSame(0, (int) $this->capsule->getConnection()->table('outflows')->count());
        $this->assertSame(0, (int) $this->capsule->getConnection()->table('investments')->count());
    }

    public function test_outflow_money_state_value_kills_string_mutation(): void
    {
        // Mutante: state='Creado' → state='Creado ' (con espacio) o 'creado' o ''
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'Inversion');
        $cId = $this->seedCategory(1, $otId, 'BTC');
        $pId = $this->seedDeposit(1, 'D');
        $inId = $this->seedInflow(1, $this->seedInflowType(1, 'Sal'), 1000.0);
        $this->capsule->getConnection()->table('inflow_porcent')->insert([
            'id_inflow' => $inId, 'id_porcent' => $pId, 'porcent' => 100, 'status' => 1, 'create_at' => date('Y-m-d H:i:s'),
        ]);

        $tool = new OutflowMoneyTool();
        $r = $tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 100.0,
            isInBudget: false, description: 'x', idUser: 1,
        );
        $this->assertTrue($this->decode($r)['success']);

        $inv = $this->capsule->getConnection()->table('investments')->first();
        $this->assertSame('Creado', $inv->state, 'state exact value');
        $this->assertSame('Conservador', $inv->risk_level, 'risk_level exact value');
    }

    public function test_outflow_money_inversion_creates_investment_kills_branch_mutation(): void
    {
        // Mutante: if stripos !== false → if stripos === false (invierte creación de investment)
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'Inversion');  // contains "inversion"
        $cId = $this->seedCategory(1, $otId, 'BTC');
        $pId = $this->seedDeposit(1, 'D');
        $inId = $this->seedInflow(1, $this->seedInflowType(1, 'Sal'), 1000.0);
        $this->capsule->getConnection()->table('inflow_porcent')->insert([
            'id_inflow' => $inId, 'id_porcent' => $pId, 'porcent' => 100, 'status' => 1, 'create_at' => date('Y-m-d H:i:s'),
        ]);

        $tool = new OutflowMoneyTool();
        $r = $tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 100.0,
            isInBudget: false, description: 'x', idUser: 1,
        );
        $this->assertTrue($this->decode($r)['success']);
        // El tool usa formato custom (no successResponse), leer directo
        $raw = json_decode($r['content']['text'], true);
        $this->assertTrue($raw['investment_created']);
        $this->assertSame(1, (int) $this->capsule->getConnection()->table('investments')->count());
    }

    public function test_outflow_money_non_inversion_no_creates_investment(): void
    {
        // Mutante: si se cambia el contains-check, también crearía investment para outflows normales
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'Comida');  // NO contains "inversion"
        $cId = $this->seedCategory(1, $otId, 'Resto');
        $pId = $this->seedDeposit(1, 'D');
        $inId = $this->seedInflow(1, $this->seedInflowType(1, 'Sal'), 1000.0);
        $this->capsule->getConnection()->table('inflow_porcent')->insert([
            'id_inflow' => $inId, 'id_porcent' => $pId, 'porcent' => 100, 'status' => 1, 'create_at' => date('Y-m-d H:i:s'),
        ]);

        $tool = new OutflowMoneyTool();
        $r = $tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 100.0,
            isInBudget: false, description: 'x', idUser: 1,
        );
        $this->assertTrue($this->decode($r)['success']);
        $raw = json_decode($r['content']['text'], true);
        $this->assertFalse($raw['investment_created']);
        $this->assertSame(0, (int) $this->capsule->getConnection()->table('investments')->count());
    }

    public function test_outflow_money_inversion_creates_init_date_and_end_date(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'Inversion');
        $cId = $this->seedCategory(1, $otId, 'BTC');
        $pId = $this->seedDeposit(1, 'D');
        $inId = $this->seedInflow(1, $this->seedInflowType(1, 'Sal'), 1000.0);
        $this->capsule->getConnection()->table('inflow_porcent')->insert([
            'id_inflow' => $inId, 'id_porcent' => $pId, 'porcent' => 100, 'status' => 1, 'create_at' => date('Y-m-d H:i:s'),
        ]);

        $tool = new OutflowMoneyTool();
        $tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 100.0,
            setDate: '2026-08-15',
            isInBudget: false, description: 'x', idUser: 1,
        );

        $inv = $this->capsule->getConnection()->table('investments')->first();
        $this->assertSame('2026-08-15', $inv->init_date);
        $this->assertSame(date('Y-m-d', strtotime('2026-08-15 +1 month')), $inv->end_date);
    }

    public function test_outflow_money_inversion_with_group_saves_group_id(): void
    {
        // Caso del bug original
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'Inversion');
        $cId = $this->seedCategory(1, $otId, 'BTC');
        $pId = $this->seedDeposit(1, 'D');
        $gId = $this->seedGroup(1, 'Crypto');
        $inId = $this->seedInflow(1, $this->seedInflowType(1, 'Sal'), 1000.0);
        $this->capsule->getConnection()->table('inflow_porcent')->insert([
            'id_inflow' => $inId, 'id_porcent' => $pId, 'porcent' => 100, 'status' => 1, 'create_at' => date('Y-m-d H:i:s'),
        ]);

        $tool = new OutflowMoneyTool();
        $tool->outflowMoney(
            idOutflowType: $otId, idCategory: $cId, idPorcent: $pId, amount: 100.0,
            isInBudget: false, description: 'x', idUser: 1, idGroupInvestment: $gId,
        );

        $inv = $this->capsule->getConnection()->table('investments')->first();
        $this->assertSame($gId, (int) $inv->id_group_investment);
    }

    // ============================================================
    // INFLOW_MONEY — mutaciones típicas
    // ============================================================

    public function test_inflow_money_porcents_sum_must_be_exactly_100(): void
    {
        // Mutante: $sumPorcent !== 100 → !== 99 o > 100
        $this->seedUser();
        $itId = $this->seedInflowType(1, 'Sal');
        $pId = $this->seedDeposit(1, 'D');

        $tool = new InflowMoneyTool();

        // < 100
        $r1 = $tool->inflowMoney(idInflowType: $itId, total: 100.0,
            porcents: [['idPorcent' => $pId, 'porcent' => 99]], description: 'x', idUser: 1);
        $this->assertFalse($this->decode($r1)['success']);
        $this->assertStringContainsString('igual a 100', $this->decode($r1)['error']);

        // > 100
        $r2 = $tool->inflowMoney(idInflowType: $itId, total: 100.0,
            porcents: [['idPorcent' => $pId, 'porcent' => 101]], description: 'x', idUser: 1);
        $this->assertFalse($this->decode($r2)['success']);

        // = 100 OK
        $r3 = $tool->inflowMoney(idInflowType: $itId, total: 100.0,
            porcents: [['idPorcent' => $pId, 'porcent' => 100]], description: 'x', idUser: 1);
        $this->assertTrue($this->decode($r3)['success']);
    }

    public function test_inflow_money_total_zero_kills_off_by_one(): void
    {
        $this->seedUser();
        $itId = $this->seedInflowType(1, 'Sal');
        $pId = $this->seedDeposit(1, 'D');

        $tool = new InflowMoneyTool();
        $r = $tool->inflowMoney(idInflowType: $itId, total: 0.0,
            porcents: [['idPorcent' => $pId, 'porcent' => 100]], description: 'x', idUser: 1);
        $this->assertFalse($this->decode($r)['success']);
        $this->assertStringContainsString('mayor a 0', $this->decode($r)['error']);
    }

    public function test_inflow_money_creates_inflow_porcent_with_exact_percentage(): void
    {
        // Mutante: el insert podría usar un valor distinto
        $this->seedUser();
        $itId = $this->seedInflowType(1, 'Sal');
        $pId1 = $this->seedDeposit(1, 'D1');
        $pId2 = $this->seedDeposit(1, 'D2');

        $tool = new InflowMoneyTool();
        $r = $tool->inflowMoney(idInflowType: $itId, total: 1000.0,
            porcents: [['idPorcent' => $pId1, 'porcent' => 70], ['idPorcent' => $pId2, 'porcent' => 30]],
            description: 'x', idUser: 1);

        $this->assertTrue($this->decode($r)['success']);
        $rows = $this->capsule->getConnection()->table('inflow_porcent')->orderBy('id_porcent')->get();
        $this->assertSame(70, (int) $rows[0]->porcent);
        $this->assertSame(30, (int) $rows[1]->porcent);
    }

    // ============================================================
    // LOOKUPS — mutaciones en update/disable/enable
    // ============================================================

    public function test_update_outflow_type_persists_status_field(): void
    {
        // Mutante: si el if ($status !== null) se quita, no actualizaría status
        $this->seedUser();
        $id = $this->seedOutflowType(1, 'Old', 1);

        $tool = new UpdateOutflowTypeTool();
        $r = $tool->updateOutflowType(idOutflowType: $id, idUser: 1, status: 0);

        $this->assertTrue($this->decode($r)['success']);
        $row = $this->capsule->getConnection()->table('outflowtypes')->where('id_outflow_type', $id)->first();
        $this->assertSame(0, (int) $row->status);
        $this->assertSame('Old', $row->name, 'name should not change when only status is updated');
    }

    public function test_disable_outflow_type_persists_zero_status(): void
    {
        // Mutante: status=0 → status=1 (invierte)
        $this->seedUser();
        $id = $this->seedOutflowType(1, 'X', 1);

        $tool = new DisableOutflowTypeTool();
        $tool->disableOutflowType($id, 1);

        $row = $this->capsule->getConnection()->table('outflowtypes')->where('id_outflow_type', $id)->first();
        $this->assertSame(0, (int) $row->status);
    }

    public function test_enable_outflow_type_persists_one_status(): void
    {
        // Mutante: status=1 → status=0
        $this->seedUser();
        $id = $this->seedOutflowType(1, 'X', 0);

        $tool = new EnableOutflowTypeTool();
        $tool->enableOutflowType($id, 1);

        $row = $this->capsule->getConnection()->table('outflowtypes')->where('id_outflow_type', $id)->first();
        $this->assertSame(1, (int) $row->status);
    }

    public function test_update_outflow_type_ownership_check_kills_removal(): void
    {
        // Mutante: si se quita where('id_user', $idUser), un user podría editar de otro
        $this->seedUser(1);
        $this->seedUser(2, 'Other');
        $id = $this->seedOutflowType(1, 'X');

        $tool = new UpdateOutflowTypeTool();
        $r = $tool->updateOutflowType(idOutflowType: $id, idUser: 2, name: 'Hijacked');
        $this->assertFalse($this->decode($r)['success']);

        $row = $this->capsule->getConnection()->table('outflowtypes')->where('id_outflow_type', $id)->first();
        $this->assertSame('X', $row->name, 'name should NOT have been changed by other user');
    }

    public function test_update_category_persists_id_outflow_type_when_changed(): void
    {
        // Mutante: si el if ($idOutflowType !== null) se quita o no asigna, no se reasigna
        $this->seedUser();
        $otId1 = $this->seedOutflowType(1, 'Tipo1');
        $otId2 = $this->seedOutflowType(1, 'Tipo2');
        $cId = $this->seedCategory(1, $otId1, 'Cat');

        $tool = new UpdateCategoryTool();
        $r = $tool->updateCategory(idCategory: $cId, idUser: 1, name: null, status: null, idOutflowType: $otId2);
        $this->assertTrue($this->decode($r)['success']);

        $row = $this->capsule->getConnection()->table('categories')->where('id_category', $cId)->first();
        $this->assertSame($otId2, (int) $row->id_outflow_type, 'category should be reassigned to tipo2');
    }

    public function test_disable_category_persists_status_zero(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'Cat', 1);

        $tool = new DisableCategoryTool();
        $tool->disableCategory($cId, 1);

        $row = $this->capsule->getConnection()->table('categories')->where('id_category', $cId)->first();
        $this->assertSame(0, (int) $row->status);
    }

    public function test_update_deposit_persists_only_name(): void
    {
        // Mutante: si no hay where de id_user, otro user puede editar
        $this->seedUser(1);
        $this->seedUser(2, 'O');
        $id = $this->seedDeposit(1, 'Original');

        $tool = new UpdateDepositTool();
        $r = $tool->updateDeposit($id, 2, 'Hijacked');
        $this->assertFalse($this->decode($r)['success']);

        $row = $this->capsule->getConnection()->table('porcents')->where('id_porcent', $id)->first();
        $this->assertSame('Original', $row->name);
    }

    public function test_disable_deposit_persists_status_zero(): void
    {
        $this->seedUser();
        $id = $this->seedDeposit(1, 'D', 1);
        $tool = new DisableDepositTool();
        $tool->disableDeposit($id, 1);

        $row = $this->capsule->getConnection()->table('porcents')->where('id_porcent', $id)->first();
        $this->assertSame(0, (int) $row->status);
    }

    // ============================================================
    // UPDATE OUTFLOW / UPDATE INFLOW — mutations
    // ============================================================

    public function test_update_outflow_persists_amount_exactly(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $id = $this->seedOutflow(1, $otId, $pId, 100.0);

        $tool = new UpdateOutflowTool();
        $r = $tool->updateOutflow(idOutflow: $id, idUser: 1, amount: 250.5);

        $row = $this->capsule->getConnection()->table('outflows')->where('id_outflow', $id)->first();
        $this->assertEquals(250.5, (float) $row->amount);
    }

    public function test_update_outflow_ownership_kills_removal(): void
    {
        $this->seedUser(1);
        $this->seedUser(2, 'O');
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $id = $this->seedOutflow(1, $otId, $pId, 100.0);

        $tool = new UpdateOutflowTool();
        $r = $tool->updateOutflow(idOutflow: $id, idUser: 2, amount: 99999.0);
        $this->assertFalse($this->decode($r)['success']);

        $row = $this->capsule->getConnection()->table('outflows')->where('id_outflow', $id)->first();
        $this->assertEquals(100.0, (float) $row->amount, 'amount should not change');
    }

    public function test_update_outflow_is_in_budget_boolean(): void
    {
        // Mutante: is_in_budget ? 1 : 0 → 0 : 1
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $id = $this->seedOutflow(1, $otId, $pId, 100.0, ['is_in_budget' => 0]);

        $tool = new UpdateOutflowTool();
        $tool->updateOutflow(idOutflow: $id, idUser: 1, isInBudget: true);

        $row = $this->capsule->getConnection()->table('outflows')->where('id_outflow', $id)->first();
        $this->assertSame(1, (int) $row->is_in_budget);
    }

    public function test_update_inflow_persists_total(): void
    {
        $this->seedUser();
        $itId = $this->seedInflowType(1, 'S');
        $id = $this->seedInflow(1, $itId, 1000.0);

        $tool = new UpdateInflowTool();
        $tool->updateInflow(idInflow: $id, idUser: 1, total: 2500.0);

        $row = $this->capsule->getConnection()->table('inflows')->where('id_inflow', $id)->first();
        $this->assertEquals(2500.0, (float) $row->total);
    }

    // ============================================================
    // LIST OUTFLOWS / LIST INFLOWS — pagination/sort
    // ============================================================

    public function test_list_outflows_pagination_total_pages_exact(): void
    {
        // Mutante: ceil → floor, o division incorrecta
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        for ($i = 0; $i < 25; $i++) {
            $this->seedOutflow(1, $otId, $pId, 10.0 + $i);
        }

        $tool = new ListOutflowsTool();
        // length=10 (allowed): 25/10 = ceil = 3 pages
        $r = $tool->listOutflows(1, page: 1, length: 10);
        $d = $this->decode($r)['data'];
        $this->assertSame(25, $d['pagination']['total']);
        $this->assertSame(3, $d['pagination']['totalPages'], 'ceil(25/10)=3');
        $this->assertSame(10, count($d['items']));

        $r2 = $tool->listOutflows(1, page: 3, length: 10);
        $this->assertSame(5, count($this->decode($r2)['data']['items']), 'last page should have 25-20=5 items');
    }

    public function test_list_inflows_pagination_total_pages_exact(): void
    {
        $this->seedUser();
        $itId = $this->seedInflowType(1, 'S');
        for ($i = 0; $i < 25; $i++) {
            $this->seedInflow(1, $itId, 100.0 + $i);
        }

        $tool = new ListInflowsTool();
        $r = $tool->listInflows(1, page: 1, length: 10);
        $d = $this->decode($r)['data'];
        $this->assertSame(25, $d['pagination']['total']);
        $this->assertSame(3, $d['pagination']['totalPages'], 'ceil(25/10)=3');
    }

    public function test_list_outflows_sort_asc_desc_exact(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $this->seedOutflow(1, $otId, $pId, 100.0);
        $this->seedOutflow(1, $otId, $pId, 300.0);
        $this->seedOutflow(1, $otId, $pId, 200.0);

        $tool = new ListOutflowsTool();
        $asc = $this->decode($tool->listOutflows(1, sort: 'amount', order: 'ASC'))['data']['items'];
        $this->assertEquals(100.0, (float) $asc[0]['amount']);
        $this->assertEquals(200.0, (float) $asc[1]['amount']);
        $this->assertEquals(300.0, (float) $asc[2]['amount']);

        $desc = $this->decode($tool->listOutflows(1, sort: 'amount', order: 'DESC'))['data']['items'];
        $this->assertEquals(300.0, (float) $desc[0]['amount']);
        $this->assertEquals(100.0, (float) $desc[2]['amount']);
    }

    // ============================================================
    // INVESTMENTS — mutations
    // ============================================================

    public function test_update_investment_persists_state_exact(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $outId = $this->seedOutflow(1, $otId, $pId, 100.0);
        $invId = $this->seedInvestment($outId, null, ['state' => 'Creado']);

        $tool = new UpdateInvestmentTool();
        $tool->updateInvestment(idInvestment: $invId, idUser: 1, state: 'Activo');

        $row = $this->capsule->getConnection()->table('investments')->where('id_investment', $invId)->first();
        $this->assertSame('Activo', $row->state);
    }

    public function test_update_investment_persists_group_id_when_changed(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $outId = $this->seedOutflow(1, $otId, $pId, 100.0);
        $invId = $this->seedInvestment($outId, null, ['id_group_investment' => null]);
        $gId = $this->seedGroup(1, 'G');

        $tool = new UpdateInvestmentTool();
        $tool->updateInvestment(idInvestment: $invId, idUser: 1, idGroupInvestment: $gId);

        $row = $this->capsule->getConnection()->table('investments')->where('id_investment', $invId)->first();
        $this->assertSame($gId, (int) $row->id_group_investment);
    }

    public function test_update_investment_ownership_via_outflow_kills_removal(): void
    {
        $this->seedUser(1);
        $this->seedUser(2, 'O');
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $outId = $this->seedOutflow(1, $otId, $pId, 100.0);
        $invId = $this->seedInvestment($outId);

        $tool = new UpdateInvestmentTool();
        $r = $tool->updateInvestment(idInvestment: $invId, idUser: 2, state: 'Ocultar');
        $this->assertFalse($this->decode($r)['success']);

        $row = $this->capsule->getConnection()->table('investments')->where('id_investment', $invId)->first();
        $this->assertSame('Creado', $row->state, 'state should NOT have changed by other user');
    }

    public function test_hide_investment_persists_ocultar_state(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $outId = $this->seedOutflow(1, $otId, $pId, 100.0);
        $invId = $this->seedInvestment($outId);

        $tool = new HideInvestmentTool();
        $tool->hideInvestment($invId, 1);

        $row = $this->capsule->getConnection()->table('investments')->where('id_investment', $invId)->first();
        $this->assertSame('Ocultar', $row->state);
    }

    public function test_create_investment_retirement_validates_real_retribution_gt_amount(): void
    {
        // Mutante: realRetribution > retirementAmount → >=
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $outId = $this->seedOutflow(1, $otId, $pId, 1000.0);
        $invId = $this->seedInvestment($outId);

        $tool = new CreateInvestmentRetirementTool();
        // 100.01 > 100.00: debe rechazar (> mutante >= dejaría pasar)
        $r = $tool->createInvestmentRetirement(
            idInvestment: $invId, retirementAmount: 100.0,
            initDate: '2026-08-15', endDate: '2026-08-20',
            idUser: 1, realRetribution: 100.01
        );
        $this->assertFalse($this->decode($r)['success']);
        $this->assertStringContainsString('no puede ser mayor', $this->decode($r)['error']);
    }

    public function test_create_investment_retirement_allows_real_retribution_equal_to_amount(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $outId = $this->seedOutflow(1, $otId, $pId, 1000.0);
        $invId = $this->seedInvestment($outId);

        $tool = new CreateInvestmentRetirementTool();
        // exactamente igual debe pasar
        $r = $tool->createInvestmentRetirement(
            idInvestment: $invId, retirementAmount: 100.0,
            initDate: '2026-08-15', endDate: '2026-08-20',
            idUser: 1, realRetribution: 100.0
        );
        $this->assertTrue($this->decode($r)['success']);
    }

    public function test_create_investment_retirement_validates_cumulative(): void
    {
        // Mutante: validación solo del monto individual sin sumar retiros previos
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $outId = $this->seedOutflow(1, $otId, $pId, 1000.0);
        $invId = $this->seedInvestment($outId);

        $tool = new CreateInvestmentRetirementTool();

        // Retiro 1: 600
        $r1 = $tool->createInvestmentRetirement(
            idInvestment: $invId, retirementAmount: 600.0,
            initDate: '2026-08-15', endDate: '2026-08-20', idUser: 1,
        );
        $this->assertTrue($this->decode($r1)['success']);

        // Retiro 2: 500 (excede 1000-600=400 disponible)
        $r2 = $tool->createInvestmentRetirement(
            idInvestment: $invId, retirementAmount: 500.0,
            initDate: '2026-08-21', endDate: '2026-08-25', idUser: 1,
        );
        $this->assertFalse($this->decode($r2)['success']);
        $this->assertStringContainsString('excede', $this->decode($r2)['error']);
    }

    public function test_create_investment_retirement_amount_zero_rejected(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $outId = $this->seedOutflow(1, $otId, $pId, 1000.0);
        $invId = $this->seedInvestment($outId);

        $tool = new CreateInvestmentRetirementTool();
        $r = $tool->createInvestmentRetirement(
            idInvestment: $invId, retirementAmount: 0.0,
            initDate: '2026-08-15', endDate: '2026-08-20', idUser: 1,
        );
        $this->assertFalse($this->decode($r)['success']);
    }

    public function test_create_investment_retirement_persists_exact_values(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $outId = $this->seedOutflow(1, $otId, $pId, 1000.0);
        $invId = $this->seedInvestment($outId);

        $tool = new CreateInvestmentRetirementTool();
        $r = $tool->createInvestmentRetirement(
            idInvestment: $invId, retirementAmount: 250.75,
            initDate: '2026-08-15', endDate: '2026-08-20',
            idUser: 1, realRetribution: 15.5, descripcion: 'parcial-test',
        );

        $row = $this->capsule->getConnection()->table('retirement_investments')->first();
        $this->assertEquals(250.75, (float) $row->retirement_amount);
        $this->assertEquals(15.5, (float) $row->real_retribution);
        $this->assertSame('parcial-test', $row->descripcion);
    }

    // ============================================================
    // GROUPS
    // ============================================================

    public function test_create_investment_group_persists_all_fields(): void
    {
        $this->seedUser();
        $tool = new CreateInvestmentGroupTool();
        $r = $tool->createInvestmentGroup(name: 'Test Group', idUser: 1, description: 'desc-test');

        $row = $this->capsule->getConnection()->table('group_investments')->first();
        $this->assertSame('Test Group', $row->name);
        $this->assertSame('desc-test', $row->description);
        $this->assertSame(1, (int) $row->id_user);
    }

    public function test_update_investment_group_ownership_kills_removal(): void
    {
        $this->seedUser(1);
        $this->seedUser(2, 'O');
        $id = $this->seedGroup(1, 'Original');

        $tool = new UpdateInvestmentGroupTool();
        $r = $tool->updateInvestmentGroup($id, 2, 'Hijacked');
        $this->assertFalse($this->decode($r)['success']);

        $row = $this->capsule->getConnection()->table('group_investments')->where('id_group_investment', $id)->first();
        $this->assertSame('Original', $row->name);
    }

    // ============================================================
    // BUDGETS — mutations
    // ============================================================

    public function test_set_monthly_budget_creates_when_no_existing(): void
    {
        // Mutante: siempre hace update en vez de insert
        $this->seedUser();
        $tool = new SetMonthlyBudgetTool();
        $r = $tool->setMonthlyBudget(total: 1500.0, idUser: 1);
        $this->assertSame('created', $this->decode($r)['data']['action']);
    }

    public function test_set_monthly_budget_updates_when_existing(): void
    {
        $this->seedUser();
        $this->seedBudget(1, 1000.0);
        $tool = new SetMonthlyBudgetTool();
        $r = $tool->setMonthlyBudget(total: 2000.0, idUser: 1);
        $this->assertSame('updated', $this->decode($r)['data']['action']);
    }

    public function test_set_monthly_budget_zero_total_rejected(): void
    {
        $this->seedUser();
        $tool = new SetMonthlyBudgetTool();
        $r = $tool->setMonthlyBudget(total: 0, idUser: 1);
        $this->assertFalse($this->decode($r)['success']);
    }

    public function test_create_temporal_budget_name_only_spaces_rejected(): void
    {
        // Mutante: empty(trim($name)) → empty($name) (sin trim)
        $this->seedUser();
        $tool = new CreateTemporalBudgetTool();
        $r = $tool->createTemporalBudget('   ', 1);
        $this->assertFalse($this->decode($r)['success']);
    }

    public function test_update_temporal_budget_ownership_kills_removal(): void
    {
        $this->seedUser(1);
        $this->seedUser(2, 'O');
        $id = $this->seedTemporalBudget(1, 'Original');

        $tool = new UpdateTemporalBudgetTool();
        $r = $tool->updateTemporalBudget($id, 2, 'Hijacked');
        $this->assertFalse($this->decode($r)['success']);

        $row = $this->capsule->getConnection()->table('temporal_budgets')->where('id_temporal_budget', $id)->first();
        $this->assertSame('Original', $row->name);
    }

    public function test_add_temporal_budget_outflow_persists_is_in_budget_exact(): void
    {
        // Mutante: isInBudget ? 1 : 0 → 0 : 1
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');

        $tool = new AddTemporalBudgetOutflowTool();
        $tool->addTemporalBudgetOutflow(idTemporalBudget: $tbId, idOutflowType: $otId, idCategory: $this->seedCategory(1, $otId, 'C'), idPorcent: $pId, amount: 100.0, isInBudget: false, idUser: 1);

        $row = $this->capsule->getConnection()->table('temporal_budgets_outflow')->first();
        $this->assertSame(0, (int) $row->is_in_budget);
    }

    public function test_update_temporal_budget_outflow_amount_zero_rejected(): void
    {
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $id = $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 100.0);

        $tool = new UpdateTemporalBudgetOutflowTool();
        $r = $tool->updateTemporalBudgetOutflow($id, 1, amount: 0);
        $this->assertFalse($this->decode($r)['success']);
    }

    public function test_disable_enable_temporal_budget_outflow_persists_status(): void
    {
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $id = $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 100.0, ['status' => 1]);

        (new DisableTemporalBudgetOutflowTool())->disableTemporalBudgetOutflow($id, 1);
        $row = $this->capsule->getConnection()->table('temporal_budgets_outflow')->where('id_temporal_budget_outflow', $id)->first();
        $this->assertSame(0, (int) $row->status);

        (new EnableTemporalBudgetOutflowTool())->enableTemporalBudgetOutflow($id, 1);
        $row = $this->capsule->getConnection()->table('temporal_budgets_outflow')->where('id_temporal_budget_outflow', $id)->first();
        $this->assertSame(1, (int) $row->status);
    }

    public function test_execute_temporal_budget_marks_items_as_inactive(): void
    {
        // Mutante: si no marca status=0, el item seguiría activo
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'C');
        $pId = $this->seedDeposit(1, 'D');
        $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 100.0, ['id_category' => $cId, 'status' => 1]);
        $this->seedInflow(1, $this->seedInflowType(1, 'Sal'), 1000000.0);
        $this->capsule->getConnection()->table('inflow_porcent')->insert([
            'id_inflow' => 1, 'id_porcent' => $pId, 'porcent' => 100, 'status' => 1, 'create_at' => date('Y-m-d H:i:s'),
        ]);

        $tool = new ExecuteTemporalBudgetTool();
        $r = $tool->executeTemporalBudget($tbId, 1);
        $this->assertTrue($this->decode($r)['success']);

        $row = $this->capsule->getConnection()->table('temporal_budgets_outflow')->first();
        $this->assertSame(0, (int) $row->status, 'item should be marked inactive after execution');
    }

    public function test_execute_temporal_budget_creates_outflow_with_exact_amount(): void
    {
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'C');
        $pId = $this->seedDeposit(1, 'D');
        $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 250.75, ['id_category' => $cId, 'status' => 1]);
        $this->seedInflow(1, $this->seedInflowType(1, 'Sal'), 1000000.0);
        $this->capsule->getConnection()->table('inflow_porcent')->insert([
            'id_inflow' => 1, 'id_porcent' => $pId, 'porcent' => 100, 'status' => 1, 'create_at' => date('Y-m-d H:i:s'),
        ]);

        $tool = new ExecuteTemporalBudgetTool();
        $r = $tool->executeTemporalBudget($tbId, 1);

        $out = $this->capsule->getConnection()->table('outflows')->first();
        $this->assertEquals(250.75, (float) $out->amount, 'outflow amount must match item amount exactly');
    }

    public function test_execute_temporal_budget_rollback_on_insufficient_balance(): void
    {
        // Sin income, ejecutar el budget debe fallar y no dejar outflows
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'C');
        $pId = $this->seedDeposit(1, 'D');
        $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 100.0, ['id_category' => $cId, 'status' => 1]);

        $tool = new ExecuteTemporalBudgetTool();
        $r = $tool->executeTemporalBudget($tbId, 1);
        $this->assertFalse($this->decode($r)['success']);
        $this->assertSame(0, (int) $this->capsule->getConnection()->table('outflows')->count());
    }

    public function test_execute_temporal_budget_item_persists_outflow(): void
    {
        $this->seedUser();
        $tbId = $this->seedTemporalBudget(1, 'B');
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'C');
        $pId = $this->seedDeposit(1, 'D');
        $id = $this->seedTemporalBudgetOutflow(1, $tbId, $otId, $pId, 100.0, ['id_category' => $cId, 'status' => 1]);
        $this->seedInflow(1, $this->seedInflowType(1, 'Sal'), 1000000.0);
        $this->capsule->getConnection()->table('inflow_porcent')->insert([
            'id_inflow' => 1, 'id_porcent' => $pId, 'porcent' => 100, 'status' => 1, 'create_at' => date('Y-m-d H:i:s'),
        ]);

        $tool = new ExecuteTemporalBudgetItemTool();
        $r = $tool->executeTemporalBudgetItem($id, 1);

        $this->assertTrue($this->decode($r)['success']);
        $row = $this->capsule->getConnection()->table('temporal_budgets_outflow')->where('id_temporal_budget_outflow', $id)->first();
        $this->assertSame(0, (int) $row->status);
    }

    // ============================================================
    // NOTES / NOTIFICATIONS — mutations
    // ============================================================

    public function test_create_note_persists_description_and_total(): void
    {
        $this->seedUser();
        $tool = new CreateNoteTool();
        $tool->createNote(description: 'Test Note', total: 999.99, idUser: 1);

        $row = $this->capsule->getConnection()->table('notes')->first();
        $this->assertSame('Test Note', $row->description);
        $this->assertEquals(999.99, (float) $row->total);
    }

    public function test_update_note_persists_changes(): void
    {
        $this->seedUser();
        $id = $this->seedNote(1, 100.0, 'Old', 1);

        $tool = new UpdateNoteTool();
        $tool->updateNote($id, 1, 'New', 200.0);

        $row = $this->capsule->getConnection()->table('notes')->where('id_note', $id)->first();
        $this->assertSame('New', $row->description);
        $this->assertEquals(200.0, (float) $row->total);
    }

    public function test_disable_note_persists_status_zero(): void
    {
        $this->seedUser();
        $id = $this->seedNote(1, 100.0, 'X', 1);

        $tool = new DisableNoteTool();
        $tool->disableNote($id, 1);

        $row = $this->capsule->getConnection()->table('notes')->where('id_note', $id)->first();
        $this->assertSame(0, (int) $row->status);
    }

    public function test_mark_notification_read_persists_readed_one(): void
    {
        // Mutante: readed=1 → readed=0
        $this->seedUser();
        $this->seedNotificationType('egress', 'E');
        $id = $this->seedNotification(1, 'egress', 0);

        $tool = new MarkNotificationReadTool();
        $tool->markNotificationRead($id, 1);

        $row = $this->capsule->getConnection()->table('notifications')->where('id_notification', $id)->first();
        $this->assertSame(1, (int) $row->readed);
    }

    // ============================================================
    // REPORTS — mutations
    // ============================================================

    public function test_get_net_worth_exact_calculation(): void
    {
        // Mutante: signo de la resta, o falta de cast
        $this->seedUser();
        $this->seedInflow(1, $this->seedInflowType(1, 'S'), 1000.0);
        $this->seedInflow(1, $this->seedInflowType(1, 'S'), 500.0);
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $this->seedOutflow(1, $otId, $pId, 300.0);

        $tool = new GetNetWorthTool();
        $r = $tool->getNetWorth(1);
        // 1000+500-300=1200
        $this->assertEquals(1200.0, $this->decode($r)['data']['net_worth']);
        $this->assertEquals(1500.0, $this->decode($r)['data']['total_income']);
        $this->assertEquals(300.0, $this->decode($r)['data']['total_outflow']);
    }

    public function test_get_net_worth_with_loans_subtracts_from_me_loans(): void
    {
        // Mutante: si no resta loans FROM_ME, o suma TO_ME
        $this->seedUser();
        $this->seedInflow(1, $this->seedInflowType(1, 'S'), 1000.0);
        $this->seedMoneyLoan(1, 'FROM_ME', 100.0, 1);
        $this->seedMoneyLoan(1, 'TO_ME', 50.0, 1);  // no debe contar

        $tool = new GetNetWorthWithLoansTool();
        $r = $tool->getNetWorthWithLoans(1);
        // 1000 - 100 (FROM_ME) = 900
        $this->assertEquals(900.0, $this->decode($r)['data']['net_worth']);
        $this->assertEquals(100.0, $this->decode($r)['data']['loans_from_me']);
    }

    public function test_get_net_worth_ignores_inactive_flows(): void
    {
        // Mutante: si no filtra status=1
        $this->seedUser();
        $this->seedInflow(1, $this->seedInflowType(1, 'S'), 1000.0);
        $this->seedInflow(1, $this->seedInflowType(1, 'S'), 99999.0);
        $this->capsule->getConnection()->table('inflows')->where('id_user', 1)->orderBy('id_inflow', 'desc')->limit(1)->update(['status' => 0]);

        $tool = new GetNetWorthTool();
        $r = $tool->getNetWorth(1);
        $this->assertEquals(1000.0, $this->decode($r)['data']['total_income'], 'inactive inflows must NOT count');
    }

    // ============================================================
    // GETTERS — mutations
    // ============================================================

    public function test_get_outflow_returns_complete_fields(): void
    {
        // Mutante: si no se devuelven todos los campos
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'C');
        $pId = $this->seedDeposit(1, 'D');
        $id = $this->seedOutflow(1, $otId, $pId, 100.5, ['id_category' => $cId, 'description' => 'full desc']);

        $tool = new GetOutflowTool();
        $data = $this->decode($tool->getOutflow($id, 1))['data'];
        $this->assertSame($id, $data['id_outflow']);
        $this->assertSame($otId, $data['id_outflow_type']);
        $this->assertSame($cId, $data['id_category']);
        $this->assertSame($pId, $data['id_porcent']);
        $this->assertEquals(100.5, $data['amount']);
        $this->assertSame('full desc', $data['description']);
    }

    public function test_get_inflow_returns_distribution(): void
    {
        // Mutante: si no se devuelve la distribución
        $this->seedUser();
        $itId = $this->seedInflowType(1, 'S');
        $id = $this->seedInflow(1, $itId, 1000.0);
        $pId1 = $this->seedDeposit(1, 'D1');
        $pId2 = $this->seedDeposit(1, 'D2');
        $this->seedInflowPorcent($id, $pId1, 70);
        $this->seedInflowPorcent($id, $pId2, 30);

        $tool = new GetInflowTool();
        $data = $this->decode($tool->getInflow($id, 1))['data'];
        $this->assertCount(2, $data['distribution']);
        $percentages = array_column($data['distribution'], 'porcent');
        sort($percentages);
        $this->assertSame([30, 70], $percentages);
    }

    public function test_list_notes_excludes_inactive_by_default(): void
    {
        // Mutante: si includeInactive filter falla
        $this->seedUser();
        $this->seedNote(1, 100.0, 'Active', 1);
        $this->seedNote(1, 200.0, 'Inactive', 0);

        $tool = new ListNotesTool();
        $this->assertSame(1, $this->decode($tool->listNotes(1))['count']);
        $this->assertSame(2, $this->decode($tool->listNotes(1, includeInactive: true))['count']);
    }

    public function test_list_notifications_only_unread_filter(): void
    {
        // Mutante: si onlyUnread se ignora
        $this->seedUser();
        $this->seedNotificationType('egress', 'E');
        $this->seedNotification(1, 'egress', 0);
        $this->seedNotification(1, 'egress', 0);
        $this->seedNotification(1, 'egress', 1);

        $tool = new ListNotificationsTool();
        $this->assertSame(3, $this->decode($tool->listNotifications(1))['count']);
        $this->assertSame(2, $this->decode($tool->listNotifications(1, onlyUnread: true))['count']);
    }

    public function test_list_investment_retirements_persists_and_returns(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $outId = $this->seedOutflow(1, $otId, $pId, 1000.0);
        $invId = $this->seedInvestment($outId);
        $this->seedInvestmentRetirement(1, $invId, 100.0);
        $this->seedInvestmentRetirement(1, $invId, 200.0);

        $tool = new ListInvestmentRetirementsTool();
        $this->assertSame(2, $this->decode($tool->listInvestmentRetirements($invId, 1))['count']);
    }

    // ============================================================
    // GET MONTHLY BUDGET — mutation
    // ============================================================

    public function test_get_monthly_budget_handles_no_data(): void
    {
        // Mutante: si no maneja el caso vacío
        $this->seedUser();
        $tool = new GetMonthlyBudgetTool();
        $r = $tool->getMonthlyBudget(1);
        $data = $this->decode($r)['data'];
        $this->assertFalse($data['has_budget']);
    }

    public function test_get_monthly_budget_returns_data_when_exists(): void
    {
        $this->seedUser();
        $tool = new SetMonthlyBudgetTool();
        $tool->setMonthlyBudget(total: 1500.0, idUser: 1, description: 'test');

        $get = new GetMonthlyBudgetTool();
        $data = $this->decode($get->getMonthlyBudget(1))['data'];
        $this->assertTrue($data['has_budget']);
        $this->assertEquals(1500.0, $data['budget']);
    }

    public function test_list_temporal_budgets_excludes_other_users(): void
    {
        $this->seedUser(1);
        $this->seedUser(2, 'O');
        $this->seedTemporalBudget(1, 'B1');
        $this->seedTemporalBudget(2, 'B2');

        $tool = new ListTemporalBudgetsTool();
        $this->assertSame(1, $this->decode($tool->listTemporalBudgets(1))['count']);
        $this->assertSame(1, $this->decode($tool->listTemporalBudgets(2))['count']);
    }
}