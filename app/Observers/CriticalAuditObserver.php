<?php

namespace App\Observers;

use App\Services\AuditLogService;
use Illuminate\Database\Eloquent\Model;

class CriticalAuditObserver
{
    private const CONFIG = [
        'inventarios' => [
            'module' => 'inventario',
            'level' => 'critical',
            'fields' => ['cdi_inv', 'cre_inv', 'ubi_inv'],
        ],
        'ventas' => [
            'module' => 'ventas',
            'level' => 'critical',
            'fields' => ['tot_ven', 'est_ven', 'mpa_ven', 'est_logistico_ven', 'fec_entrega_ven'],
        ],
        'detalle_ventas' => [
            'module' => 'ventas',
            'level' => 'warning',
            'fields' => ['can_dve', 'pun_dve', 'sub_dve'],
        ],
        'reservas' => [
            'module' => 'reservas',
            'level' => 'critical',
            'fields' => [
                'est_res',
                'est_pago_res',
                'est_logistico_res',
                'tot_res',
                'ven_res',
                'fec_disponible_recojo_res',
                'fec_limite_recojo_res',
                'fec_recogida_res',
            ],
        ],
        'detalle_reservas' => [
            'module' => 'reservas',
            'level' => 'warning',
            'fields' => ['can_dre', 'pun_dre', 'sub_dre'],
        ],
        'venta_pagos' => [
            'module' => 'pagos',
            'level' => 'critical',
            'fields' => ['monto_declarado_vpg', 'monto_verificado_vpg', 'est_vpg', 'metodo_pago_vpg', 'obs_vpg'],
        ],
        'reserva_pagos' => [
            'module' => 'pagos',
            'level' => 'critical',
            'fields' => ['monto_declarado_rpg', 'monto_confirmado_rpg', 'est_rpg', 'metodo_pago_rpg', 'obs_rpg'],
        ],
        'prendas' => [
            'module' => 'prendas',
            'level' => 'warning',
            'fields' => ['prb_pre', 'est_pre', 'cod_categorias_pre', 'cod_marcas_pre'],
        ],
        'prenda_variantes' => [
            'module' => 'variantes',
            'level' => 'warning',
            'fields' => ['pve_prv', 'est_prv', 'cod_colores_prv', 'cod_tallas_prv', 'sku_prv'],
        ],
        'users' => [
            'module' => 'usuarios',
            'level' => 'critical',
            'fields' => ['cod_roles_usu', 'est_usu', 'email', 'tel_usu'],
        ],
        'roles' => [
            'module' => 'usuarios',
            'level' => 'critical',
            'fields' => ['nom_rol', 'est_rol'],
        ],
    ];

    public function __construct(private readonly AuditLogService $audit)
    {
    }

    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->record($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    public function restored(Model $model): void
    {
        $this->record($model, 'restored');
    }

    public function forceDeleted(Model $model): void
    {
        $this->record($model, 'force_deleted');
    }

    private function record(Model $model, string $action): void
    {
        $table = $model->getTable();
        $config = self::CONFIG[$table] ?? null;

        if (!$config) {
            return;
        }

        [$oldValues, $newValues, $changedFields] = $this->valuesFor($model, $config['fields'], $action);

        if ($changedFields === []) {
            return;
        }

        $this->audit->record(
            $table,
            $model->getKey() ? (int) $model->getKey() : null,
            $action,
            $config['module'],
            $oldValues,
            $newValues,
            $changedFields,
            $config['level'],
            $this->descriptionFor($table, $action, $model->getKey(), $changedFields),
        );
    }

    private function valuesFor(Model $model, array $fields, string $action): array
    {
        $oldValues = [];
        $newValues = [];
        $changedFields = [];

        foreach ($fields as $field) {
            if (!$this->attributeExists($model, $field)) {
                continue;
            }

            if ($action === 'created' || $action === 'restored') {
                $newValues[$field] = $model->getAttribute($field);
                $changedFields[] = $field;
                continue;
            }

            if ($action === 'deleted' || $action === 'force_deleted') {
                $oldValues[$field] = $model->getAttribute($field);
                $changedFields[] = $field;
                continue;
            }

            if (!$model->wasChanged($field)) {
                continue;
            }

            $oldValues[$field] = $model->getOriginal($field);
            $newValues[$field] = $model->getAttribute($field);
            $changedFields[] = $field;
        }

        return [$oldValues, $newValues, $changedFields];
    }

    private function attributeExists(Model $model, string $field): bool
    {
        return array_key_exists($field, $model->getAttributes())
            || array_key_exists($field, $model->getOriginal());
    }

    private function descriptionFor(string $table, string $action, mixed $key, array $changedFields): string
    {
        $record = $key ? ('registro #' . $key) : 'registro sin identificador';

        return 'Auditoria critica ' . $action . ' en ' . $table . ' para ' . $record . ': ' . implode(', ', $changedFields) . '.';
    }
}
