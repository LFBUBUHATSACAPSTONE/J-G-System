<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function index(): JsonResponse
    {
        $packages = Package::query()->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (Package $package) => $package->toAdminArray());

        return response()->json(['data' => $packages]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateJson($request, [
            'name' => ['required', 'string', 'max:100', Rule::unique('packages', 'name')],
            'price' => ['required', 'integer', 'min:1', 'max:10000000'],
            'features' => ['required', 'string', 'max:2000', $this->hasFeatures()],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $validated['features'] = $this->normalizeFeatures($validated['features']);
        $id = substr(Str::slug($validated['name']) ?: 'package', 0, 120);
        $baseId = $id;
        $suffix = 2;
        while (Package::query()->whereKey($id)->exists()) {
            $id = "{$baseId}-{$suffix}";
            $suffix++;
        }

        $package = Package::query()->create([
            ...$validated,
            'id' => $id,
            'available' => true,
            'sort_order' => (Package::query()->max('sort_order') ?? -1) + 1,
        ]);

        return response()->json([
            'message' => 'Package created successfully.',
            'data' => $package->toAdminArray(),
        ], 201);
    }

    public function update(Request $request, string $package): JsonResponse
    {
        $record = Package::query()->find($package);
        if (! $record) {
            return response()->json(['message' => 'Package not found.'], 404);
        }

        $validated = $this->validateJson($request, [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('packages', 'name')->ignore($record->id, 'id'),
            ],
            'price' => ['required', 'integer', 'min:1', 'max:10000000'],
            'features' => ['required', 'string', 'max:2000', $this->hasFeatures()],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $validated['features'] = $this->normalizeFeatures($validated['features']);
        $record->update([
            ...$validated,
        ]);

        return response()->json([
            'message' => 'Package updated successfully.',
            'data' => $record->fresh()->toAdminArray(),
        ]);
    }

    public function updateAvailability(Request $request, string $package): JsonResponse
    {
        $record = Package::query()->find($package);
        if (! $record) {
            return response()->json(['message' => 'Package not found.'], 404);
        }

        $validated = $this->validateJson($request, [
            'availability' => ['required', Rule::in(array_keys(config('admin.packages.availability')))],
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $record->update(['available' => $validated['availability'] === 'available']);

        return response()->json([
            'message' => "Package {$record->name} is now {$validated['availability']}.",
            'data' => $record->fresh()->toAdminArray(),
        ]);
    }

    private function normalizeFeatures(string $features): array
    {
        return collect(preg_split('/\R/u', $features) ?: [])
            ->map(fn (string $feature) => preg_replace('/^[\s•*-]+/u', '', trim($feature)))
            ->map(fn (?string $feature) => trim($feature ?? ''))
            ->filter()
            ->values()
            ->all();
    }

    private function hasFeatures(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! is_string($value) || ! $this->normalizeFeatures($value)) {
                $fail('Add at least one feature (one per line).');
            }
        };
    }

    private function validateJson(Request $request, array $rules): array|JsonResponse
    {
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        return $validator->validated();
    }
}
