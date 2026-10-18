<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $company_id
 * @property int $order_id
 * @property int $part_id
 * @property int $quantity
 * @property int $unit_price_cents
 */
class OrderItem extends Model
{
    use BelongsToCompany;

    protected $fillable = ['order_id', 'part_id', 'quantity', 'unit_price_cents'];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'unit_price_cents' => 'integer'];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Part, $this> */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }
}
