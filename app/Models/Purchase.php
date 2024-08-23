<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $table = 'purchase';
    protected $primaryKey = 'purchase_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'purchase_id',
        'client_name',
        'client_mail',
        'client_phone',
        'purchase_date'
    ];
}