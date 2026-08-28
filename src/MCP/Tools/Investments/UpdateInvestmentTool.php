<?php

declare(strict_types=1);

namespace Tools\Investments;

use Mcp\Capability\Attribute\McpTool;
use Tools\BaseTool;

class UpdateInvestmentTool extends BaseTool
{
    #[McpTool(
        name: 'update_investment',
        description: 'Actualiza campos editables de una inversion (initDate, endDate, state, riskLevel, realRetribution, percentAnnualEffective, idGroupInvestment). Solo si pertenece al idUser.'
    )]
    public function updateInvestment(
        int $idInvestment,
        int $idUser = 1,
        ?string $initDate = null,
        ?string $endDate = null,
        ?string $state = null,
        ?string $riskLevel = null,
        ?float $realRetribution = null,
        ?float $percentAnnualEffective = null,
        ?int $idGroupInvestment = null
    ): array {
        return $this->executeWithLogging(function () use ($idInvestment, $idUser, $initDate, $endDate, $state, $riskLevel, $realRetribution, $percentAnnualEffective, $idGroupInvestment) {
            $this->debug('updateInvestment start', compact('idInvestment', 'idUser', 'initDate', 'endDate', 'state', 'riskLevel', 'realRetribution', 'percentAnnualEffective', 'idGroupInvestment'));

            $row = $this->table('investments')->where('id_investment', $idInvestment)->first();
            if (!$row) {
                return $this->validationError('La inversion no existe.');
            }

            $ownerRow = $this->table('outflows')->where('id_outflow', $row->id_outflow)->where('id_user', $idUser)->first();
            if (!$ownerRow) {
                return $this->validationError('La inversion no pertenece al usuario.');
            }

            $data = [];
            if ($initDate !== null && $initDate !== '')              { $data['init_date']               = $initDate; }
            if ($endDate !== null && $endDate !== '')                { $data['end_date']                = $endDate; }
            if ($state !== null && $state !== '')                    { $data['state']                   = $state; }
            if ($riskLevel !== null && $riskLevel !== '')            { $data['risk_level']              = $riskLevel; }
            if ($realRetribution !== null)                           { $data['real_retribution']        = $realRetribution; }
            if ($percentAnnualEffective !== null)                    { $data['percent_annual_effective']= $percentAnnualEffective; }
            if ($idGroupInvestment !== null) {
                $g = $this->table('group_investments')->where('id_group_investment', $idGroupInvestment)->where('id_user', $idUser)->first();
                if (!$g) {
                    return $this->validationError('El grupo no existe o no pertenece al usuario.');
                }
                $data['id_group_investment'] = $idGroupInvestment;
            }

            if (empty($data)) {
                return $this->validationError('Debes enviar al menos un campo a actualizar.');
            }

            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->table('investments')->where('id_investment', $idInvestment)->update($data);
            $updated = $this->table('investments')->where('id_investment', $idInvestment)->first();

            $this->debug('updateInvestment updated', ['fields' => array_keys($data)]);

            return $this->successResponse([
                'id_investment'              => (int) $updated->id_investment,
                'id_outflow'                 => (int) $updated->id_outflow,
                'state'                      => $updated->state,
                'risk_level'                 => $updated->risk_level,
                'init_date'                  => $updated->init_date,
                'end_date'                   => $updated->end_date,
                'real_retribution'           => (float) $updated->real_retribution,
                'percent_annual_effective'   => (float) $updated->percent_annual_effective,
                'id_group_investment'        => $updated->id_group_investment !== null ? (int) $updated->id_group_investment : null,
                'updated_at'                 => $updated->updated_at,
            ], 'Inversion actualizada.');
        }, 'update_investment', compact('idInvestment', 'idUser', 'initDate', 'endDate', 'state', 'riskLevel', 'realRetribution', 'percentAnnualEffective', 'idGroupInvestment'));
    }
}