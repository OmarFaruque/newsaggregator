<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ArticleFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test fetching articles from NewsAPI.
     */
    public function test_fetch_articles_from_newsapi()
    {
        // Mock the method to return a sample response
        $this->partialMock(\App\Http\Controllers\ArticleController::class, function ($mock) {
            $mock->shouldReceive('fetchFromNewsAPI')
                 ->once()
                 ->andReturn(response()->json([
                     [
                         'title' => 'Sample NewsAPI Article',
                         'author' => 'John Doe',
                         'source' => 'newsapi',
                         'published_at' => '2024-11-24T10:00:00Z',
                         'url' => 'https://example.com/article',
                     ]
                 ], 200));
        });

        $response = $this->getJson('/api/articles?source=newsapi');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     '*' => [
                         'title',
                         'author',
                         'source',
                         'published_at',
                         'url',
                     ],
                 ]);
    }

    /**
     * Test fetching articles from an invalid source.
     */
    public function test_fetch_articles_from_invalid_source()
    {
        $response = $this->getJson('/api/articles?source=invalid');

        $response->assertStatus(400)
                 ->assertJson([
                     'message' => 'Failed to fetch articles',
                 ]);
    }

    /**
     * Test exception handling while fetching articles.
     */
    public function test_exception_handling_while_fetching_articles()
    {
        $this->partialMock(\App\Http\Controllers\ArticleController::class, function ($mock) {
            $mock->shouldReceive('fetchFromNewsAPI')
                 ->once()
                 ->andThrow(new \Exception('Simulated error'));
        });

        $response = $this->getJson('/api/articles?source=newsapi');

        $response->assertStatus(500)
                 ->assertJson([
                     'message' => 'An error occurred while fetching articles',
                     'error' => 'Simulated error',
                 ]);
    }
}
