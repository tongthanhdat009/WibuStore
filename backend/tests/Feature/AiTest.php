<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.llm.base_url' => 'http://llm.test/v1',
            'services.llm.api_key' => 'test-key',
            'services.llm.chat_model' => 'cf/@cf/mistralai/mistral-small-3.1-24b-instruct',
            'services.llm.image_model' => 'cf/@cf/bytedance/stable-diffusion-xl-lightning',
        ]);
        $chatSse = "data: {\"model\":\"test-chat\",\"choices\":[{\"delta\":{\"content\":\"Xin chao\"}}]}\n\ndata: [DONE]\n\n";
        $visionSse = "data: {\"model\":\"muse-spark-1.3-contributor\",\"choices\":[{\"delta\":{\"content\":\"Chiec la xanh\"}}]}\n\ndata: [DONE]\n\n";
        Http::fake([
            'llm.test/v1/chat/completions*' => function ($request) use ($chatSse, $visionSse) {
                $payload = $request->data();
                $messages = $payload['messages'] ?? [];
                $hasToolMsg = collect($messages)->contains(fn ($m) => ($m['role'] ?? '') === 'tool');
                // Vong 1 co tools: tra tool_calls; vong 2 (da co tool result) tra text.
                if (! empty($payload['tools']) && ! $hasToolMsg) {
                    return Http::response([
                        'model' => 'test-chat',
                        'choices' => [[
                            'message' => [
                                'content' => null,
                                'tool_calls' => [[
                                    'id' => 'call_1',
                                    'type' => 'function',
                                    'function' => ['name' => 'search_products', 'arguments' => '{"q":"gojo","limit":5}'],
                                ]],
                            ],
                            'finish_reason' => 'tool_calls',
                        ]],
                    ], 200);
                }
                $content = $payload['messages'][0]['content'] ?? '';
                $imageCount = is_array($content)
                    ? collect($content)->where('type', 'image_url')->count()
                    : 0;
                if ($imageCount >= 2) {
                    return Http::response([
                        'model' => 'gemini-3.1-flash-image',
                        'choices' => [[
                            'message' => ['content' => '![image](data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRUlErkJggg==)'],
                        ]],
                    ], 200);
                }
                if ($imageCount === 1) {
                    return Http::response($visionSse, 200);
                }
                if (str_contains(json_encode($payload), 'json-suffix-probe')) {
                    return Http::response(
                        '{"model":"test-chat","choices":[{"message":{"content":"Chao ban"}}]}data: [DONE]'."\n", 200
                    );
                }

                return Http::response($chatSse, 200);
            },
            'llm.test/v1/images/generations*' => Http::response([
                'data' => [['b64_json' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRUlErkJggg==']],
            ], 200),
        ]);
    }

    public function test_chat_returns_reply(): void
    {
        $res = $this->postJson('/api/v1/ai/chat', ['message' => 'chao shop']);

        $res->assertOk()->assertJsonPath('data.reply', 'Xin chao');
    }

    public function test_chat_parses_json_with_done_suffix(): void
    {
        // Provider cf tra JSON kem `data: [DONE]` phia sau khi khong gui `stream`.
        $res = $this->postJson('/api/v1/ai/chat', ['message' => 'json-suffix-probe']);

        $res->assertOk()->assertJsonPath('data.reply', 'Chao ban');
    }

    public function test_chat_without_key_returns_not_configured(): void
    {
        config(['services.llm.api_key' => null]);

        $res = $this->postJson('/api/v1/ai/chat', ['message' => 'chao']);

        $res->assertStatus(502)->assertJsonPath('error.code', 'LLM_NOT_CONFIGURED');
    }

    public function test_chat_calls_search_products_tool(): void
    {
        $this->seed(DemoSeeder::class);

        $res = $this->postJson('/api/v1/ai/chat', ['message' => 'goi y figure gojo']);

        $res->assertOk()->assertJsonPath('data.reply', 'Xin chao');
        $products = $res->json('data.products');
        $this->assertNotEmpty($products);
        $this->assertStringContainsStringIgnoringCase('gojo', $products[0]['name']);
        $this->assertArrayHasKey('url', $products[0]);
        $this->assertArrayHasKey('image', $products[0]);
        $this->assertArrayHasKey('images', $products[0]);
    }

    public function test_vision_returns_description(): void
    {
        config(['services.llm.vision_model' => 'ocg/muse-spark-1.3-contributor']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = UploadedFile::fake()->createWithContent('fig.png', $png);

        $res = $this->post('/api/v1/ai/vision', ['image' => $file, 'prompt' => 'Mo ta anh']);

        $res->assertOk()
            ->assertJsonPath('data.description', 'Chiec la xanh')
            ->assertJsonPath('data.model', 'muse-spark-1.3-contributor');
    }

    public function test_vision_rejects_non_image(): void
    {
        $file = UploadedFile::fake()->createWithContent('note.txt', 'hello');

        $res = $this->post('/api/v1/ai/vision', ['image' => $file]);

        $res->assertStatus(422);
    }

    public function test_tryon_returns_composited_image(): void
    {
        Storage::fake('s3');
        config(['filesystems.default' => 's3']);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwAEhQGAhKmMIQAAAABJRUlErkJggg==');
        $person = UploadedFile::fake()->createWithContent('me.png', $png);
        $outfit = UploadedFile::fake()->createWithContent('ao-dai.png', $png);

        $res = $this->post('/api/v1/ai/try-on', ['person' => $person, 'outfits' => [$outfit]]);

        $res->assertCreated()->assertJsonStructure(['data' => ['url', 'mime_type', 'model', 'media_asset_id']]);
        $asset = MediaAsset::findOrFail($res->json('data.media_asset_id'));
        $this->assertSame('image/png', $asset->mime_type);
        Storage::disk('s3')->assertExists($asset->object_key);
    }

    public function test_tryon_requires_outfit(): void
    {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwAEhQGAhKmMIQAAAABJRUlErkJggg==');
        $person = UploadedFile::fake()->createWithContent('me.png', $png);

        $res = $this->post('/api/v1/ai/try-on', ['person' => $person]);

        $res->assertStatus(422);
    }

    public function test_image_stores_file_and_returns_url(): void
    {
        Storage::fake('s3');
        config(['filesystems.default' => 's3']);

        $res = $this->postJson('/api/v1/ai/image', ['prompt' => 'anime fox girl figure']);

        $res->assertCreated()->assertJsonStructure(['data' => ['url', 'mime_type', 'model', 'media_asset_id']]);
        $asset = MediaAsset::findOrFail($res->json('data.media_asset_id'));
        $this->assertSame('image/png', $asset->mime_type);
        Storage::disk('s3')->assertExists($asset->object_key);
    }
}
