<?php

declare(strict_types=1);

namespace Tests\Tools\Investments;

use Tests\TestCase;
use Tools\Investments\ListInvestmentsTool;
use Tools\Investments\GetInvestmentTool;
use Tools\Investments\UpdateInvestmentTool;
use Tools\Investments\HideInvestmentTool;
use Tools\Investments\ListInvestmentRetirementsTool;
use Tools\Investments\CreateInvestmentRetirementTool;

class InvestmentsTest extends TestCase
{
    public function test_list_investments(): void
    {
        $this->seedUser();
        $outId = $this->seedOutflow(1, $this->seedOutflowType(1, 'Inversion'), $this->seedDeposit(1, 'D'), 100.0);
        $this->capsule->getConnection()->table('investments_view')->insert([
            'id_investment' => 1, 'id_outflow' => $outId, 'percent_annual_effective' => 5.0,
            'state' => 'Creado', 'init_date' => '2026-08-01', 'end_date' => '2026-09-01',
            'real_retribution' => 0, 'updated_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s'),
            'id_user' => 1, 'original_amount' => 100.0, 'amount' => 100.0,
            'description' => 'X', 'name' => 'Inversion',
            'retirement_real_retribution' => 0, 'retirements_amount' => 0,
            'earn_amount' => 0, 'earn_amount_all' => 0,
        ]);

        $tool = new ListInvestmentsTool();
        $this->assertEquals(1, $this->decode($tool->listInvestments(1))['data']['count']);
        $this->assertFalse($this->decode($tool->listInvestments(999))['success']);

        $this->capsule->getConnection()->table('investments_view')->where('id_investment', 1)->update(['state' => 'Ocultar']);
        $this->assertEquals(0, $this->decode($tool->listInvestments(1))['data']['count']);
        $this->assertEquals(1, $this->decode($tool->listInvestments(1, includeHidden: true))['data']['count']);

        $this->assertEquals(0, $this->decode($tool->listInvestments(1, state: 'Completado'))['data']['count']);

        $this->assertEquals(0, $this->decode($tool->listInvestments(1, idGroupInvestment: 999))['data']['count']);
        $this->assertEquals(1, $this->decode($tool->listInvestments(1, idGroupInvestment: null, includeHidden: true))['data']['count']);
    }

    public function test_get_investment(): void
    {
        $this->seedUser();
        $outId = $this->seedOutflow(1, $this->seedOutflowType(1, 'X'), $this->seedDeposit(1, 'D'), 100.0);
        $this->capsule->getConnection()->table('investments_view')->insert([
            'id_investment' => 1, 'id_outflow' => $outId, 'percent_annual_effective' => 5.0,
            'state' => 'Creado', 'init_date' => '2026-08-01', 'end_date' => '2026-09-01',
            'real_retribution' => 0, 'updated_at' => date('Y-m-d H:i:s'), 'created_at' => date('Y-m-d H:i:s'),
            'id_user' => 1, 'original_amount' => 100.0, 'amount' => 100.0,
            'description' => 'X', 'name' => 'Inversion',
            'retirement_real_retribution' => 0, 'retirements_amount' => 0,
            'earn_amount' => 0, 'earn_amount_all' => 0,
        ]);

        $tool = new GetInvestmentTool();
        $this->assertTrue($this->decode($tool->getInvestment(1, 1))['success']);
        $this->assertFalse($this->decode($tool->getInvestment(999, 1))['success']);
        $this->assertFalse($this->decode($tool->getInvestment(1, 2))['success']);
    }

    public function test_update_investment(): void
    {
        $this->seedUser();
        $outId = $this->seedOutflow(1, $this->seedOutflowType(1, 'X'), $this->seedDeposit(1, 'D'), 100.0);
        $invId = $this->seedInvestment($outId);
        $gId = $this->seedGroup(1, 'Grp');
        $tool = new UpdateInvestmentTool();

        $res = $tool->updateInvestment(idInvestment: $invId, idUser: 1, initDate: '2026-08-10', endDate: '2026-09-10', state: 'Activo', riskLevel: 'Moderado', realRetribution: 50.0, percentAnnualEffective: 10.0, idGroupInvestment: $gId);
        $this->assertTrue($this->decode($res)['success']);

        $this->assertFalse($this->decode($tool->updateInvestment(idInvestment: 999, idUser: 1, initDate: '2026-08-10'))['success']);

        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->updateInvestment(idInvestment: $invId, idUser: 2, initDate: '2026-08-10'))['success']);

        $this->assertFalse($this->decode($tool->updateInvestment(idInvestment: $invId, idUser: 1, idGroupInvestment: 999))['success']);

        $this->assertFalse($this->decode($tool->updateInvestment(idInvestment: $invId, idUser: 1))['success']);
    }

    public function test_hide_investment(): void
    {
        $this->seedUser();
        $outId = $this->seedOutflow(1, $this->seedOutflowType(1, 'X'), $this->seedDeposit(1, 'D'), 100.0);
        $invId = $this->seedInvestment($outId);
        $tool = new HideInvestmentTool();

        $this->assertTrue($this->decode($tool->hideInvestment($invId, 1))['success']);
        $this->assertFalse($this->decode($tool->hideInvestment(999, 1))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->hideInvestment($invId, 2))['success']);
    }

    public function test_list_retirements(): void
    {
        $this->seedUser();
        $outId = $this->seedOutflow(1, $this->seedOutflowType(1, 'X'), $this->seedDeposit(1, 'D'), 100.0);
        $invId = $this->seedInvestment($outId);
        $this->seedInvestmentRetirement(1, $invId, 10.0);

        $tool = new ListInvestmentRetirementsTool();
        $this->assertEquals(1, $this->decode($tool->listInvestmentRetirements($invId, 1))['count']);
        $this->assertFalse($this->decode($tool->listInvestmentRetirements(999, 1))['success']);
        $this->assertFalse($this->decode($tool->listInvestmentRetirements($invId, 2))['success']);
    }

    public function test_create_investment_retirement(): void
    {
        $this->seedUser();
        $outId = $this->seedOutflow(1, $this->seedOutflowType(1, 'X'), $this->seedDeposit(1, 'D'), 100.0);
        $invId = $this->seedInvestment($outId);

        $tool = new CreateInvestmentRetirementTool();

        $res = $tool->createInvestmentRetirement(idInvestment: $invId, retirementAmount: 30.0, initDate: '2026-08-15', endDate: '2026-08-20', idUser: 1, realRetribution: 5.0, descripcion: 'parcial');
        $this->assertTrue($this->decode($res)['success']);

        $this->assertFalse($this->decode($tool->createInvestmentRetirement(idInvestment: 999, retirementAmount: 1, initDate: '2026-08-15', endDate: '2026-08-20'))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($tool->createInvestmentRetirement(idInvestment: $invId, retirementAmount: 1, initDate: '2026-08-15', endDate: '2026-08-20', idUser: 2))['success']);

        $this->assertFalse($this->decode($tool->createInvestmentRetirement(idInvestment: $invId, retirementAmount: 0, initDate: '2026-08-15', endDate: '2026-08-20'))['success']);
        $this->assertFalse($this->decode($tool->createInvestmentRetirement(idInvestment: $invId, retirementAmount: 10, initDate: '2026-08-15', endDate: '2026-08-20', realRetribution: 100))['success']);

        $this->assertFalse($this->decode($tool->createInvestmentRetirement(idInvestment: $invId, retirementAmount: 999, initDate: '2026-08-15', endDate: '2026-08-20'))['success']);
    }
}