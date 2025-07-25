<?php 

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiToken extends Model
{
    protected $fillable = ['key', 'access_token', 'updated_at'];
    public $timestamps = ['updated_at'];
    public $created_at = false;
}

