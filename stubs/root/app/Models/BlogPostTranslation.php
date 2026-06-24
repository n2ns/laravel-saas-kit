<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlogPostTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_post_id',
        'locale',
        'title',
        'content',
        'excerpt',
    ];

    public function blogPost()
    {
        return $this->belongsTo(BlogPost::class);
    }
}
