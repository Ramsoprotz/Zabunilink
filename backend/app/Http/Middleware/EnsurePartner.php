<?php

namespace App\Http\Middleware;

use App\Models\PartnerApiUsage;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsurePartner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, ['partner', 'admin'])) {
            return response()->json([
                'message' => 'Partner API access required.',
                'code'    => 'partner_required',
            ], 403);
        }

        if (! $user->tokenCan('tenders:read')) {
            return response()->json([
                'message' => 'This token does not have the tenders:read ability.',
                'code'    => 'insufficient_scope',
            ], 403);
        }

        try {
            PartnerApiUsage::record($user->id);
        } catch (\Throwable $e) {
            Log::warning('Partner API usage logging failed', ['error' => $e->getMessage()]);
        }

        return $next($request);
    }
}
