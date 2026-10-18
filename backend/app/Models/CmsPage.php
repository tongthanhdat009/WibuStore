<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    public $timestamps = true;

    protected $fillable = ['slug', 'title', 'current_published_revision_id'];

    public function revisions()
    {
        return $this->hasMany(CmsPageRevision::class, 'page_id')->orderByDesc('version_number');
    }

    public function publishedRevision()
    {
        return $this->belongsTo(CmsPageRevision::class, 'current_published_revision_id');
    }
}
