<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPageRevision extends Model
{
    protected $fillable = ['page_id', 'version_number', 'status', 'content', 'created_by', 'published_by', 'published_at'];

    protected $casts = ['content' => 'array', 'published_at' => 'datetime'];
}
