<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function scopeDisplayOrdered($query)
    {
        return $query
            ->orderByRaw('CASE WHEN sort_order IS NULL OR sort_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC');
    }

    public function childcategories() {
        return $this->hasMany(Childcategory::class, 'subcategory_id')->where('status', 1);
    }
    public function category() {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }
    
    public function menuchildcategories()
    {
        return $this->hasMany(Childcategory::class, 'subcategory_id')->select('id','slug','subcategory_id','childcategoryName')->where('status',1);
    }
    
    
}
