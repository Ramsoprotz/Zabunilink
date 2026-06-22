<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\TenderIngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenderIngestController extends Controller
{
    public function bulk(Request $request, TenderIngestService $service): JsonResponse
    {
        $validated = $request->validate([
            'source'                       => 'required|string|max:50',
            'notify'                       => 'sometimes|boolean',
            'tenders'                      => 'required|array|min:1|max:500',
            'tenders.*.title'              => 'required|string',
            'tenders.*.reference_number'   => 'required|string|max:255',
            'tenders.*.external_id'        => 'sometimes|nullable|string|max:255',
            'tenders.*.organization'       => 'required|string|max:255',
            'tenders.*.description'        => 'sometimes|nullable|string',
            'tenders.*.category_name'      => 'sometimes|nullable|string|max:255',
            'tenders.*.location_name'      => 'sometimes|nullable|string|max:255',
            'tenders.*.type'               => 'sometimes|in:government,private',
            'tenders.*.value'              => 'sometimes|nullable|numeric|min:0',
            'tenders.*.deadline'           => 'sometimes|nullable|date',
            'tenders.*.published_date'     => 'sometimes|nullable|date',
            'tenders.*.status'             => 'sometimes|in:open,closed,awarded',
            'tenders.*.requirements'       => 'sometimes|nullable|string',
            'tenders.*.documents_url'      => 'sometimes|nullable|string|max:1000',
            'tenders.*.contact_info'       => 'sometimes|nullable|string|max:1000',
        ]);

        $source = $validated['source'];
        $notify = $validated['notify'] ?? false;

        $results = [
            'source'  => $source,
            'notify'  => $notify,
            'totals'  => ['created' => 0, 'updated' => 0, 'errors' => 0],
            'created' => [],
            'updated' => [],
            'errors'  => [],
        ];

        foreach ($validated['tenders'] as $index => $row) {
            try {
                $row['source'] = $source;
                $result = $service->ingest($row, $notify);

                $entry = [
                    'index'      => $index,
                    'reference'  => $row['reference_number'],
                    'tender_id'  => $result['tender_id'],
                    'warnings'   => $result['warnings'],
                ];

                $results[$result['status']][] = $entry;
                $results['totals'][$result['status']]++;
            } catch (\Throwable $e) {
                $results['errors'][] = [
                    'index'     => $index,
                    'reference' => $row['reference_number'] ?? null,
                    'reason'    => $e->getMessage(),
                ];
                $results['totals']['errors']++;
            }
        }

        return response()->json($results);
    }
}
