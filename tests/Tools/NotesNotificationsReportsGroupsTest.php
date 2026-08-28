<?php

declare(strict_types=1);

namespace Tests\Tools;

use Tests\TestCase;
use Tools\Notes\ListNotesTool;
use Tools\Notes\CreateNoteTool;
use Tools\Notes\UpdateNoteTool;
use Tools\Notes\DisableNoteTool;
use Tools\Notifications\ListNotificationsTool;
use Tools\Notifications\MarkNotificationReadTool;
use Tools\Reports\GetNetWorthTool;
use Tools\Reports\GetNetWorthWithLoansTool;
use Tools\EgressMoney\CreateInvestmentGroupTool;
use Tools\EgressMoney\UpdateInvestmentGroupTool;

class NotesNotificationsReportsGroupsTest extends TestCase
{
    public function test_notes_crud(): void
    {
        $this->seedUser();

        $create = (new CreateNoteTool())->createNote(description: 'N1', total: 100.0, idUser: 1);
        $id = $this->decode($create)['data']['id_note'];
        $this->assertGreaterThan(0, $id);

        $this->assertFalse($this->decode((new CreateNoteTool())->createNote(description: '   ', total: 100.0))['success']);

        $this->assertSame(1, $this->decode((new ListNotesTool())->listNotes(1))['count']);

        $idInactive = $this->insertAndReturnId('notes', [
            'id_user' => 1, 'description' => 'old', 'total' => 50, 'status' => 0,
            'create_at' => date('Y-m-d H:i:s'),
        ]);
        $this->assertSame(2, $this->decode((new ListNotesTool())->listNotes(1, includeInactive: true))['count']);

        $this->assertFalse($this->decode((new ListNotesTool())->listNotes(999))['success']);

        $upd = new UpdateNoteTool();
        $this->assertTrue($this->decode($upd->updateNote($id, 1, 'new', 200.0))['success']);
        $this->assertFalse($this->decode($upd->updateNote(999, 1, 'x'))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($upd->updateNote($id, 2, 'x'))['success']);
        $this->assertFalse($this->decode($upd->updateNote($id, 1))['success']);

        $dis = new DisableNoteTool();
        $this->assertTrue($this->decode($dis->disableNote($id, 1))['success']);
        $this->assertFalse($this->decode($dis->disableNote(999, 1))['success']);
        $this->assertFalse($this->decode($dis->disableNote($id, 2))['success']);
    }

    public function test_notifications(): void
    {
        $this->seedUser();
        $this->seedNotificationType('egress', 'Egreso');
        $this->seedNotification(1, 'egress', 0);
        $this->seedNotification(1, 'egress', 1);

        $tool = new ListNotificationsTool();
        $this->assertSame(2, $this->decode($tool->listNotifications(1))['count']);
        $this->assertSame(1, $this->decode($tool->listNotifications(1, onlyUnread: true))['count']);
        $this->assertSame(1, $this->decode($tool->listNotifications(1, onlyUnread: true, limit: 0))['count']);

        $mark = new MarkNotificationReadTool();
        $firstId = $this->capsule->getConnection()->table('notifications')->where('id_user', 1)->where('readed', 0)->first()->id_notification;
        $this->assertTrue($this->decode($mark->markNotificationRead($firstId, 1))['success']);
        $this->assertFalse($this->decode($mark->markNotificationRead(999, 1))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($mark->markNotificationRead($firstId, 2))['success']);
    }

    public function test_net_worth(): void
    {
        $this->seedUser();
        $inId = $this->seedInflow(1, $this->seedInflowType(1, 'S'), 1000.0);
        $this->capsule->getConnection()->table('outflows')->insert([
            'id_user' => 1, 'id_outflow_type' => 1, 'id_porcent' => 1,
            'amount' => 300.0, 'set_date' => '2026-08-01', 'status' => 1,
            'update_at' => date('Y-m-d H:i:s'), 'create_at' => date('Y-m-d H:i:s'),
            'is_in_budget' => 0,
        ]);
        $this->seedMoneyLoan(1, 'FROM_ME', 50.0, 1);

        $tool = new GetNetWorthTool();
        $this->assertEquals(700.0, $this->decode($tool->getNetWorth(1))['data']['net_worth']);
        $this->assertFalse($this->decode($tool->getNetWorth(999))['success']);

        $wLoans = new GetNetWorthWithLoansTool();
        $this->assertEquals(650.0, $this->decode($wLoans->getNetWorthWithLoans(1))['data']['net_worth']);
        $this->assertFalse($this->decode($wLoans->getNetWorthWithLoans(999))['success']);
    }

    public function test_investment_groups(): void
    {
        $this->seedUser();

        $create = (new CreateInvestmentGroupTool())->createInvestmentGroup(name: 'G1', idUser: 1, description: 'D');
        $id = $this->decode($create)['data']['id_group_investment'];
        $this->assertGreaterThan(0, $id);

        $this->assertFalse($this->decode((new CreateInvestmentGroupTool())->createInvestmentGroup(name: '   '))['success']);

        $upd = new UpdateInvestmentGroupTool();
        $this->assertTrue($this->decode($upd->updateInvestmentGroup($id, 1, 'New', 'D2'))['success']);
        $this->assertFalse($this->decode($upd->updateInvestmentGroup(999, 1, 'X'))['success']);
        $this->seedUser(2, 'O');
        $this->assertFalse($this->decode($upd->updateInvestmentGroup($id, 2, 'X'))['success']);
        $this->assertFalse($this->decode($upd->updateInvestmentGroup($id, 1))['success']);
    }
}