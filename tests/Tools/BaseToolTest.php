<?php

declare(strict_types=1);

namespace Tests\Tools;

use Tests\TestCase;
use Tools\BaseTool;

class BaseToolTest extends TestCase
{
    public function test_helpers_directly(): void
    {
        $tool = new class extends BaseTool {
            public function debugPub(string $m, array $c = []): void { $this->debug($m, $c); }
            public function infoPub(string $m, array $c = []): void { $this->info($m, $c); }
            public function errorPub(string $m, array $c = []): void { $this->error($m, $c); }
            public function successPub(array $d, string $m = ''): array { return $this->successResponse($d, $m); }
            public function listPub(array $items, string $label = 'items'): array { return $this->listResponse($items, $label); }
            public function errorPub2(string $m, ?string $s = null): array { return $this->errorResponse($m, $s); }
            public function validationPub(string $m): array { return $this->validationError($m); }
            public function userNotFoundPub(): array { return $this->userNotFound(); }
            public function requireUserPub(int $id): array { return $this->requireUser($id); }
            public function transactionPub(callable $cb) { return $this->transaction($cb); }
            public function tablePub(string $t) { return $this->table($t); }
            public function getConnectionPub() { return $this->getConnection(); }
        };

        $this->seedUser();

        $tool->debugPub('msg', ['a' => 1]);
        $tool->infoPub('msg');
        $tool->errorPub('msg');

        $r = $tool->successPub(['x' => 1], 'ok');
        $this->assertSame('ok', json_decode($r['content']['text'], true)['message']);

        $r = $tool->listPub([['a' => 1]]);
        $this->assertSame(1, json_decode($r['content']['text'], true)['count']);

        $r = $tool->errorPub2('err');
        $this->assertFalse(json_decode($r['content']['text'], true)['success']);

        $r = $tool->errorPub2('err', 'fix this');
        $this->assertSame('fix this', json_decode($r['content']['text'], true)['suggestion']);

        $r = $tool->validationPub('tipo de egreso invalido');
        $this->assertStringContainsString('get_outflow_types', json_decode($r['content']['text'], true)['hint']);

        $r = $tool->validationPub('categoria invalida');
        $this->assertStringContainsString('get_categories', json_decode($r['content']['text'], true)['hint']);

        $r = $tool->validationPub('deposito invalido');
        $this->assertStringContainsString('get_available_by_deposits', json_decode($r['content']['text'], true)['hint']);

        $r = $tool->validationPub('porcent invalido');
        $this->assertStringContainsString('get_available_by_deposits', json_decode($r['content']['text'], true)['hint']);

        $r = $tool->validationPub('usuario invalido');
        $this->assertStringContainsString('usuario', json_decode($r['content']['text'], true)['hint']);

        $r = $tool->validationPub('grupo invalido');
        $this->assertStringContainsString('get_investment_groups', json_decode($r['content']['text'], true)['hint']);

        $r = $tool->validationPub('otro error');
        $this->assertStringContainsString('Revisa los parametros', json_decode($r['content']['text'], true)['hint']);

        $r = $tool->userNotFoundPub();
        $this->assertFalse(json_decode($r['content']['text'], true)['success']);

        $this->assertNotEmpty($tool->requireUserPub(1));
        $this->assertEmpty($tool->requireUserPub(999));

        $tool->transactionPub(function () { return 1; });
        $tool->tablePub('users');
        $this->assertNotNull($tool->getConnectionPub());

        $this->expectException(\Exception::class);
        $tool->transactionPub(function () { throw new \Exception('boom'); });
    }
}