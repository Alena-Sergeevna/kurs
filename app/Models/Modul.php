<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modul extends Model
{
    public $incrementing = false;
    protected $keyType = 'int';
    
    protected $fillable = ['id', 'name'];

    public function modulSubjects(): HasMany
    {
        return $this->hasMany(ModulSubject::class, 'id_module');
    }

    public function profCompetencies(): HasMany
    {
        return $this->hasMany(ProfCompetency::class, 'id_module');
    }
}
