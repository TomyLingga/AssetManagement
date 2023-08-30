<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'nama',
        'kode_aktiva_tetap',
        'kode_akm_penyusutan',
        'format'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function subGroups()
    {
        return $this->hasMany(SubGroup::class, 'id_grup');
    }
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
