<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Adjustment extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'nama_margin',
        'kode_margin',
        'nama_loss',
        'kode_loss'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function fixedAssets()
    {
        return $this->hasMany(FixedAssets::class, 'id_kode_adjustment');
    }
}
