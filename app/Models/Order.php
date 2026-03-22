<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'it_orders';

    protected $fillable = [
        'subject',
        'quantity',
        'price_gross',
        'order_date',
        'vendor_id',
        'cost_center_id',
        'account_code_id',
        'buyer_username',
        'status',
        'bemerkungen',
        'status_updated_at',
    ];

    protected $casts = [
        'price_gross'       => 'decimal:2',
        'order_date'        => 'date',
        'status'            => 'integer',
        'status_updated_at' => 'datetime',
    ];

    public const STATUS_LABELS = [
        1 => 'bestellt',
        2 => 'geliefert',
        3 => 'in Inventarisierung',
        4 => 'im Rechnungsworkflow',
        5 => 'Feststellung',
        6 => 'angeordnet',
    ];

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? 'Unbekannt';
    }

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 6);
    }

    public function vendor()
    {
        return $this->belongsTo(Dienstleister::class, 'vendor_id');
    }

    public function costCenter()
    {
        return $this->belongsTo(CostCenter::class, 'cost_center_id');
    }

    public function accountCode()
    {
        return $this->belongsTo(AccountCode::class, 'account_code_id');
    }

    public function history()
    {
        return $this->hasMany(OrderHistory::class, 'order_id');
    }
}
