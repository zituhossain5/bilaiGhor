<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeAdminDateInputs
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('admin', 'admin/*')) {
            $request->merge($this->normalizeDates($request->all()));
            $request->query->replace($this->normalizeDates($request->query->all()));
        }

        return $next($request);
    }

    private function normalizeDates(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->normalizeDates($value);
                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $trimmed = trim($value);
            if (! preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $trimmed)) {
                continue;
            }

            try {
                $date = Carbon::createFromFormat('d/m/Y', $trimmed);
            } catch (\Throwable) {
                continue;
            }

            if ($date && $date->format('d/m/Y') === $trimmed) {
                $data[$key] = $date->format('Y-m-d');
            }
        }

        return $data;
    }
}
