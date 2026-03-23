<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductionResource;
use App\Models\Production;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Production::query()->active()->ordered();

        if ($request->filled('type')) {
            $query->byType($request->string('type')->toString());
        }

        return ProductionResource::collection($query->get());
    }

    public function show(Production $production): ProductionResource|JsonResponse
    {
        if (! $production->is_active) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return new ProductionResource($production);
    }
}
