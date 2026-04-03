<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBusinessSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isBusiness()) {
            return response()->json([
                'message' => 'Business subscription required to access this feature.',
            ], 403);
        }

        return $next($request);
    }
}
