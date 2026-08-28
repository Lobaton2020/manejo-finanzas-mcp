<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Database\Capsule\Manager as Capsule;
use PDO;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
            'foreign_key_constraints' => true,
        ]);
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();

        \Connection::setCapsule($this->capsule);

        \Tests\Schema::migrate($this->capsule->getConnection()->getPdo());
    }

    protected function tearDown(): void
    {
        \Connection::resetCapsule();
        parent::tearDown();
    }

    protected function seedUser(int $idUser = 1, string $name = 'Test User'): int
    {
        $this->capsule->getConnection()->table('users')->insert([
            'id_user'         => $idUser,
            'id_rol'          => 1,
            'id_document_type'=> 1,
            'number_document' => (string) $idUser,
            'complete_name'   => $name,
            'email'           => "user{$idUser}@test.local",
            'password'        => 'x',
            'status'          => 1,
            'update_at'       => date('Y-m-d H:i:s'),
            'create_at'       => date('Y-m-d H:i:s'),
        ]);
        return $idUser;
    }

    protected function seedInflowType(int $idUser, string $name = 'Salario', int $status = 1): int
    {
        return $this->insertAndReturnId('inflowtypes', [
            'id_user'    => $idUser,
            'name'       => $name,
            'status'     => $status,
            'create_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    protected function seedOutflowType(int $idUser, string $name = 'Comida', int $status = 1): int
    {
        return $this->insertAndReturnId('outflowtypes', [
            'id_user'    => $idUser,
            'name'       => $name,
            'status'     => $status,
            'create_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    protected function seedCategory(int $idUser, int $idOutflowType, string $name = 'Restaurantes', int $status = 1): int
    {
        return $this->insertAndReturnId('categories', [
            'id_user'         => $idUser,
            'id_outflow_type' => $idOutflowType,
            'name'            => $name,
            'status'          => $status,
            'create_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    protected function seedDeposit(int $idUser, string $name = 'Efectivo', int $status = 1): int
    {
        return $this->insertAndReturnId('porcents', [
            'id_user'    => $idUser,
            'name'       => $name,
            'status'     => $status,
            'create_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    protected function seedInflow(int $idUser, int $idInflowType, float $total, string $setDate = null): int
    {
        return $this->insertAndReturnId('inflows', [
            'id_user'        => $idUser,
            'id_inflow_type' => $idInflowType,
            'total'          => $total,
            'set_date'       => $setDate ?? date('Y-m-d'),
            'status'         => 1,
            'update_at'      => date('Y-m-d H:i:s'),
            'create_at'      => date('Y-m-d H:i:s'),
        ]);
    }

    protected function seedInflowPorcent(int $idInflow, int $idPorcent, int $porcent = 100): int
    {
        return $this->insertAndReturnId('inflow_porcent', [
            'id_inflow'  => $idInflow,
            'id_porcent' => $idPorcent,
            'porcent'    => $porcent,
            'status'     => 1,
            'create_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    protected function seedOutflow(int $idUser, int $idOutflowType, int $idPorcent, float $amount, array $overrides = []): int
    {
        return $this->insertAndReturnId('outflows', array_merge([
            'id_user'         => $idUser,
            'id_outflow_type' => $idOutflowType,
            'id_porcent'      => $idPorcent,
            'amount'          => $amount,
            'set_date'        => date('Y-m-d'),
            'status'          => 1,
            'update_at'       => date('Y-m-d H:i:s'),
            'create_at'       => date('Y-m-d H:i:s'),
            'is_in_budget'    => 0,
        ], $overrides));
    }

    protected function seedGroup(int $idUser, string $name = 'Cripto', ?int $idGroupInvestment = null): int
    {
        return $this->insertAndReturnId('group_investments', [
            'id_group_investment' => $idGroupInvestment,
            'id_user'             => $idUser,
            'name'                => $name,
            'description'         => null,
            'created_at'          => date('Y-m-d H:i:s'),
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);
    }

    protected function seedInvestment(int $idOutflow, ?int $idGroupInvestment = null, array $overrides = []): int
    {
        return $this->insertAndReturnId('investments', array_merge([
            'id_outflow'                => $idOutflow,
            'percent_annual_effective'  => 0,
            'state'                     => 'Creado',
            'init_date'                 => date('Y-m-d'),
            'end_date'                  => date('Y-m-d', strtotime('+1 month')),
            'real_retribution'          => 0,
            'risk_level'                => 'Conservador',
            'updated_at'                => date('Y-m-d H:i:s'),
            'created_at'                => date('Y-m-d H:i:s'),
            'id_group_investment'       => $idGroupInvestment,
        ], $overrides));
    }

    protected function seedInvestmentRetirement(int $idUser, int $idInvestment, float $retirementAmount, array $overrides = []): int
    {
        return $this->insertAndReturnId('retirement_investments', array_merge([
            'id_user'           => $idUser,
            'id_investment'     => $idInvestment,
            'descripcion'       => 'Retiro test',
            'retirement_amount' => $retirementAmount,
            'init_date'         => date('Y-m-d'),
            'end_date'          => date('Y-m-d'),
            'real_retribution'  => 0,
            'created_at'        => date('Y-m-d H:i:s'),
        ], $overrides));
    }

    protected function seedTemporalBudget(int $idUser, string $name = 'Budget test', ?string $description = null): int
    {
        return $this->insertAndReturnId('temporal_budgets', [
            'id_user'     => $idUser,
            'name'        => $name,
            'description' => $description,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }

    protected function seedTemporalBudgetOutflow(int $idUser, int $idTemporalBudget, int $idOutflowType, int $idPorcent, float $amount, array $overrides = []): int
    {
        return $this->insertAndReturnId('temporal_budgets_outflow', array_merge([
            'id_user'         => $idUser,
            'id_temporal_budget' => $idTemporalBudget,
            'id_outflow_type' => $idOutflowType,
            'id_porcent'      => $idPorcent,
            'amount'          => $amount,
            'status'          => 1,
            'is_in_budget'    => 1,
            'update_at'       => date('Y-m-d H:i:s'),
            'create_at'       => date('Y-m-d H:i:s'),
        ], $overrides));
    }

    protected function seedNote(int $idUser, ?float $total = null, string $description = 'Nota test', int $status = 1): int
    {
        return $this->insertAndReturnId('notes', [
            'id_user'     => $idUser,
            'description' => $description,
            'total'       => $total,
            'status'      => $status,
            'create_at'   => date('Y-m-d H:i:s'),
        ]);
    }

    protected function seedBudget(int $idUser, float $total, string $date = null): int
    {
        return $this->insertAndReturnId('budget', [
            'id_user'    => $idUser,
            'total'      => $total,
            'description'=> null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    protected function seedNotificationType(string $key, string $name): void
    {
        $this->capsule->getConnection()->table('notificationtypes')->insert([
            'key_notification_type' => $key,
            'name'                  => $name,
        ]);
    }

    protected function seedNotification(int $idUser, string $keyType, int $readed = 0): int
    {
        return $this->insertAndReturnId('notifications', [
            'id_user'              => $idUser,
            'key_notification_type'=> $keyType,
            'readed'               => $readed,
            'create_at'            => date('Y-m-d H:i:s'),
        ]);
    }

    protected function seedMoneyLoan(int $idUser, string $type = 'FROM_ME', float $total = 100.0, int $status = 1): int
    {
        return $this->insertAndReturnId('moneyloans', [
            'id_user'   => $idUser,
            'description' => 'Test loan',
            'total'     => $total,
            'set_date'  => date('Y-m-d'),
            'status'    => $status,
            'create_at' => date('Y-m-d H:i:s'),
            'type'      => $type,
        ]);
    }

    protected function insertAndReturnId(string $table, array $data): int
    {
        $this->capsule->getConnection()->table($table)->insert($data);
        $pk = $this->capsule->getConnection()->getPdo()->lastInsertId();
        return (int) $pk;
    }

    protected function decode(array $response): array
    {
        return json_decode($response['content']['text'], true);
    }
}