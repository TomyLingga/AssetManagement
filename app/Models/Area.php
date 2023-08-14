<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'nama'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function locations()
    {
        return $this->hasMany(Location::class, 'id_area');
    }

}
