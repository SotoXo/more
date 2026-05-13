<?php

namespace App\Services\Superadmin\Resources;

use App\Models\Role;
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
        abort_unless($this->definitions->supportsCrud($definition), 404);

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
            'submit' => $this->definitions->supportsCrud($definition)
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
        abort_unless($this->definitions->supportsCrud($definition), 404);

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
        abort_unless($this->definitions->supportsCrud($definition), 404);

        $resolvedRecord = $this->queries->resolveRecord($definition, $record);
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

        return true;
    }
}
