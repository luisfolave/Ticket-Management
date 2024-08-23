<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Ticket extends Model
{
    protected $table = 'ticket';
    protected $primaryKey = 'ticket_id';
    public $incrementing = false; // Id no autoincremental debido a UUID
    protected $keyType = 'string';
    protected $fillable = [
        'ticket_id',
        'purchase_id',
        'event_id',
        'seat_number',
        'price',
        'ticket_type'
    ];
}