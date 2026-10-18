<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Models\Banner;
use App\Models\Blog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Childcategory;
use App\Models\CreatePage;
use App\Models\KittenPack;
use App\Models\Product;
use App\Models\Productimage;
use App\Models\Subcategory;
use App\Observers\ProductObserver;
use App\Observers\SearchIndexObserver;
use App\Observers\SitemapObserver;
use App\Observers\WebpVariantObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Product::observe(ProductObserver::class);

        // Keep storefront search in step with catalog text changes.
        foreach ([Product::class, Category::class, Subcategory::class, Childcategory::class, Brand::class, KittenPack::class] as $model) {
            $model::observe(SearchIndexObserver::class);
        }

        // Resized .webp copy of every newly uploaded storefront image.
        foreach ([Productimage::class, Banner::class, Category::class, Subcategory::class, Blog::class] as $model) {
            $model::observe(WebpVariantObserver::class);
        }

        // Rebuild sitemap.xml (after the response) whenever a URL in it can appear, change or disappear.
        foreach ([Product::class, Category::class, Subcategory::class, Brand::class, Blog::class, CreatePage::class] as $model) {
            $model::observe(SitemapObserver::class);
        }
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * @return bool
     */
    public function shouldDiscoverEvents()
    {
        return false;
    }
}
