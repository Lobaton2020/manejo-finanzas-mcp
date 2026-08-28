<?php

declare(strict_types=1);

namespace Tests\Tools;

use Tests\TestCase;
use Tools\EgressMoney\ListOutflowsTool;
use Tools\EgressMoney\GetOutflowTool;
use Tools\EgressMoney\UpdateOutflowTool;
use Tools\InflowMoney\ListInflowsTool;
use Tools\InflowMoney\GetInflowTool;
use Tools\InflowMoney\UpdateInflowTool;

class OutflowsInflowsCrudTest extends TestCase
{
    public function test_list_outflows(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'Cat');
        $pId = $this->seedDeposit(1, 'D');
        $this->seedOutflow(1, $otId, $pId, 100.0, ['id_category' => $cId, 'description' => 'test1']);
        $this->seedOutflow(1, $otId, $pId, 200.0, ['id_category' => $cId, 'description' => 'test2']);

        $tool = new ListOutflowsTool();

        $this->assertSame(2, $this->decode($tool->listOutflows(1))['data']['pagination']['total']);
        $this->assertFalse($this->decode($tool->listOutflows(999))['success']);

        $filtered = $tool->listOutflows(1, idOutflowType: $otId);
        $this->assertSame(2, $this->decode($filtered)['data']['pagination']['total']);

        $desc = $tool->listOutflows(1, description: 'test1');
        $this->assertSame(1, $this->decode($desc)['data']['pagination']['total']);

        $budget = $tool->listOutflows(1, isInBudget: 0);
        $this->assertSame(2, $this->decode($budget)['data']['pagination']['total']);

        $range = $tool->listOutflows(1, dateFrom: '2026-01-01', dateTo: '2026-12-31');
        $this->assertSame(2, $this->decode($range)['data']['pagination']['total']);

        $empty = $tool->listOutflows(1, dateFrom: '2099-01-01');
        $this->assertSame(0, $this->decode($empty)['data']['pagination']['total']);

        $cat = $tool->listOutflows(1, idCategory: $cId);
        $this->assertSame(2, $this->decode($cat)['data']['pagination']['total']);

        $p = $tool->listOutflows(1, idPorcent: $pId);
        $this->assertSame(2, $this->decode($p)['data']['pagination']['total']);

        $asc = $tool->listOutflows(1, sort: 'amount', order: 'ASC');
        $this->assertEquals(100.0, $this->decode($asc)['data']['items'][0]['amount']);

        $badSort = $tool->listOutflows(1, sort: 'bogus');
        $this->assertSame('id_outflow', $this->decode($badSort)['data']['sort']);

        $badLen = $tool->listOutflows(1, length: 9999);
        $this->assertSame(50, $this->decode($badLen)['data']['pagination']['perPage']);

