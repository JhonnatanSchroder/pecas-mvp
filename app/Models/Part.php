<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\PartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $company_id
 * @property string $name
 * @property string $phone_brand
 * @property string $phone_model
 * @property string|null $sku
 * @property int $price_cents
 * @property int $stock_quantity
 * @property array<int, string>|null $aliases
 * @property bool $is_active
 */
class Part extends Model
{
    /** @use HasFactory<PartFactory> */
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'phone_brand',
        'phone_model',
        'sku',
        'price_cents',
        'stock_quantity',
        'aliases',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'is_active' => 'boolean',
            'price_cents' => 'integer',
            'stock_quantity' => 'integer',
        ];
    }
}
