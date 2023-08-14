<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_area',
        'nama',
        'keterangan'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function fixedAssets()
    {
        return $this->hasMany(FixedAssets::class, 'id_lokasi');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }
}
