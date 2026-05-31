<?php

namespace RiseTechApps\OrchestratorLink\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $table = 'orchestrator_shipments';

    protected $fillable = [
        'me_order_id',
        'me_protocol',
        'service_id',
        'service_name',
        'status',
        'tracking_code',
        'from_postal_code',
        'from_data',
        'to_postal_code',
        'to_data',
        'products',
        'volumes',
        'options',
        'price',
        'label_url',
        'posted_at',
        'delivered_at',
        'canceled_at',
    ];

    protected $casts = [
        'from_data'    => 'array',
        'to_data'      => 'array',
        'products'     => 'array',
        'volumes'      => 'array',
        'options'      => 'array',
        'price'        => 'decimal:2',
        'posted_at'    => 'datetime',
        'delivered_at' => 'datetime',
        'canceled_at'  => 'datetime',
    ];

    public function isPending(): bool    { return $this->status === 'pending'; }
    public function isPosted(): bool     { return $this->status === 'posted'; }
    public function isDelivered(): bool  { return $this->status === 'delivered'; }
    public function isCanceled(): bool   { return $this->status === 'canceled'; }

    public function scopePending($query)   { return $query->where('status', 'pending'); }
    public function scopePosted($query)    { return $query->where('status', 'posted'); }
    public function scopeDelivered($query) { return $query->where('status', 'delivered'); }
}
