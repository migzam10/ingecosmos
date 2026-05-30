<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Secuencia extends Model
{
    protected $table = 'secuencias';
    protected $fillable = ['tipo', 'ultimo_numero'];
}
