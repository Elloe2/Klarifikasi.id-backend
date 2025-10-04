<?php

namespace App\Http\Controllers;

use App\Services\GoogleSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class SearchController extends Controller
{
    public function __construct(private readonly GoogleSearchService $service)
    {
    }

    public function search(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'query' => ['required', 'string', 'min:3', 'max:255'],
            ]);
        } catch (ValidationException $exception) {
            return response()->json([
                'message' => 'Invalid query.',
                'errors' => $exception->errors(),
            ], 422);
        }

        try {
            $items = $this->service->search($validated['query']);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'query' => $validated['query'],
            'results' => $items,
        ]);
    }
}
