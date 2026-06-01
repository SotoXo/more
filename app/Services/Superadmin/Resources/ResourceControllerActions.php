<?php

namespace App\Services\Superadmin\Resources;

use App\Models\Role;
use App\Services\ReceiptService;
use App\Services\Superadmin\ReservationService;
use App\Services\Superadmin\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResourceControllerActions
{
    public function __construct(
        private readonly ResourceDefinitionService $definitions,
        private readonly ResourceQueryService $queries,
        private readonly ResourceViewDataService $resourceViewData,
        private readonly ResourceValidationService $validation,
        private readonly ResourceWriteService $writer,
        private readonly ProductCatalogService $products,
        private readonly ProductImageService $productImages,
        private readonly ResourceRowService $rows,
        private readonly ResourceDetailViewService $details,
        private readonly ResourceOptionsService $options,
        private readonly ReservationService $reservations,
        private readonly SaleService $sales,
        private readonly ReceiptService $receipts,
    ) {
    }

    public function index(Request $request, string $section, string $resource)
    {
        $definition = $this->definitions->definition($section, $resource);
        $rows = $this->rows->indexRows($definition, $request);
        $filterControls = $this->resourceViewData->filterControls($definition, $request);
        $sortOptions = $this->resourceViewData->sortOptions($definition);
        $headerActions = $this->resourceViewData->headerActions($definition, $request);
        $segmentedFilters = $this->resourceViewData->segmentedFilters($definition, $request);
        $emptyState = $this->resourceViewData->emptyState($definition, $request);

        return Inertia::render('Superadmin/ResourceIndex', [
            'meta' => $this->definitions->meta($definition),
            'breadcrumbs' => $this->definitions->breadcrumbs($definition),
            'metrics' => $this->resourceViewData->metrics($definition),
            'columns' => $this->resourceViewData->columns($definition, $request),
            'rows' => $rows,
            'headerActions' => $headerActions,
            'segmentedFilters' => $segmentedFilters,
            'filterAction' => $this->resourceViewData->filterAction($definition, $request),
            'filterControls' => $filterControls,
            'sortOptions' => $sortOptions,
            'sortBy' => $request->string('sort_by')->toString(),
            'sortDirection' => $request->input('sort_dir', 'desc'),
            'appliedFilters' => $this->resourceViewData->appliedFilters($filterControls, $sortOptions, $request),
            'emptyState' => $emptyState,
            'errorState' => [
                'title' => 'No se pudo cargar el módulo',
                'description' => 'Vuelve al panel e intenta otra vez.',
                'actionLabel' => 'Volver al panel',
                'actionHref' => '/superadmin',
            ],
        ]);
    }

    public function create(Request $request, string $section, string $resource)
    {
        $definition = $this->definitions->definition($section, $resource);
        abort_unless($this->definitions->supportsCreate($definition), 404);

        if ($this->definitions->definitionKey($definition) === 'catalogo.prendas') {
            return Inertia::render('Superadmin/ProductCreate', $this->products->productEditorPayload(
                $definition,
                null,
                $request,
                'create',
                [
                    'categories' => $this->options->categoryOptions(),
                    'brands' => $this->options->brandOptions(),
                    'seasons' => $this->options->seasonOptions(),
                    'genders' => $this->options->genderOptions(),
                    'colors' => $this->options->colorOptions(),
                    'sizes' => $this->options->sizeOptions(),
                    'sizeSegments' => $this->options->sizeSegmentOptions(),
                    'locations' => $this->options->locationOptions(),
                ],
            ));
        }

        if ($this->definitions->definitionKey($definition) === 'comercial.ventas') {
            return Inertia::render('Superadmin/SaleCreate', $this->sales->createPayload($definition, $request));
        }

        if ($this->definitions->definitionKey($definition) === 'comercial.reservas') {
            return Inertia::render('Superadmin/ReservationCreate', $this->reservations->createPayload($definition, $request));
        }

        $viewData = $this->details->viewData($definition, null, $request);

        $createLabel = match ($this->definitions->definitionKey($definition)) {
            'catalogo.tallas' => $viewData['recordLabel'] ?? 'Nueva talla',
            'catalogo.temporadas' => $viewData['recordLabel'] ?? 'Nueva temporada',
            default => 'Nuevo ' . $definition['singular'],
        };

        return Inertia::render('Superadmin/ResourceForm', [
            'meta' => $this->definitions->meta($definition),
            'breadcrumbs' => $this->definitions->breadcrumbs($definition, $createLabel),
            'mode' => 'create',
            'recordLabel' => null,
            'sections' => $viewData['formSections'],
            'title' => $viewData['formTitle'] ?? null,
            'description' => $viewData['formDescription'] ?? null,
            'submit' => $this->definitions->supportsCreate($definition)
                ? [
                    'action' => $this->definitions->indexHref($definition),
                    'method' => 'post',
                    'deleteAction' => null,
                ]
                : null,
        ]);
    }

    public function show(string $section, string $resource, string $record)
    {
        $definition = $this->definitions->definition($section, $resource);
        $resolvedRecord = $this->queries->resolveRecord($definition, $record);

        if ($this->definitions->definitionKey($definition) === 'catalogo.prendas') {
            return Inertia::render('Superadmin/ProductShow', $this->products->productShowPayload($definition, $resolvedRecord));
        }

        $viewData = $this->details->viewData($definition, $resolvedRecord);

        return Inertia::render('Superadmin/ResourceShow', [
            'meta' => $this->definitions->meta($definition),
            'breadcrumbs' => $this->definitions->breadcrumbs($definition, $viewData['recordLabel']),
            'recordLabel' => $viewData['recordLabel'],
            'summaryMetrics' => $viewData['summaryMetrics'],
            'detailSections' => $viewData['detailSections'],
            'timeline' => $viewData['timeline'],
            'relatedColumns' => $viewData['relatedColumns'],
            'relatedRows' => $viewData['relatedRows'],
            'editHref' => $this->definitions->supportsCrud($definition) ? $this->definitions->editHref($definition, $this->definitions->recordKey($resolvedRecord)) : null,
            'recordActions' => $this->recordActions($definition, $resolvedRecord),
            'paymentForm' => $this->reservationPaymentForm($definition, $resolvedRecord),
            'receiptPanel' => $this->receiptPanel($definition, $resolvedRecord),
        ]);
    }

    public function edit(Request $request, string $section, string $resource, string $record)
    {
        $definition = $this->definitions->definition($section, $resource);
        $resolvedRecord = $this->queries->resolveRecord($definition, $record);

        if (!$this->definitions->supportsCrud($definition)) {
            return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)));
        }

        if ($this->definitions->definitionKey($definition) === 'catalogo.prendas') {
            return Inertia::render('Superadmin/ProductCreate', $this->products->productEditorPayload(
                $definition,
                $resolvedRecord,
                $request,
                'edit',
                [
                    'categories' => $this->options->categoryOptions(),
                    'brands' => $this->options->brandOptions(),
                    'seasons' => $this->options->seasonOptions(),
                    'genders' => $this->options->genderOptions(),
                    'colors' => $this->options->colorOptions(),
                    'sizes' => $this->options->sizeOptions(),
                    'sizeSegments' => $this->options->sizeSegmentOptions(),
                    'locations' => $this->options->locationOptions(),
                ],
            ));
        }

        $viewData = $this->details->viewData($definition, $resolvedRecord, $request);

        return Inertia::render('Superadmin/ResourceForm', [
            'meta' => $this->definitions->meta($definition),
            'breadcrumbs' => $this->definitions->breadcrumbs($definition, $viewData['recordLabel'], 'Editar'),
            'mode' => 'edit',
            'recordLabel' => $viewData['recordLabel'],
            'sections' => $viewData['formSections'],
            'title' => $viewData['formTitle'] ?? null,
            'description' => $viewData['formDescription'] ?? null,
            'submit' => $this->definitions->supportsCrud($definition)
                ? [
                    'action' => $this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)),
                    'method' => 'put',
                    'deleteAction' => $this->deleteActionFor($definition, $resolvedRecord)
                        ? $this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord))
                        : null,
                ]
                : null,
            'stateAction' => $this->stateAction($definition, $resolvedRecord, $request->user()),
        ]);
    }

    public function store(Request $request, string $section, string $resource): RedirectResponse
    {
        $definition = $this->definitions->definition($section, $resource);
        abort_unless($this->definitions->supportsCreate($definition), 404);

        $this->prepareSizeRequest($request, $definition);
        $this->prepareColorRequest($request, $definition);

        if ($this->definitions->definitionKey($definition) === 'comercial.ventas') {
            $validated = $this->sales->validate($request);
            $record = $this->sales->createSale($request, $validated);

            return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($record)))
                ->with('success', 'Venta registrada correctamente.');
        }

        if ($this->definitions->definitionKey($definition) === 'comercial.reservas') {
            $validated = $this->reservations->validateCreate($request);
            $record = $this->reservations->createReservation($request, $validated);

            return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($record)))
                ->with('success', 'Reserva registrada correctamente.');
        }

        if ($this->definitions->definitionKey($definition) === 'catalogo.colores') {
            $existingColor = $this->writer->reusableColorFor((string) $request->input('hex_col'), $request->input('fam_col') ? (string) $request->input('fam_col') : null);

            if ($existingColor) {
                return response()->json([
                    'message' => 'Color existente encontrado.',
                    'option' => $this->options->inlineOption($definition, $existingColor),
                    'reused' => true,
                ]);
            }
        }

        $validated = $this->validation->validateWrite($request, $definition);
        $record = $this->writer->createRecord(
            $request,
            $definition,
            $validated,
            fn (Request $request, array $payload, array $validated) => $this->products->createProductRecord($request, $payload, $validated),
            fn (array $validated, array $payload) => $this->productImages->createImageRecord($validated, $payload),
        );

        return redirect($this->definitions->editHref($definition, $this->definitions->recordKey($record)))
            ->with('success', ucfirst($definition['singular']) . ' creado correctamente.');
    }

    public function storeInline(Request $request, string $section, string $resource): JsonResponse
    {
        $definition = $this->definitions->definition($section, $resource);
        abort_unless($this->definitions->supportsInlineCreate($definition), 404);

        $this->prepareSizeRequest($request, $definition);
        $this->prepareColorRequest($request, $definition);
        $validated = $this->validation->validateWrite($request, $definition);
        $record = $this->writer->createRecord(
            $request,
            $definition,
            $validated,
            fn (Request $request, array $payload, array $validated) => $this->products->createProductRecord($request, $payload, $validated),
            fn (array $validated, array $payload) => $this->productImages->createImageRecord($validated, $payload),
        );

        return response()->json([
            'message' => ucfirst($definition['singular']) . ' creado correctamente.',
            'option' => $this->options->inlineOption($definition, $record),
        ], 201);
    }

    public function update(Request $request, string $section, string $resource, string $record): RedirectResponse
    {
        $definition = $this->definitions->definition($section, $resource);
        $definitionKey = $this->definitions->definitionKey($definition);
        $allowsOperationalAction = $definitionKey === 'comercial.reservas'
            || ($definitionKey === 'comercial.ventas' && $request->filled('sale_action'));

        abort_unless(
            $this->definitions->supportsCrud($definition)
                || $allowsOperationalAction,
            404,
        );

        $resolvedRecord = $this->queries->resolveRecord($definition, $record);

        if ($definitionKey === 'comercial.reservas') {
            if ($request->input('reservation_action') === 'add_payment') {
                $this->reservations->addPayment($request, $resolvedRecord);

                return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                    ->with('success', 'Pago posterior registrado para revisión.');
            }

            if ($request->input('reservation_action') === 'verify_payment') {
                $result = $this->reservations->verifyLatestPendingPayment(
                    $resolvedRecord,
                    (float) $request->input('monto_confirmado_rpg', 0),
                    $request->input('obs_rpg') ? (string) $request->input('obs_rpg') : null,
                    (int) $request->user()->id,
                );

                return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                    ->with($result['accepted'] ? 'success' : 'error', $result['message']);
            }

            if ($request->input('reservation_action') === 'reject_payment') {
                $this->reservations->rejectLatestPendingPayment(
                    $resolvedRecord,
                    $request->input('obs_rpg') ? (string) $request->input('obs_rpg') : null,
                    (int) $request->user()->id,
                );

                return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                    ->with('success', 'Comprobante rechazado y stock liberado.');
            }

            if ($request->input('reservation_action') === 'cancel') {
                $this->reservations->cancel(
                    $resolvedRecord,
                    $request->input('obs_res') ? (string) $request->input('obs_res') : null,
                );

                return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                    ->with('success', 'Reserva cancelada correctamente.');
            }

            if ($request->input('reservation_action') === 'ready') {
                $this->reservations->markReady($resolvedRecord);

                return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                    ->with('success', 'Reserva marcada como lista para recojo.');
            }

            if ($request->input('reservation_action') === 'complete') {
                $this->reservations->complete(
                    $resolvedRecord,
                    (int) $request->user()->id,
                    $request->filled('monto_pago_final') ? (float) $request->input('monto_pago_final') : null,
                    $request->input('metodo_pago_final') ? (string) $request->input('metodo_pago_final') : null,
                    $request->input('obs_res') ? (string) $request->input('obs_res') : null,
                    $request->file('comprobante_pago_final'),
                );

                return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                    ->with('success', 'Pago registrado y venta finalizada correctamente.');
            }

            if ($request->input('reservation_action') === 'settle_completed') {
                $this->reservations->settleCompletedReservation(
                    $resolvedRecord,
                    (int) $request->user()->id,
                    $request->filled('monto_pago_final') ? (float) $request->input('monto_pago_final') : null,
                    $request->input('metodo_pago_final') ? (string) $request->input('metodo_pago_final') : null,
                    $request->input('obs_res') ? (string) $request->input('obs_res') : null,
                    $request->file('comprobante_pago_final'),
                );

                return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                    ->with('success', 'Reserva y compra completada correctamente.');
            }

            if ($request->input('reservation_action') === 'expire') {
                $this->reservations->markExpired(
                    $resolvedRecord,
                    $request->input('obs_res') ? (string) $request->input('obs_res') : null,
                );

                return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                    ->with('success', 'Reserva marcada como vencida y stock liberado.');
            }

            return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                ->with('error', 'Accion no disponible para esta reserva.');
        }

        if ($definitionKey === 'comercial.ventas' && $request->filled('sale_action')) {
            if ($request->input('sale_action') === 'confirm_online') {
                $this->sales->confirmOnlineSale(
                    $resolvedRecord,
                    (float) $request->input('monto_verificado_ven', 0),
                    (int) $request->user()->id,
                    $request->input('obs_vpg') ? (string) $request->input('obs_vpg') : null,
                );

                return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                    ->with('success', 'Venta confirmada correctamente.');
            }

            if ($request->input('sale_action') === 'mark_incomplete') {
                $this->sales->markOnlineSaleIncomplete(
                    $resolvedRecord,
                    (float) $request->input('monto_verificado_ven', 0),
                    (int) $request->user()->id,
                    $request->input('obs_vpg') ? (string) $request->input('obs_vpg') : null,
                );

                return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                    ->with('success', 'Venta marcada como incompleta.');
            }

            if ($request->input('sale_action') === 'cancel_online') {
                $this->sales->cancelOnlineSale(
                    $resolvedRecord,
                    (int) $request->user()->id,
                    $request->input('obs_vpg') ? (string) $request->input('obs_vpg') : null,
                );

                return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                    ->with('success', 'Venta cancelada correctamente.');
            }

            return redirect($this->definitions->showHref($definition, $this->definitions->recordKey($resolvedRecord)))
                ->with('error', 'Accion no disponible para esta venta.');
        }

        $this->prepareSizeRequest($request, $definition);
        $this->prepareColorRequest($request, $definition, $resolvedRecord);
        $validated = $this->validation->validateWrite($request, $definition, $resolvedRecord);
        $this->writer->updateRecord(
            $request,
            $definition,
            $validated,
            $resolvedRecord,
            fn (Request $request, $record, array $payload, array $validated) => $this->products->updateProductRecord($request, $record, $payload, $validated),
            fn ($record, array $validated, array $payload) => $this->productImages->updateImageRecord($record, $validated, $payload),
        );

        return redirect($this->definitions->editHref($definition, $this->definitions->recordKey($resolvedRecord)))
            ->with('success', ucfirst($definition['singular']) . ' actualizado correctamente.');
    }

    public function destroy(Request $request, string $section, string $resource, string $record): RedirectResponse
    {
        $definition = $this->definitions->definition($section, $resource);
        abort_unless($this->definitions->supportsCrud($definition), 404);

        $resolvedRecord = $this->queries->resolveRecord($definition, $record);
        $deleteError = $this->writer->deleteBlockedReason($definition, $resolvedRecord, $request->user());

        if ($deleteError) {
            return redirect($this->definitions->editHref($definition, $this->definitions->recordKey($resolvedRecord)))
                ->with('error', $deleteError);
        }

        try {
            $this->writer->deleteRecord($definition, $resolvedRecord, fn ($record) => $this->productImages->deleteImageRecord($record));
        } catch (\Throwable) {
            return redirect($this->definitions->editHref($definition, $this->definitions->recordKey($resolvedRecord)))
                ->with('error', 'No se pudo eliminar el registro porque tiene dependencias asociadas.');
        }

        return redirect($this->definitions->indexHref($definition))
            ->with('success', ucfirst($definition['singular']) . ' eliminado correctamente.');
    }

    private function stateAction(array $definition, mixed $record, $authUser = null): ?array
    {
        // Los usuarios no tienen accion separada de estado.
        // El estado se gestiona unicamente desde el toggle en el formulario de edicion.
        if ($this->definitions->definitionKey($definition) === 'seguridad.usuarios') {
            return null;
        }

        return null;
    }

    private function recordActions(array $definition, mixed $record): array
    {
        if ($this->definitions->definitionKey($definition) === 'comercial.ventas') {
            return $this->saleRecordActions($definition, $record);
        }

        if ($this->definitions->definitionKey($definition) !== 'comercial.reservas') {
            return [];
        }

        $action = $this->definitions->showHref($definition, $this->definitions->recordKey($record));
        $actions = [];
        $realBalance = max(0, round((float) ($record->tot_res ?? 0) - (float) ($record->monto_pagado_res ?? 0), 2));

        $pendingPayment = $record->pagos?->firstWhere('est_rpg', 'pendiente_revision');

        if ($pendingPayment && in_array($record->est_res, ['por_procesar', 'confirmada', 'lista_recojo'], true)) {
            $actions[] = [
                'label' => 'Aprobar adelanto / pago total',
                'action' => $action,
                'method' => 'put',
                'data' => ['reservation_action' => 'verify_payment'],
                'promptFields' => [
                    ['key' => 'monto_confirmado_rpg', 'label' => 'Monto confirmado'],
                    ['key' => 'obs_rpg', 'label' => 'Observación', 'optional' => true],
                ],
                'kind' => 'primary',
            ];
            $actions[] = [
                'label' => 'Rechazar comprobante',
                'action' => $action,
                'method' => 'put',
                'data' => ['reservation_action' => 'reject_payment'],
                'promptFields' => [
                    ['key' => 'obs_rpg', 'label' => 'Observación del rechazo'],
                ],
                'confirm' => 'Se rechazará el comprobante y se liberará el stock reservado. Continuar?',
                'kind' => 'danger',
            ];
        }

        if (($record->est_res ?? null) === 'confirmada') {
            $actions[] = [
                'label' => 'Lista para recojo',
                'action' => $action,
                'method' => 'put',
                'data' => ['reservation_action' => 'ready'],
                'kind' => 'primary',
            ];
        }

        if (in_array(($record->est_res ?? null), ['confirmada', 'lista_recojo'], true)) {
            $promptFields = [];

            if ($realBalance > 0) {
                $promptFields = [
                    ['key' => 'monto_pago_final', 'label' => 'Monto recibido ahora. Saldo pendiente: Bs ' . number_format($realBalance, 2, '.', ',')],
                    ['key' => 'metodo_pago_final', 'label' => 'Metodo de pago final: efectivo, qr o transferencia_bancaria'],
                    ['key' => 'obs_res', 'label' => 'Observacion', 'optional' => true],
                ];
            }

            $actions[] = [
                'label' => 'Marcar como recogido y registrar venta',
                'action' => $action,
                'method' => 'put',
                'data' => ['reservation_action' => 'complete'],
                'promptFields' => $promptFields,
                'confirm' => 'La reserva se marcara como recogida, se convertira en venta y se reducira el stock reservado. Continuar?',
                'kind' => 'primary',
            ];
        }

        $hasCompletedInconsistency = in_array(($record->est_res ?? null), ['completada', 'recogida'], true)
            && (
                $realBalance > 0
                || ($record->est_pago_res ?? null) !== 'pagado_total'
                || !$this->reservations->saleForReservation($record)
            );

        if ($hasCompletedInconsistency) {
            $promptFields = [];

            if ($realBalance > 0) {
                $promptFields = [
                    ['key' => 'monto_pago_final', 'label' => 'Monto recibido ahora. Saldo pendiente: Bs ' . number_format($realBalance, 2, '.', ',')],
                    ['key' => 'metodo_pago_final', 'label' => 'Metodo de pago final: efectivo, qr o transferencia_bancaria'],
                    ['key' => 'obs_res', 'label' => 'Observacion', 'optional' => true],
                ];
            }

            $actions[] = [
                'label' => $realBalance > 0
                    ? 'Registrar pago restante y generar venta'
                    : 'Generar venta faltante',
                'action' => $action,
                'method' => 'put',
                'data' => ['reservation_action' => 'settle_completed'],
                'promptFields' => $promptFields,
                'confirm' => 'Se corregira la reserva recogida para dejar pago completo y venta registrada. Continuar?',
                'kind' => 'primary',
            ];
        }

        if (in_array(($record->est_res ?? null), ['por_procesar', 'confirmada', 'lista_recojo'], true)) {
            $actions[] = [
                'label' => 'Marcar vencida',
                'action' => $action,
                'method' => 'put',
                'data' => ['reservation_action' => 'expire'],
                'promptFields' => [
                    ['key' => 'obs_res', 'label' => 'Observación', 'optional' => true],
                ],
                'confirm' => 'La reserva se marcará como vencida y el stock reservado volverá a disponible. Continuar?',
                'kind' => 'secondary',
            ];
            $actions[] = [
                'label' => 'Cancelar reserva',
                'action' => $action,
                'method' => 'put',
                'data' => ['reservation_action' => 'cancel'],
                'promptFields' => [
                    ['key' => 'obs_res', 'label' => 'Observación', 'optional' => true],
                ],
                'confirm' => 'La reserva se cancelará y el stock reservado volverá a disponible. Continuar?',
                'kind' => 'danger',
            ];
        }

        return $actions;
    }

    private function saleRecordActions(array $definition, mixed $record): array
    {
        $action = $this->definitions->showHref($definition, $this->definitions->recordKey($record));
        $actions = [];

        if (in_array(($record->est_ven ?? null), ['en_revision', 'incompleta'], true)) {
            $actions[] = [
                'label' => 'Confirmar venta',
                'action' => $action,
                'method' => 'put',
                'data' => ['sale_action' => 'confirm_online'],
                'promptFields' => [
                    ['key' => 'monto_verificado_ven', 'label' => 'Monto verificado'],
                    ['key' => 'obs_vpg', 'label' => 'Observacion', 'optional' => true],
                ],
                'kind' => 'primary',
            ];
            $actions[] = [
                'label' => 'Marcar incompleta',
                'action' => $action,
                'method' => 'put',
                'data' => ['sale_action' => 'mark_incomplete'],
                'promptFields' => [
                    ['key' => 'monto_verificado_ven', 'label' => 'Monto verificado'],
                    ['key' => 'obs_vpg', 'label' => 'Observacion', 'optional' => true],
                ],
                'kind' => 'secondary',
            ];
            $actions[] = [
                'label' => 'Cancelar venta',
                'action' => $action,
                'method' => 'put',
                'data' => ['sale_action' => 'cancel_online'],
                'promptFields' => [
                    ['key' => 'obs_vpg', 'label' => 'Observacion', 'optional' => true],
                ],
                'confirm' => 'La venta se cancelara y no descontara inventario. Continuar?',
                'kind' => 'danger',
            ];
        }

        if (($record->est_ven ?? null) === 'registrada' && in_array(($record->est_logistico_ven ?? null), ['pendiente_traslado', 'pendiente_preparacion'], true)) {
            $actions[] = [
                'label' => 'Marcar como lista para recojo',
                'action' => route('superadmin.sales.pickup.ready', ['venta' => $record->id_venta]),
                'method' => 'post',
                'kind' => 'primary',
            ];
        }

        if (($record->est_ven ?? null) === 'registrada' && ($record->est_logistico_ven ?? null) === 'lista_recojo') {
            $actions[] = [
                'label' => 'Marcar como entregada',
                'action' => route('superadmin.sales.pickup.deliver', ['venta' => $record->id_venta]),
                'method' => 'post',
                'confirm' => 'La venta se marcara como entregada. No se modificara inventario. Continuar?',
                'kind' => 'primary',
            ];
        }

        return $actions;
    }

    private function reservationPaymentForm(array $definition, mixed $record): ?array
    {
        if ($this->definitions->definitionKey($definition) !== 'comercial.reservas') {
            return null;
        }

        return [
            'action' => $this->definitions->showHref($definition, $this->definitions->recordKey($record)),
            'canAdd' => in_array(($record->est_res ?? null), ['confirmada', 'lista_recojo'], true)
                && max(0, round((float) ($record->tot_res ?? 0) - (float) ($record->monto_pagado_res ?? 0), 2)) > 0,
            'saldo' => 'Bs ' . number_format(max(0, round((float) ($record->tot_res ?? 0) - (float) ($record->monto_pagado_res ?? 0), 2)), 2, '.', ','),
            'methods' => [
                ['label' => 'Efectivo', 'value' => 'efectivo'],
                ['label' => 'QR', 'value' => 'qr'],
                ['label' => 'Transferencia bancaria', 'value' => 'transferencia_bancaria'],
            ],
        ];
    }

    private function receiptPanel(array $definition, mixed $record): ?array
    {
        $definitionKey = $this->definitions->definitionKey($definition);

        if ($definitionKey === 'comercial.ventas') {
            if ($this->receipts->canIssueSaleReceipt($record)) {
                return [
                    'title' => 'Recibo emitido por Mati Boutique',
                    'description' => 'Este documento se habilita despues de confirmar el pago de la venta.',
                    'links' => [[
                        'label' => 'Ver recibo de venta',
                        'href' => route('superadmin.receipts.sales.show', ['venta' => $record->id_venta]),
                    ]],
                    'notice' => null,
                ];
            }

            if (($record->est_ven ?? null) === 'en_revision') {
                return [
                    'title' => 'Recibo emitido por Mati Boutique',
                    'description' => 'El recibo estara disponible cuando el pago sea confirmado.',
                    'links' => [],
                    'notice' => 'El recibo estara disponible cuando el pago sea confirmado.',
                ];
            }

            return null;
        }

        if ($definitionKey !== 'comercial.reservas') {
            return null;
        }

        $links = [];

        if ($this->receipts->canIssueReservationReceipt($record)) {
            $links[] = [
                'label' => 'Ver recibo de reserva',
                'href' => route('superadmin.receipts.reservations.show', ['reserva' => $record->id_reserva]),
            ];
        }

        $sale = $this->receipts->saleForReservation($record);

        if ($sale && $this->receipts->canIssueSaleReceipt($sale)) {
            $links[] = [
                'label' => 'Ver recibo de venta',
                'href' => route('superadmin.receipts.sales.show', ['venta' => $sale->id_venta]),
            ];
        }

        if ($links === []) {
            return null;
        }

        return [
            'title' => 'Recibos emitidos por Mati Boutique',
            'description' => 'Estos documentos son generados por el sistema despues de la verificacion administrativa del pago.',
            'links' => $links,
            'notice' => null,
        ];
    }

    private function prepareSizeRequest(Request $request, array $definition): void
    {
        if ($this->definitions->definitionKey($definition) !== 'catalogo.tallas') {
            return;
        }

        $metadata = $this->writer->sizeMetadataFor((string) $request->input('nom_tal'));
        $segment = $request->input('seg_tal') ?: 'ADULTO';
        $system = $request->input('sis_tal') ?: $metadata['sis_tal'];
        $group = $request->input('gru_tal') ?: $metadata['gru_tal'];

        $request->merge([
            'seg_tal' => $segment,
            'sis_tal' => $system,
            'gru_tal' => $group,
            'ord_tal' => $this->writer->calculateSizeOrder((string) $request->input('nom_tal'), (string) $system, (string) $segment),
        ]);
    }

    private function prepareColorRequest(Request $request, array $definition, mixed $record = null): void
    {
        if ($this->definitions->definitionKey($definition) !== 'catalogo.colores') {
            return;
        }

        $metadata = $this->writer->colorMetadataFor(
            (string) $request->input('hex_col'),
            $request->input('fam_col') ? (string) $request->input('fam_col') : null,
            $record,
        );

        $request->merge([
            'nom_col' => $metadata['nom_col'],
            'fam_col' => $metadata['fam_col'],
            'hex_col' => $metadata['hex_col'],
            'hex_dom_col' => $metadata['hex_dom_col'],
            'ord_col' => $metadata['ord_col'],
        ]);
    }

    private function deleteActionFor(array $definition, mixed $record): bool
    {
        if ($this->definitions->definitionKey($definition) === 'seguridad.usuarios') {
            return false;
        }

        if ($this->definitions->definitionKey($definition) === 'seguridad.roles' && $record instanceof Role && $record->isSystemRole()) {
            return false;
        }

        if ($this->definitions->definitionKey($definition) === 'inventario.stock') {
            return false;
        }

        return true;
    }
}
