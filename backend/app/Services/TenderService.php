<?php

namespace App\Services;

use App\Jobs\SendTenderNotifications;
use App\Models\Tender;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TenderService
{
    /**
     * Get paginated tenders with filters.
     */
    public function getTenders(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Tender::with(['category', 'location', 'creator']);

        if (!empty($filters['category_id'])) {
            $query->byCategory($filters['category_id']);
        }

        if (!empty($filters['location_id'])) {
            $query->byLocation($filters['location_id']);
        }

        if (!empty($filters['type'])) {
            $query->byType($filters['type']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('organization', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['month'])) {
            $query->whereMonth('deadline', $filters['month']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Get a single tender with its relations.
     */
    public function getTender(int $id): Tender
    {
        return Tender::with(['category', 'location', 'creator', 'favorites', 'applications'])
            ->findOrFail($id);
    }

    /**
     * Create a new tender and dispatch notification job.
     */
    public function createTender(array $data): Tender
    {
        $tender = Tender::create($data);

        SendTenderNotifications::dispatch($tender);

        return $tender->load(['category', 'location']);
    }

    /**
     * Update an existing tender.
     */
    public function updateTender(Tender $tender, array $data): Tender
    {
        $tender->update($data);

        return $tender->fresh(['category', 'location']);
    }
}
