<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\InventoryMovementFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $company_id
 * @property int $part_id
 * @property int|null $user_id
 * @property string $type
 * @property int $quantity_delta
 * @property int $balance_after
 * @property string|null $reason
 */
class InventoryMovement extends Model
{
    /** @use HasFactory<InventoryMovementFactory> */
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'part_id',
        'user_id',
        'type',
        'quantity_delta',
        'balance_after',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'quantity_delta' => 'integer',
            'balance_after' => 'integer',
        ];
    }

    /** @return BelongsTo<Part, $this> */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
