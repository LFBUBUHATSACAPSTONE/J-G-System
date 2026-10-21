<?php

namespace App\Services;

use App\Models\Package;
use Illuminate\Support\Facades\DB;

/**
 * The equipment of a package: the lines of config/package-equipment.php and the values the
 * admin types for them in Admin > Packages.
 *
 * The values live in the package_equipment table (one row per package and line). The admin
 * modal sends them as one `equipment` object (row_key => text); the landing page's Package
 * Comparison Table reads them back, so both sides always show the same equipment.
 */
class PackageEquipmentService
{
    /**
     * The equipment lines the config knows, in the order the modal shows them.
     *
     * @return array<int, string>
     */
    public function rowKeys(): array
    {
        $keys = [];

        foreach (config('package-equipment.sections', []) as $section) {
            foreach (array_keys($section['rows'] ?? []) as $rowKey) {
                $keys[] = (string) $rowKey;
            }
        }

        return $keys;
    }

    /**
     * The validation rules for the `equipment` field of the admin modal.
     *
     * `equipment` is an object of row_key => text. Every line is checked on its own, so the
     * error of a line comes back under `equipment.<row_key>` and the modal can print it under
     * that line (see resources/js/admin/packages.js).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxLength = (int) config('package-equipment.max_length', 60);
        $blankWords = array_map('mb_strtolower', (array) config('package-equipment.blank_words', []));

        return [
            'equipment' => ['sometimes', 'nullable', 'array'],
            'equipment.*' => [
                function (string $attribute, mixed $value, \Closure $fail) use ($maxLength, $blankWords): void {
                    if (! is_scalar($value)) {
                        $fail('Enter a value for this equipment row.');

                        return;
                    }

                    $text = trim((string) $value);

                    if (mb_strlen($text) > $maxLength) {
                        $fail("Use {$maxLength} characters or fewer.");

                        return;
                    }

                    // "No", "None", ... would be printed as the word itself in the comparison
                    // table, so a line that is not included has to stay blank.
                    if ($text !== '' && in_array(mb_strtolower($text), $blankWords, true)) {
                        $fail("Leave this blank if it's not included.");
                    }
                },
            ],
        ];
    }

    /**
     * The values that will be saved: the known lines only, each one trimmed and with its
     * spaces collapsed. A blank line is dropped (it means "not included") and every spelling
     * of "yes" becomes "Yes", so the comparison table prints a check mark for it.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, string>
     */
    public function normalize(array $values): array
    {
        $known = array_flip($this->rowKeys());
        $yesWords = array_map('mb_strtolower', (array) config('package-equipment.yes_values', []));
        $normalized = [];

        foreach ($values as $rowKey => $value) {
            // A line the config does not know is not saved.
            if (! isset($known[$rowKey]) || ! is_scalar($value)) {
                continue;
            }

            $text = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
            if ($text === '') {
                continue;
            }

            if (in_array(mb_strtolower($text), $yesWords, true)) {
                $text = 'Yes';
            }

            $normalized[(string) $rowKey] = $text;
        }

        return $normalized;
    }

    /**
     * Save the equipment of a package: the lines that are in the map are written, the lines
     * that are not are removed (the admin cleared them). Done in one transaction, so a
     * package never ends up with half of its equipment.
     *
     * @param  array<string, mixed>  $values
     */
    public function sync(Package $package, array $values): void
    {
        $rows = $this->normalize($values);

        DB::transaction(function () use ($package, $rows): void {
            $package->equipment()->delete();

            foreach ($rows as $rowKey => $value) {
                $package->equipment()->create([
                    'row_key' => $rowKey,
                    'value' => $value,
                ]);
            }
        });

        // The relation on the model is stale now; drop it so the next read loads the new rows.
        $package->unsetRelation('equipment');
    }
}
