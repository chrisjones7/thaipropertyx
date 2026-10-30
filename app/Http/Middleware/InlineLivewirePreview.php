<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InlineLivewirePreview
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (
            $request->routeIs('livewire.preview-file')
            && $response->headers->has('Content-Disposition')
        ) {
            $response->headers->set(
                'Content-Disposition',
                'inline'
            );
        }

        return $response;
    }
}