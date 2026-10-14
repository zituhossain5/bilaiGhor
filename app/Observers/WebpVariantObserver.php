<?php

namespace App\Observers;

use App\Support\WebpVariant;
use Illuminate\Database\Eloquent\Model;

/**
 * Creates the resized .webp copy of a newly uploaded image (products, sliders/banners,
 * categories, subcategories, blogs) as soon as the model is saved with a new `image` path.
 * The database keeps the original path; <x-picture> serves the copy. A failure is only
 * reported, never thrown, so uploads keep working exactly as before.
 */
class WebpVariantObserver
{
    public function saved(Model $model): void
    {
        if ($model->wasRecentlyCreated || $model->wasChanged('image')) {
            WebpVariant::forUpload($model->getAttribute('image'));
        }
    }
}
