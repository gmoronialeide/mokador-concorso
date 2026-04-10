<?php

namespace App\Http\Middleware;

use App\Filament\Pages\FinalDraw;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectNotaioFromDashboard
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->routeIs('filament.admin.pages.dashboard')
            && auth('admin')->user()?->isNotaio()
        ) {
            return redirect(FinalDraw::getUrl(panel: 'admin'));
        }

        return $next($request);
    }
}
