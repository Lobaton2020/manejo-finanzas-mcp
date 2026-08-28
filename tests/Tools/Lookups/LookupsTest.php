<?php

declare(strict_types=1);

namespace Tests\Tools\Lookups;

use Tests\TestCase;
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

class LookupsTest extends TestCase
{
    public function test_create_outflow_type(): void
    {
        $this->seedUser();
        $tool = new CreateOutflowTypeTool();
        $res = $tool->createOutflowType(name: 'Comida', idUser: 1);
        $this->assertTrue($this->decode($res)['success']);
        $this->assertGreaterThan(0, $this->decode($res)['data']['id_outflow_type']);

        $bad = $tool->createOutflowType(name: '   ', idUser: 1);
        $this->assertFalse($this->decode($bad)['success']);
    }

    public function test_update_outflow_type(): void
    {
        $this->seedUser();
        $id = $this->seedOutflowType(1, 'Old');
        $tool = new UpdateOutflowTypeTool();

        $res = $tool->updateOutflowType(idOutflowType: $id, idUser: 1, name: 'New', status: 0);
        $this->assertSame('New', $this->decode($res)['data']['name']);

        $this->assertFalse($this->decode($tool->updateOutflowType(idOutflowType: 999, idUser: 1, name: 'X'))['success']);

        $this->seedUser(2, 'Other');
        $this->assertFalse($this->decode($tool->updateOutflowType(idOutflowType: $id, idUser: 2, name: 'X'))['success']);

        $this->assertFalse($this->decode($tool->updateOutflowType(idOutflowType: $id, idUser: 1))['success']);
    }

    public function test_disable_enable_outflow_type(): void
    {
        $this->seedUser();
        $id = $this->seedOutflowType(1, 'X', 1);
        $this->assertTrue($this->decode((new DisableOutflowTypeTool())->disableOutflowType($id, 1))['success']);
        $this->assertFalse($this->decode((new DisableOutflowTypeTool())->disableOutflowType(999, 1))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode((new DisableOutflowTypeTool())->disableOutflowType($id, 2))['success']);

        $this->assertTrue($this->decode((new EnableOutflowTypeTool())->enableOutflowType($id, 1))['success']);
        $this->assertFalse($this->decode((new EnableOutflowTypeTool())->enableOutflowType(999, 1))['success']);
        $this->assertFalse($this->decode((new EnableOutflowTypeTool())->enableOutflowType($id, 2))['success']);
    }

    public function test_create_inflow_type(): void
    {
        $this->seedUser();
        $res = (new CreateInflowTypeTool())->createInflowType(name: 'Salario', idUser: 1);
        $this->assertTrue($this->decode($res)['success']);

        $bad = (new CreateInflowTypeTool())->createInflowType(name: '   ', idUser: 1);
        $this->assertFalse($this->decode($bad)['success']);
    }

