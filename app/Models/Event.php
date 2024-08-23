<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;


class Event extends Model
{
    protected $table = 'event';
    protected $primaryKey = 'event_id';
    public $incrementing = false; // Id no autoincremental debido a UUID
    protected $keyType = 'string';
    protected $fillable = [
        'event_id',
        'event_name',
        'organizer_name',
        'description',
        'description_details',
        'event_date',
        'location',
        'ticket_price'
    ];

    protected static function boot()
    {
        parent::boot();

        // Generar automaticamente UUID al generar nuevo evento
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}