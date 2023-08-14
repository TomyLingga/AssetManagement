<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;


class FixedAssets extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_sub_grup',
        'nama',
        'brand',
        'kode_aktiva',
        'kode_penyusutan',
        'nomor',
        'masa_manfaat',
        'tgl_perolehan',
        'nilai_perolehan',
        'nilai_depresiasi_awal',
        'id_lokasi',
        'id_departemen',
        'id_pic',
        'cost_centre',
        'kondisi',
        'id_supplier',
        'id_kode_adjustment',
        'spesifikasi',
        'keterangan',
        'status',
    ];

    // protected $hidden = ['created_at', 'updated_at'];

    // Format kode aktiva (grup,sub,aset)
    public function getFormatedKodeAktivaAttribute()
    {
        $groupKodeAktivaTetap = $this->subGroup->group->kode_aktiva_tetap;
        $subGroupKodeAktivaTetap = $this->subGroup->kode_aktiva_tetap;
        $fixedAssetKodeAktiva = $this->attributes['kode_aktiva'];

        return $groupKodeAktivaTetap . $subGroupKodeAktivaTetap . $fixedAssetKodeAktiva;
    }

    // Format kode penyusutan (grup,sub,aset)
    public function getFormatedKodePenyusutanAttribute()
    {
        $groupKodeAkmPenyusutan = $this->subGroup->group->kode_akm_penyusutan;
        $subGroupKodeAkmPenyusutan = $this->subGroup->kode_akm_penyusutan;
        $fixedAssetKodeAkmPenyusutan = $this->attributes['kode_penyusutan'];

        return $groupKodeAkmPenyusutan . $subGroupKodeAkmPenyusutan . $fixedAssetKodeAkmPenyusutan;
    }

    public function subGroup()
    {
        return $this->belongsTo(SubGroup::class, 'id_sub_grup');
    }

    public function location()
    {
        return $this->belongsTo(Location::class, 'id_lokasi');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'id_supplier');
    }

    public function adjustment()
    {
        return $this->belongsTo(Adjustment::class, 'id_kode_adjustment');
    }

    public function fairValues()
    {
        return $this->hasMany(FairValue::class, 'id_fixed_asset');
    }

    public function valueInUses()
    {
        return $this->hasMany(ValueInUse::class, 'id_fixed_asset');
    }
}
