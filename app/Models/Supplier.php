<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'nama',
        'kode',
        'keterangan',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function fixedAssets()
    {
        return $this->hasMany(FixedAssets::class, 'id_supplier');
    }
}