        $page = $tool->listOutflows(1, page: 1, length: 1);
        $this->assertSame(1, $this->decode($page)['data']['pagination']['totalPages']);
    }

    public function test_get_outflow(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $pId = $this->seedDeposit(1, 'D');
        $id = $this->seedOutflow(1, $otId, $pId, 100.0);
        $tool = new GetOutflowTool();

        $this->assertSame($id, $this->decode($tool->getOutflow($id, 1))['data']['id_outflow']);
        $this->assertFalse($this->decode($tool->getOutflow(999, 1))['success']);
        $this->assertFalse($this->decode($tool->getOutflow($id, 2))['success']);
    }

    public function test_update_outflow(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'Cat');
        $cId2 = $this->seedCategory(1, $otId, 'Cat2');
        $pId = $this->seedDeposit(1, 'D');
        $id = $this->seedOutflow(1, $otId, $pId, 100.0, ['id_category' => $cId]);

        $tool = new UpdateOutflowTool();
        $res = $tool->updateOutflow(idOutflow: $id, idUser: 1, amount: 200.0, setDate: '2026-09-01', description: 'new', isInBudget: true, idCategory: $cId2);
        $this->assertTrue($this->decode($res)['success']);

        $this->assertFalse($this->decode($tool->updateOutflow(idOutflow: 999, idUser: 1, amount: 1))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->updateOutflow(idOutflow: $id, idUser: 2, amount: 1))['success']);

        $this->assertFalse($this->decode($tool->updateOutflow(idOutflow: $id, idUser: 1, amount: 0))['success']);
        $this->assertFalse($this->decode($tool->updateOutflow(idOutflow: $id, idUser: 1, idCategory: 999))['success']);
        $this->assertFalse($this->decode($tool->updateOutflow(idOutflow: $id, idUser: 1))['success']);
    }

    public function test_list_inflows(): void
    {
        $this->seedUser();
        $itId = $this->seedInflowType(1, 'S');
        $this->seedInflow(1, $itId, 1000.0, '2026-08-01');
        $this->seedInflow(1, $itId, 2000.0, '2026-08-02');
        $this->capsule->getConnection()->table('inflows')->where('id_user', 1)->update(['description' => 'salario']);

        $tool = new ListInflowsTool();
        $this->assertEquals(2, $this->decode($tool->listInflows(1))['data']['pagination']['total']);
        $this->assertFalse($this->decode($tool->listInflows(999))['success']);

        $this->assertEquals(2, $this->decode($tool->listInflows(1, idInflowType: $itId))['data']['pagination']['total']);
        $this->assertEquals(0, $this->decode($tool->listInflows(1, idInflowType: 999))['data']['pagination']['total']);

        $this->assertEquals(2, $this->decode($tool->listInflows(1, description: 'sal'))['data']['pagination']['total']);

        $this->assertEquals(1, $this->decode($tool->listInflows(1, dateFrom: '2026-08-02'))['data']['pagination']['total']);

        $this->assertEquals(0, $this->decode($tool->listInflows(1, dateFrom: '2099-01-01'))['data']['pagination']['total']);

        $asc = $tool->listInflows(1, sort: 'total', order: 'ASC');
        $this->assertEquals(1000.0, $this->decode($asc)['data']['items'][0]['total']);

        $bad = $tool->listInflows(1, sort: 'X', length: 9999);
        $this->assertSame(50, $this->decode($bad)['data']['pagination']['perPage']);
    }

    public function test_get_inflow(): void
    {
        $this->seedUser();
        $itId = $this->seedInflowType(1, 'S');
        $id = $this->seedInflow(1, $itId, 1000.0);
        $pId = $this->seedDeposit(1, 'D');
        $this->seedInflowPorcent($id, $pId, 100);

        $tool = new GetInflowTool();
        $res = $tool->getInflow($id, 1);
        $this->assertSame(1, count($this->decode($res)['data']['distribution']));

        $this->assertFalse($this->decode($tool->getInflow(999, 1))['success']);
        $this->assertFalse($this->decode($tool->getInflow($id, 2))['success']);
    }

    public function test_update_inflow(): void
    {
        $this->seedUser();
        $itId = $this->seedInflowType(1, 'S');
        $id = $this->seedInflow(1, $itId, 1000.0);
        $itId2 = $this->seedInflowType(1, 'B');

        $tool = new UpdateInflowTool();
        $res = $tool->updateInflow(idInflow: $id, idUser: 1, total: 2000.0, setDate: '2026-09-01', description: 'new', idInflowType: $itId2);
        $this->assertTrue($this->decode($res)['success']);

        $this->assertFalse($this->decode($tool->updateInflow(idInflow: 999, idUser: 1, total: 1))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->updateInflow(idInflow: $id, idUser: 2, total: 1))['success']);
        $this->assertFalse($this->decode($tool->updateInflow(idInflow: $id, idUser: 1, total: 0))['success']);
        $this->assertFalse($this->decode($tool->updateInflow(idInflow: $id, idUser: 1, idInflowType: 999))['success']);
        $this->assertFalse($this->decode($tool->updateInflow(idInflow: $id, idUser: 1))['success']);
    }
}