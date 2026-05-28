<?php

namespace App\Services;

use App\Models\ShopProduct;
use Illuminate\Database\Eloquent\Collection;

class ShopCatalogService
{
    /**
     * @return Collection<int, ShopProduct>
     */
    public function listActiveProducts(): Collection
    {
        return ShopProduct::query()
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
