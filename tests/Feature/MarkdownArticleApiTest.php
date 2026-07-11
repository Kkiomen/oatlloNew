<?php

namespace Tests\Feature;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MarkdownArticleApiTest extends TestCase
{
    private string $dir;
    private string $token = 'test-token-123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('framework/testing/articles-' . uniqid());
        config()->set('articles.path', $this->dir);
        config()->set('articles.api_token', $this->token);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->dir)) {
            File::deleteDirectory($this->dir);
        }

        parent::tearDown();
    }

    private function markdown(): string
    {
        return "---\nname: \"Test API Article\"\nslug: test-api-article\nlanguage: en\ntags: [alpha, beta]\n---\n\n## Heading\n\nBody **content**.";
    }

    public function test_rejects_request_without_token(): void
    {
        $this->postJson('/api/articles', ['content' => $this->markdown()])
            ->assertStatus(401);
    }

    public function test_uploads_article_via_content(): void
    {
        $response = $this->withToken($this->token)
            ->postJson('/api/articles', ['content' => $this->markdown()]);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'data' => ['slug' => 'test-api-article']]);

        $this->assertTrue(File::exists($this->dir . DIRECTORY_SEPARATOR . 'test-api-article.md'));
    }

    public function test_uploads_article_via_file(): void
    {
        $file = UploadedFile::fake()->createWithContent('post.md', $this->markdown());

        $response = $this->withToken($this->token)
            ->post('/api/articles', ['file' => $file], ['Accept' => 'application/json']);

        $response->assertStatus(201)
            ->assertJson(['success' => true, 'data' => ['slug' => 'test-api-article']]);

        $this->assertTrue(File::exists($this->dir . DIRECTORY_SEPARATOR . 'test-api-article.md'));
    }

    public function test_rejects_content_without_name(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/articles', ['content' => "---\nslug: no-name\n---\n\nbody"])
            ->assertStatus(422);
    }

    public function test_lists_and_deletes_article(): void
    {
        $this->withToken($this->token)
            ->postJson('/api/articles', ['content' => $this->markdown()])
            ->assertStatus(201);

        $this->withToken($this->token)
            ->getJson('/api/articles')
            ->assertStatus(200)
            ->assertJsonFragment(['slug' => 'test-api-article']);

        $this->withToken($this->token)
            ->deleteJson('/api/articles/test-api-article')
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertFalse(File::exists($this->dir . DIRECTORY_SEPARATOR . 'test-api-article.md'));
    }
}
