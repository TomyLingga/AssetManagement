<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class FairValue extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_fixed_asset',
        'nilai',
    ];

    public function fixedAsset()
    {
        return $this->belongsTo(FixedAssets::class, 'id_fixed_asset');
    }
}
