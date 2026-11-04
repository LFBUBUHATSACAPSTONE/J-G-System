<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\Packages\PackageEquipmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PackageController extends Controller
{
    public function __construct(private readonly PackageEquipmentService $equipment) {}

    public function index(): JsonResponse
    {
        $packages = Package::query()->with('equipment')->orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (Package $package) => $package->toAdminArray());

        return response()->json(['data' => $packages]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateJson($request, [
            'name' => ['required', 'string', 'max:100', Rule::unique('packages', 'name')],
            'price' => ['required', 'integer', 'min:1', 'max:10000000'],
            'features' => ['required', 'string', 'max:2000', $this->hasFeatures()],
            ...$this->equipment->rules(),
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $validated['features'] = $this->normalizeFeatures($validated['features']);
        $equipment = $this->equipmentFrom($request);
        unset($validated['equipment']);

        $package = DB::transaction(function () use ($validated, $equipment): Package {
            $package = Package::query()->create([
                ...$validated,
                'id' => $this->uniqueId($validated['name']),
                'available' => true,
                'sort_order' => (Package::query()->max('sort_order') ?? -1) + 1,
            ]);

            // The equipment of the modal is saved with the package, so the landing page's
            // comparison table shows what the admin typed.
            $this->equipment->sync($package, $equipment);

            return $package;
        });

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
            ...$this->equipment->rules(),
        ]);
        if ($validated instanceof JsonResponse) {
            return $validated;
        }

        $validated['features'] = $this->normalizeFeatures($validated['features']);
        unset($validated['equipment']);

        DB::transaction(function () use ($record, $validated, $request): void {
            $record->update([
                ...$validated,
            ]);

            // Only when the request carries equipment: a request without it leaves the saved
            // equipment alone instead of clearing it.
            if ($request->has('equipment')) {
                $this->equipment->sync($record, $this->equipmentFrom($request));
            }
        });

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

    /**
     * The id of a new package: the name as a slug, with a number behind it when that one is
     * already taken.
     */
    private function uniqueId(string $name): string
    {
        $id = substr(Str::slug($name) ?: 'package', 0, 120);
        $baseId = $id;
        $suffix = 2;

        while (Package::query()->whereKey($id)->exists()) {
            $id = "{$baseId}-{$suffix}";
            $suffix++;
        }

        return $id;
    }

    /**
     * The equipment the request carries: row_key => text. An empty map when the request has
     * none (a new package starts without equipment).
     *
     * @return array<string, mixed>
     */
    private function equipmentFrom(Request $request): array
    {
        $values = $request->input('equipment');

        return is_array($values) ? $values : [];
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
