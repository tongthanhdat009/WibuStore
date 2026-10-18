<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    protected $fillable = ['object_key', 'disk', 'mime_type', 'file_size_bytes', 'width', 'height', 'checksum', 'alt_text', 'visibility', 'status', 'uploaded_by'];

    /**
     * object_key is stored relative to the disk root (AWS_ROOT, e.g. Dat/portfolio),
     * e.g. "public/products/2026/10/uuid.jpg".
     */
    public function url(): string
    {
        if ($this->disk === 's3') {
            if ($this->visibility === 'private') {
                return Storage::disk('s3')->temporaryUrl($this->object_key, now()->addMinutes(30));
            }
            $base = rtrim((string) config('filesystems.disks.s3.url'), '/');
            $root = trim((string) config('filesystems.disks.s3.root'), '/');
            if ($base !== '') {
                $encoded = implode('/', array_map('rawurlencode', explode('/', $this->object_key)));

                return $base.'/'.($root !== '' ? $root.'/' : '').$encoded;
            }
        }

        return '/storage/'.$this->object_key;
    }
}
