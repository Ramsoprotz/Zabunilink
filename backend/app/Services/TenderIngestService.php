<?php

namespace App\Services;

use App\Jobs\SendTenderNotifications;
use App\Models\Category;
use App\Models\Location;
use App\Models\Tender;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenderIngestService
{
    private const TITLE_MAX = 255;

    private array $mappings;
    private ?array $categoryCache = null;
    private ?array $locationCache = null;

    public function __construct()
    {
        $this->mappings = config('tender_mappings');
    }

    /**
     * Ingest a single row. Returns:
     *   ['status' => 'created'|'updated', 'tender_id' => int, 'warnings' => string[]]
     * Throws on validation/resolution failure (caller catches and records as error).
     */
    public function ingest(array $row, bool $notify = false): array
    {
        $warnings = [];

        $source     = $row['source'];
        $externalId = $row['external_id'] ?? $row['reference_number'];

        [$title, $description] = $this->normalizeTitleAndDescription(
            $row['title'],
            $row['description'] ?? null,
            $warnings,
        );

        $publishedDate = isset($row['published_date'])
            ? Carbon::parse($row['published_date'])
            : Carbon::now();

        $deadline = isset($row['deadline']) && $row['deadline']
            ? Carbon::parse($row['deadline'])
            : $publishedDate->copy()->addDays(
                $this->mappings['defaults']['deadline_days_from_publish']
            );

        if (! isset($row['deadline']) || ! $row['deadline']) {
            $warnings[] = 'deadline missing — defaulted to published_date + '
                . $this->mappings['defaults']['deadline_days_from_publish'] . ' days';
        }

        $categoryId = $this->resolveCategory($row['category_name'] ?? null, $warnings);
        $locationId = $this->resolveLocation(
            $row['location_name'] ?? null,
            $row['organization'] ?? null,
            $warnings,
        );

        $payload = [
            'title'            => $title,
            'reference_number' => $row['reference_number'],
            'description'      => $description,
            'organization'     => $row['organization'],
            'category_id'      => $categoryId,
            'location_id'      => $locationId,
            'type'             => $row['type']   ?? $this->mappings['defaults']['type'],
            'status'           => $row['status'] ?? $this->mappings['defaults']['status'],
            'value'            => $row['value']  ?? null,
            'deadline'         => $deadline,
            'published_date'   => $publishedDate,
            'requirements'     => $row['requirements'] ?? null,
            'documents_url'    => $row['documents_url'] ?? $this->mappings['defaults']['documents_url'],
            'contact_info'     => $row['contact_info']  ?? null,
            'source'           => $source,
            'external_id'      => $externalId,
            'imported_at'      => now(),
            'is_published'     => true,
        ];

        return DB::transaction(function () use ($source, $externalId, $payload, $notify, $warnings) {
            $existing = Tender::where('source', $source)
                ->where('external_id', $externalId)
                ->first();

            if ($existing) {
                $existing->update($payload);
                return [
                    'status'    => 'updated',
                    'tender_id' => $existing->id,
                    'warnings'  => $warnings,
                ];
            }

            $tender = Tender::create($payload);

            if ($notify) {
                SendTenderNotifications::dispatch($tender);
            }

            return [
                'status'    => 'created',
                'tender_id' => $tender->id,
                'warnings'  => $warnings,
            ];
        });
    }

    private function normalizeTitleAndDescription(string $title, ?string $description, array &$warnings): array
    {
        $title       = trim($title);
        $description = $description !== null ? trim($description) : '';

        if (Str::length($title) <= self::TITLE_MAX) {
            return [$title, $description ?: $title];
        }

        $short = Str::limit($title, self::TITLE_MAX - 1, '…');
        $warnings[] = 'title exceeded 255 chars — truncated; full text prepended to description';

        $fullPrefix = $title . "\n\n";
        $description = $fullPrefix . $description;

        return [$short, $description];
    }

    private function resolveCategory(?string $name, array &$warnings): int
    {
        $resolvedName = $this->resolveName(
            $name,
            $this->mappings['categories'],
            $this->categoryCache(),
        );

        if ($resolvedName === null) {
            $default = $this->mappings['defaults']['category'];
            $warnings[] = $name
                ? "category '{$name}' not resolved — defaulted to '{$default}'"
                : "category missing — defaulted to '{$default}'";
            $resolvedName = $default;
        }

        $id = $this->categoryCache()[Str::lower($resolvedName)] ?? null;

        if ($id === null) {
            throw new \RuntimeException("Default category '{$resolvedName}' not found in DB");
        }

        return $id;
    }

    private function resolveLocation(?string $name, ?string $organization, array &$warnings): int
    {
        $resolvedName = $this->resolveName(
            $name,
            $this->mappings['locations'],
            $this->locationCache(),
        );

        if ($resolvedName === null && $organization) {
            $resolvedName = $this->extractLocationFromOrganization($organization);
            if ($resolvedName !== null) {
                $warnings[] = "location resolved from organization name → '{$resolvedName}'";
            }
        }

        if ($resolvedName === null) {
            $default = $this->mappings['defaults']['location'];
            $warnings[] = $name
                ? "location '{$name}' not resolved — defaulted to '{$default}'"
                : "location missing — defaulted to '{$default}'";
            $resolvedName = $default;
        }

        $id = $this->locationCache()[Str::lower($resolvedName)] ?? null;

        if ($id === null) {
            throw new \RuntimeException("Default location '{$resolvedName}' not found in DB");
        }

        return $id;
    }

    /**
     * Resolve a raw external name to an existing DB record name.
     * 1) Synonym table (exact lowercase key)
     * 2) Exact lowercase match in DB
     * 3) Substring match in DB ("dar es salaam" matches "Dar es Salaam Region")
     */
    private function resolveName(?string $raw, array $synonyms, array $dbIndex): ?string
    {
        if (! $raw) {
            return null;
        }

        $key = Str::lower(trim($raw));

        if (isset($synonyms[$key])) {
            return $synonyms[$key];
        }

        if (isset($dbIndex[$key])) {
            return $key;
        }

        foreach ($dbIndex as $dbKey => $_id) {
            if (str_contains($key, $dbKey) || str_contains($dbKey, $key)) {
                return $dbKey;
            }
        }

        return null;
    }

    private function extractLocationFromOrganization(string $organization): ?string
    {
        $org = Str::lower($organization);

        foreach ($this->mappings['locations'] as $synonymKey => $mapped) {
            if (str_contains($org, $synonymKey)) {
                return $mapped;
            }
        }

        foreach ($this->locationCache() as $name => $_id) {
            if (str_contains($org, $name)) {
                return $name;
            }
        }

        return null;
    }

    private function categoryCache(): array
    {
        if ($this->categoryCache === null) {
            $this->categoryCache = Category::query()
                ->where('is_active', true)
                ->pluck('id', 'name')
                ->mapWithKeys(fn ($id, $name) => [Str::lower($name) => $id])
                ->all();
        }

        return $this->categoryCache;
    }

    private function locationCache(): array
    {
        if ($this->locationCache === null) {
            $this->locationCache = Location::query()
                ->where('is_active', true)
                ->pluck('id', 'name')
                ->mapWithKeys(fn ($id, $name) => [Str::lower($name) => $id])
                ->all();
        }

        return $this->locationCache;
    }
}
