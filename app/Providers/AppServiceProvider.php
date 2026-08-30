<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Contact;
use App\Models\CreatePage;
use App\Models\GeneralSetting;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\SocialMedia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);

        View::composer('*', function ($view) {
            $view->with($this->sharedViewData());
        });

        View::composer('backEnd.layouts.master', function ($view) {
            $pendingOrders = $this->safeValue(
                fn () => Order::where('order_status', 1)
                    ->with('customer')
                    ->latest()
                    ->limit(10)
                    ->get(),
                collect()
            );

            $view->with([
                'neworder' => $this->safeValue(
                    fn () => Order::where('order_status', 1)->count(),
                    0
                ),
                'pendingorder' => $pendingOrders,
                'orderstatus' => $this->safeValue(
                    fn () => OrderStatus::orderBy('id')->get(),
                    collect()
                ),
            ]);
        });

        View::composer('backEnd.order.process', function ($view) {
            $view->with('orderstatus', $this->safeValue(
                fn () => OrderStatus::orderBy('id')->get(),
                collect()
            ));
        });
    }

    private function sharedViewData(): array
    {
        return [
            'generalsetting' => $this->safeValue(fn () => GeneralSetting::where('status', 1)->first() ?? GeneralSetting::first()),
            'contact' => $this->safeValue(fn () => Contact::where('status', 1)->first() ?? Contact::first()),
            'socialicons' => $this->safeValue(fn () => SocialMedia::where('status', 1)->get(), collect()),
            'pages' => $this->safeValue(fn () => CreatePage::where('status', 1)->where('placement', 'top')->get(), collect()),
            'pagesright' => $this->safeValue(fn () => CreatePage::where('status', 1)->where('placement', 'right')->get(), collect()),
            'menucategories' => $this->safeValue(fn () => Category::where('status', 1)
                ->where('parent_id', 0)
                ->with(['subcategories.childcategories'])
                ->displayOrdered()
                ->get(), collect()),
        ];
    }

    private function safeValue(callable $callback, mixed $default = null): mixed
    {
        try {
            return $callback();
        } catch (\Throwable $e) {
            return $default;
        }
    }
}
