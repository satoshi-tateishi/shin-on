<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\LocationResource;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LocationController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Location::query()->active()->ordered();

        if ($request->filled('type')) {
            $query->byType($request->string('type')->toString());
        }

        return LocationResource::collection($query->get());
    }

    public function show(Location $location): LocationResource|JsonResponse
    {
        if (! $location->is_active) {
            return response()->json(['message' => 'Not found.'], 404);
        }

        return new LocationResource($location);
    }
}
