<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Services\Superadmin\Resources\ResourceControllerActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ResourceController extends Controller
{
    public function index(Request $request, string $section, string $resource)
    {
        return app(ResourceControllerActions::class)->index($request, $section, $resource);
    }

    public function create(Request $request, string $section, string $resource)
    {
        return app(ResourceControllerActions::class)->create($request, $section, $resource);
    }

    public function show(string $section, string $resource, string $record)
    {
        return app(ResourceControllerActions::class)->show($section, $resource, $record);
    }

    public function edit(Request $request, string $section, string $resource, string $record)
    {
        return app(ResourceControllerActions::class)->edit($request, $section, $resource, $record);
    }

    public function store(Request $request, string $section, string $resource): RedirectResponse
    {
        return app(ResourceControllerActions::class)->store($request, $section, $resource);
    }

    public function storeInline(Request $request, string $section, string $resource): JsonResponse
    {
        return app(ResourceControllerActions::class)->storeInline($request, $section, $resource);
    }

    public function update(Request $request, string $section, string $resource, string $record): RedirectResponse
    {
        return app(ResourceControllerActions::class)->update($request, $section, $resource, $record);
    }

    public function destroy(Request $request, string $section, string $resource, string $record): RedirectResponse
    {
        return app(ResourceControllerActions::class)->destroy($request, $section, $resource, $record);
    }
}
