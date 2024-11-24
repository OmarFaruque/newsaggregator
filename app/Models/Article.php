<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 
        'source',
        'description',
        'content', 
        'author', 
        'category',
        'published_at',
        'url', 
        'url_to_image'
    ];
}
