<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Slide hero đầu homepage bị CMS override (headline "NEW DROP: Gojo Satoru",
 * nút "Mua ngay") nhưng cta_target trỏ tới gojo-satoru-unlimited-void-1-7 —
 * món demo KHÔNG có ảnh, trang product render trống. Chuyển sang
 * figure-4-seed (Gojo Satoru Bịt Mắt, có ảnh S3 thật), khớp headline CMS.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('cms_page_revisions')->get(['id', 'content']);
        foreach ($rows as $row) {
            $content = is_string($row->content) ? json_decode($row->content, true) : $row->content;
            if (! is_array($content) || ! isset($content['sections']) || ! is_array($content['sections'])) {
                continue;
            }
            $changed = false;
            foreach ($content['sections'] as &$section) {
                if (($section['type'] ?? null) === 'hero_comic'
                    && ($section['cta_target'] ?? null) === '/products/gojo-satoru-unlimited-void-1-7') {
                    $section['cta_target'] = '/products/figure-4-seed';
                    $changed = true;
                }
            }
            unset($section);
            if ($changed) {
                DB::table('cms_page_revisions')->where('id', $row->id)->update([
                    'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        $rows = DB::table('cms_page_revisions')->get(['id', 'content']);
        foreach ($rows as $row) {
            $content = is_string($row->content) ? json_decode($row->content, true) : $row->content;
            if (! is_array($content) || ! isset($content['sections']) || ! is_array($content['sections'])) {
                continue;
            }
            $changed = false;
            foreach ($content['sections'] as &$section) {
                if (($section['type'] ?? null) === 'hero_comic'
                    && ($section['cta_target'] ?? null) === '/products/figure-4-seed') {
                    $section['cta_target'] = '/products/gojo-satoru-unlimited-void-1-7';
                    $changed = true;
                }
            }
            unset($section);
            if ($changed) {
                DB::table('cms_page_revisions')->where('id', $row->id)->update([
                    'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
