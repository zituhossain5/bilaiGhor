<?php

namespace Tests\Unit;

use App\Http\Middleware\NormalizeAdminDateInputs;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class NormalizeAdminDateInputsTest extends TestCase
{
    public function test_it_converts_admin_day_first_dates_to_iso_dates(): void
    {
        $request = Request::create('/admin/expenses/store', 'POST', [
            'expense_date' => '10/09/2026',
            'from_date' => '01/09/2026',
            'to_date' => '31/12/2026',
        ]);

        (new NormalizeAdminDateInputs())->handle($request, fn () => new Response());

        $this->assertSame('2026-09-10', $request->input('expense_date'));
        $this->assertSame('2026-09-01', $request->input('from_date'));
        $this->assertSame('2026-12-31', $request->input('to_date'));
    }

    public function test_it_converts_admin_query_dates(): void
    {
        $request = Request::create('/admin/reports/orders', 'GET', [
            'from_date' => '25/12/2026',
        ]);

        (new NormalizeAdminDateInputs())->handle($request, fn () => new Response());

        $this->assertSame('2026-12-25', $request->query('from_date'));
        $this->assertSame('2026-12-25', $request->input('from_date'));
    }

    public function test_it_does_not_change_frontend_or_invalid_dates(): void
    {
        $frontend = Request::create('/checkout', 'POST', [
            'delivery_date' => '10/09/2026',
        ]);
        $invalid = Request::create('/admin/expenses/store', 'POST', [
            'expense_date' => '31/02/2026',
        ]);

        $middleware = new NormalizeAdminDateInputs();
        $middleware->handle($frontend, fn () => new Response());
        $middleware->handle($invalid, fn () => new Response());

        $this->assertSame('10/09/2026', $frontend->input('delivery_date'));
        $this->assertSame('31/02/2026', $invalid->input('expense_date'));
    }
}
