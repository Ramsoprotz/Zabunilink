<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isPro()) {
            return response()->json([
                'message' => 'An active Pro subscription is required to perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
