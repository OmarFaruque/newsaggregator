<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'news_sources', 'categories', 'authors'];
    protected $casts = [
        'news_sources' => 'array',
        'categories' => 'array',
        'authors' => 'array',
    ];
}
