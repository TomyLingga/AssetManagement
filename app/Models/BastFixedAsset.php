<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class BastFixedAsset extends Model
{
    use HasFactory, Notifiable;

    protected $table = 'bast_fixed_assets';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_fixed_asset',
        'tgl_serah',
        'nomor_serah',
        'id_user',
        'id_pic',
        'id_checker',
        'ttd_terima',
        'ttd_checker',
        'status',
    ];

    public function fixedAsset()
    {
        return $this->belongsTo(FixedAssets::class, 'id_fixed_asset');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
