<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Album extends Model
{
    use HasFactory;

    public $table = 'album';
    
    protected $fillable = [
        'id',
        'user_id',
        'title',
        'description',
        'featured_image',
        'date',
        'is_featured',
        'created',
        'modified'
    ];
}
