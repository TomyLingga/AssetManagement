<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class SubGroup extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_grup',
        'nama',
        'kode_aktiva_tetap',
        'kode_akm_penyusutan'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function fixedAssets()
    {
        return $this->hasMany(FixedAssets::class, 'id_sub_grup');
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'id_grup');
    }
}
