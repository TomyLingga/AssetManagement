<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class FotoFixedAsset extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $table = 'foto_fixed_aset';

    protected $fillable = [
        'id_fixed_asset',
        'nama_file'
    ];

    public function fixedAsset()
    {
        return $this->belongsTo(FixedAssets::class, 'id_fixed_asset');
    }
}