    public function test_update_inflow_type(): void
    {
        $this->seedUser();
        $id = $this->seedInflowType(1, 'Old');
        $tool = new UpdateInflowTypeTool();
        $this->assertTrue($this->decode($tool->updateInflowType($id, 1, 'New'))['success']);
        $this->assertTrue($this->decode($tool->updateInflowType($id, 1, status: 0))['success']);
        $this->assertFalse($this->decode($tool->updateInflowType(999, 1, 'X'))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->updateInflowType($id, 2, 'X'))['success']);
        $this->assertFalse($this->decode($tool->updateInflowType($id, 1))['success']);
    }

    public function test_disable_enable_inflow_type(): void
    {
        $this->seedUser();
        $id = $this->seedInflowType(1, 'X', 1);
        $this->assertTrue($this->decode((new DisableInflowTypeTool())->disableInflowType($id, 1))['success']);
        $this->assertFalse($this->decode((new DisableInflowTypeTool())->disableInflowType(999, 1))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode((new DisableInflowTypeTool())->disableInflowType($id, 2))['success']);
        $this->assertTrue($this->decode((new EnableInflowTypeTool())->enableInflowType($id, 1))['success']);
        $this->assertFalse($this->decode((new EnableInflowTypeTool())->enableInflowType(999, 1))['success']);
        $this->assertFalse($this->decode((new EnableInflowTypeTool())->enableInflowType($id, 2))['success']);
    }

    public function test_create_category(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $res = (new CreateCategoryTool())->createCategory(idOutflowType: $otId, name: 'Cat', idUser: 1);
        $this->assertTrue($this->decode($res)['success']);

        $bad1 = (new CreateCategoryTool())->createCategory(idOutflowType: $otId, name: '   ', idUser: 1);
        $this->assertFalse($this->decode($bad1)['success']);

        $bad2 = (new CreateCategoryTool())->createCategory(idOutflowType: 999, name: 'X', idUser: 1);
        $this->assertFalse($this->decode($bad2)['success']);
    }

    public function test_update_category(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'Cat');
        $otId2 = $this->seedOutflowType(1, 'Y');
        $tool = new UpdateCategoryTool();

        $this->assertTrue($this->decode($tool->updateCategory($cId, 1, 'New', 0, $otId2))['success']);
        $this->assertFalse($this->decode($tool->updateCategory(999, 1, 'X'))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->updateCategory($cId, 2, 'X'))['success']);
        $this->assertFalse($this->decode($tool->updateCategory($cId, 1, null, null, 999))['success']);
        $this->assertFalse($this->decode($tool->updateCategory($cId, 1))['success']);
    }

    public function test_disable_enable_category(): void
    {
        $this->seedUser();
        $otId = $this->seedOutflowType(1, 'X');
        $cId = $this->seedCategory(1, $otId, 'Cat', 1);
        $this->assertTrue($this->decode((new DisableCategoryTool())->disableCategory($cId, 1))['success']);
        $this->assertFalse($this->decode((new DisableCategoryTool())->disableCategory(999, 1))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode((new DisableCategoryTool())->disableCategory($cId, 2))['success']);
        $this->assertTrue($this->decode((new EnableCategoryTool())->enableCategory($cId, 1))['success']);
        $this->assertFalse($this->decode((new EnableCategoryTool())->enableCategory(999, 1))['success']);
        $this->assertFalse($this->decode((new EnableCategoryTool())->enableCategory($cId, 2))['success']);
    }

    public function test_create_deposit(): void
    {
        $this->seedUser();
        $res = (new CreateDepositTool())->createDeposit(name: 'Efectivo', idUser: 1);
        $this->assertTrue($this->decode($res)['success']);

        $bad = (new CreateDepositTool())->createDeposit(name: '   ', idUser: 1);
        $this->assertFalse($this->decode($bad)['success']);
    }

    public function test_list_deposits(): void
    {
        $this->seedUser();
        $this->seedDeposit(1, 'Active', 1);
        $this->seedDeposit(1, 'Inactive', 0);
        $tool = new ListDepositsTool();

        $res = $tool->getDeposits(idUser: 1);
        $this->assertSame(1, $this->decode($res)['count']);

        $all = $tool->getDeposits(idUser: 1, includeInactive: true);
        $this->assertSame(2, $this->decode($all)['count']);

        $noUser = $tool->getDeposits(idUser: 999);
        $this->assertFalse($this->decode($noUser)['success']);
    }

    public function test_update_deposit(): void
    {
        $this->seedUser();
        $id = $this->seedDeposit(1, 'Old');
        $tool = new UpdateDepositTool();
        $this->assertTrue($this->decode($tool->updateDeposit($id, 1, 'New', 0))['success']);
        $this->assertFalse($this->decode($tool->updateDeposit(999, 1, 'X'))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->updateDeposit($id, 2, 'X'))['success']);
        $this->assertFalse($this->decode($tool->updateDeposit($id, 1))['success']);
    }

    public function test_disable_enable_deposit(): void
    {
        $this->seedUser();
        $id = $this->seedDeposit(1, 'X', 1);
        $this->assertTrue($this->decode((new DisableDepositTool())->disableDeposit($id, 1))['success']);
        $this->assertFalse($this->decode((new DisableDepositTool())->disableDeposit(999, 1))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode((new DisableDepositTool())->disableDeposit($id, 2))['success']);
        $this->assertTrue($this->decode((new EnableDepositTool())->enableDeposit($id, 1))['success']);
        $this->assertFalse($this->decode((new EnableDepositTool())->enableDeposit(999, 1))['success']);
        $this->assertFalse($this->decode((new EnableDepositTool())->enableDeposit($id, 2))['success']);
    }
}