<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Article;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;


/**
 * @OA\Info(
 *     title="News Aggregator API",
 *     version="1.0.0",
 *     description="API Documentation for fetching and managing news articles."
 * )
 */
class ArticleController extends Controller
{

    /**
     * Fetch articles from multiple external APIs.
     *
     * This method retrieves and aggregates articles from three different sources: 
     * NewsAPI, Guardian API, and New York Times API. It processes the data to store 
     * it in a unified format in the database.
     *
     * @OA\Get(
     *     path="/api/articles/fetch",
     *     summary="Fetch and aggregate articles",
     *     tags={"Articles"},
     *     description="Fetches articles from multiple external APIs and stores them in the database.",
     *     @OA\Response(
     *         response=200,
     *         description="Articles fetched and stored successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "status": "success",
     *                 "message": "Articles fetched and stored successfully",
     *                 "articles_fetched": 150
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "status": "error",
     *                 "message": "Failed to fetch articles"
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             type="object",
     *             example={
     *                 "status": "error",
     *                 "message": "Invalid parameters provided"
     *             }
     *         )
     *     )
     * )
     */
    public function fetchArticles()
    {

        $articles = [];

        // Fetch articles from NewsAPI
        $newsAPIArticles = $this->fetchFromNewsAPI(new Request());
        $newsApiData = $newsAPIArticles->getData(true)['articles']; 
        $this->storeArticles($newsApiData, 'NewsAPI');

        // Fetch articles from The Guardian response
        $guardianArticles = $this->fetchFromGuardian(new Request());
        $guardianData = $guardianArticles->getData(true)['data']['results']; 
        $this->storeArticles($guardianData, 'Guardian');

         // Fetch articles from New York Times
        $nytArticles = $this->fetchFromNYTimes(new Request());
        $nytimesData = $nytArticles->getData(true)['data']; 
        $this->storeArticles($nytimesData, 'New York Times');
        

        return response()->json(['message' => 'Articles fetched and stored successfully.']);
    }




    /**
     * Normalize and store articles in the database.
     *
     * This method is called internally by the `fetchArticles` method to process
     * and save articles fetched from external sources like NewsAPI, The Guardian, 
     * and The New York Times. Each article is normalized to a common structure 
     * before being stored.
     *
     * @param array $articles Array of articles fetched from an API.
     * @param string $source The name of the source from which the articles were fetched (e.g., 'NewsAPI', 'Guardian').
     * @return void
     */
    public function storeArticles($articles, $source)
    {
        foreach ($articles as $article) {
            $normalizedArticle = $this->transformApiResponse($article, $source);
    
            Article::updateOrCreate(
                ['url' => $normalizedArticle['url']],
                $normalizedArticle
            );
        }
    }    




    /**
     * Process API data and store it in the database.
     *
     * This method processes raw API responses and normalizes them based on the provided source.
     * The supported sources are 'Guardian', 'NewsAPI', and 'New York Times'.
     *
     * @param array $data The raw API response data.
     * @param string $source The name of the API source.
     * @throws \Exception If the source is unsupported.
     * @return null
     */
    protected function transformApiResponse($data, $source)
    {
        switch ($source) {
            case 'Guardian':
                return $this->transformGuardianApiResponse($data);
            case 'NewsAPI':
                return $this->transformNewsApiResponse($data);
            case 'New York Times':
                return $this->transformNewYorkTimesApiResponse($data);
            default:
                throw new Exception("Unsupported API source: $source");
        }
    }


    /**
     * Normalize NewsAPI response data for consistent access.
     *
     * This method maps NewsAPI fields to a unified format for easier processing and storage.
     *
     * @param array $data The raw NewsAPI response data.
     * @return array The normalized data.
     */
    protected function transformNewsApiResponse($data)
    {
        return [
            'source' => 'NewsAPI',
            'title' => $data['title'] ?? '',
            'description' => $data['description'] ?? '',
            'content' => $data['content'] ?? '',
            'author' => $data['author'] ?? '',
            'category' => $data['category'] ?? 'General',
            'published_at' => isset($data['publishedAt']) ? Carbon::parse($data['publishedAt'])->toDateTimeString() : null,
            'url' => $data['url'] ?? '',
            'url_to_image' => $data['urlToImage'] ?? '',
        ];
    }


    /**
     * Normalize Guardian API response data for consistent access.
     *
     * This method maps Guardian API fields to a unified format for easier processing and storage.
     *
     * @param array $data The raw Guardian API response data.
     * @return array The normalized data.
     */
    protected function transformGuardianApiResponse($data)
    {
        return [
            'source' => 'Guardian',
            'title' => $data['webTitle'] ?? '',
            'description' => '',
            'content' => '',
            'author' => '',
            'category' => $data['pillarName'] ?? 'General',
            'published_at' => isset($data['webPublicationDate']) ? Carbon::parse($data['webPublicationDate'])->toDateTimeString() : null,
            'url' => $data['webUrl'] ?? '',
            'url_to_image' => '',
        ];
    }


    /**
     * Normalize New York Times API response data for consistent access.
     *
     * This method maps New York Times API fields to a unified format for easier processing and storage.
     *
     * @param array $data The raw New York Times API response data.
     * @return array The normalized data.
     */
    protected function transformNewYorkTimesApiResponse($data)
    {
        return [
            'source' => 'New York Times', // Static source name
            'title' => $data['abstract'] ?? '',
            'description' => $data['snippet'] ?? '',
            'content' => $data['lead_paragraph'] ?? '',
            'author' => '', // NYT response doesn't seem to include author in this example
            'category' => $data['print_section'] ?? 'General',
            'published_at' => null, // Adjust if you have a publication date in the response
            'url' => $data['web_url'] ?? '',
            'url_to_image' => isset($data['multimedia'][0]['url']) ? $data['multimedia'][0]['url'] : '', // Assume first multimedia item is the image
        ];
    }


    /**
     * @OA\Get(
     *     path="/articles",
     *     summary="Fetch articles from external APIs",
     *     description="Fetches articles from different sources like NewsAPI, Guardian, and New York Times based on the provided source parameter.",
     *     tags={"Articles"},
     *     @OA\Parameter(
     *         name="source",
     *         in="query",
     *         description="The source to fetch articles from (e.g., 'newsapi', 'guardian', 'newyorktimes').",
     *         required=true,
     *         @OA\Schema(type="string", example="newsapi")
     *     ),
     *     @OA\Parameter(
     *         name="keyword",
     *         in="query",
     *         description="Optional search keyword to filter articles.",
     *         required=false,
     *         @OA\Schema(type="string", example="technology")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Articles fetched successfully.",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="title", type="string", example="Sample Article Title"),
     *                 @OA\Property(property="author", type="string", example="John Doe"),
     *                 @OA\Property(property="source", type="string", example="newsapi"),
     *                 @OA\Property(property="published_at", type="string", format="date-time", example="2024-11-24T10:00:00Z"),
     *                 @OA\Property(property="url", type="string", example="https://example.com/article")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid source parameter or missing source.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Failed to fetch articles")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error while fetching articles.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while fetching articles"),
     *             @OA\Property(property="error", type="string", example="Detailed error message")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        try{
            $source = $request->source;
            // Decide which external source to fetch from
            if ($source == 'newsapi') {
                return $this->fetchFromNewsAPI($request);
            } elseif ($source == 'guardian') {
                return $this->fetchFromGuardian($request);
            } elseif ($source == 'newyorktimes') {
                return $this->fetchFromNYTimes($request);
            }else {
                return response()->json([
                    'message' => 'Failed to fetch articles',
                ], 400);
            }
        }catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching articles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Fetch articles from NewsAPI.
     *
     * This method makes an HTTP request to NewsAPI to retrieve articles based on search criteria.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object containing parameters like `keyword`, `page`, etc.
     * @return \Illuminate\Http\JsonResponse JSON response containing articles or an error message.
     */
    public function fetchFromNewsAPI(Request $request)
    {
        
        $apiKey = config('services.newsapi.api_key');  // API Key
        $response = Http::get('https://newsapi.org/v2/everything', [
            'q' => $request->keyword ?? 'technology', 
            'apiKey' => $apiKey, 
            'page' => $request->page ?? 1, 
            'pageSize' => $request->pagesize ?? 100
        ]);

        if ($response->successful()) {
            return response()->json($response->json(), 200);
        }

        return response()->json(['message' => 'Failed to fetch articles'], 500);
    }


    /**
     * Fetch articles from Guardian API.
     *
     * This method makes an HTTP request to The Guardian API to retrieve articles based on search criteria.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object containing parameters like `keyword`, `category`, `date`, etc.
     * @return \Illuminate\Http\JsonResponse JSON response containing articles or an error message.
     */
    private function fetchFromGuardian(Request $request)
    {
        $apiKey = config('services.guardian.api_key'); // API Key

        try {
            // Base URL for The Guardian API
            $baseUrl = 'https://content.guardianapis.com/search';
    
            // Prepare query parameters
            $queryParams = [
                'page' => $request->page ?? 1,
                'page-size' => $request->pagesize ?? 10,
                'api-key' => $apiKey,
            ];
            if($request->keyword)
                $queryParams['q'] = $request->keyword;

            if($request->category)
                $queryParams['section'] = $request->category;

            if($request->date)
                $queryParams['from-date'] = $request->date;

    
            // Make HTTP GET request to The Guardian API
            $response = Http::get($baseUrl, $queryParams);
    
            if ($response->ok()) {
                $data = $response->json();
    
                return response()->json([
                    'message' => 'Articles fetched successfully',
                    'data' => $data['response'], // The Guardian wraps its results in a 'response' key
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Failed to fetch articles',
                    'error' => $response->body(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching articles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Fetch articles from New York Times API.
     *
     * This method makes an HTTP request to New York Times API to retrieve articles based on search criteria.
     *
     * @param \Illuminate\Http\Request $request The HTTP request object containing parameters like `keyword`, `category`, `date`, etc.
     * @return \Illuminate\Http\JsonResponse JSON response containing articles or an error message.
     */
    private function fetchFromNYTimes(Request $request)
    {
        $apiKey = config('services.newyorktimes.api_key'); // Add your NYT API key to the .env file

        try {
            // Base URL for New York Times Article Search API
            $baseUrl = 'https://api.nytimes.com/svc/search/v2/articlesearch.json';

            // Prepare query parameters
            $queryParams = [
                'fq' => $request->category ? "news_desk:(" . $request->category . ")" : null, // Filter by category
                'begin_date' => $request->date ? date('Ymd', strtotime($request->date)) : null, // Start date in YYYYMMDD format
                'page' => $request->page ?? 0, // Page number (starts from 0)
                'api-key' => $apiKey,
            ];
            if($request->keyword)
                $queryParams['q'] = $request->keyword;

            // Make HTTP GET request to The New York Times API
            $response = Http::get($baseUrl, array_filter($queryParams)); // array_filter removes null values

            if ($response->ok()) {
                $data = $response->json();

                return response()->json([
                    'message' => 'Articles fetched successfully',
                    'data' => $data['response']['docs'], // The results are under 'response.docs'
                    'pagination' => [
                        'currentPage' => $queryParams['page'] + 1,
                        'totalPages' => ceil($data['response']['meta']['hits'] / 10),
                    ],
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Failed to fetch articles',
                    'error' => $response->body(),
                ], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching articles',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    /**
     * @OA\Get(
     *     path="/articles/{id}",
     *     summary="Fetch a single article from a specified source",
     *     description="Retrieve the details of a single article by its ID from a specific source. 
     *                  Supported sources include 'newsapi', 'guardian', and 'nyt' (New York Times).",
     *     tags={"Articles"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The unique identifier of the article to be fetched.",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="source",
     *         in="query",
     *         required=true,
     *         description="The source from which to fetch the article. Valid values: 'newsapi', 'guardian', 'nyt'.",
     *         @OA\Schema(type="string", enum={"newsapi", "guardian", "nyt"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Article details retrieved successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="string", description="The ID of the article."),
     *             @OA\Property(property="title", type="string", description="The title of the article."),
     *             @OA\Property(property="content", type="string", description="The content/body of the article."),
     *             @OA\Property(property="source", type="string", description="The source of the article."),
     *             @OA\Property(property="author", type="string", description="The author of the article."),
     *             @OA\Property(property="published_at", type="string", format="date-time", description="The publication date of the article."),
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad Request. Possible reasons: missing or invalid 'source' parameter.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", description="Error message explaining the issue.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Article not found.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", description="Error message indicating the article was not found.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error while fetching the article.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", description="Error message indicating an internal server issue."),
     *             @OA\Property(property="error", type="string", description="Detailed error information.")
     *         )
     *     ),
     *     security={
     *         {"sanctum": {}}
     *     }
     * )
     */
    public function show(Request $request, $id)
    {
        $source = $request->query('source'); // Example: 'newsapi', 'guardian', newyorktime etc.

        if (!$source) {
            return response()->json([
                'message' => 'Source parameter is required.',
            ], 400);
        }

        try {
            switch ($source) {
                case 'newsapi':
                    return $this->fetchSingleFromNewsAPI($id);
                case 'guardian':
                    return $this->fetchSingleFromGuardian($id);
                case 'nyt':
                    return $this->fetchSingleFromNYTimes($id);
                default:
                    return response()->json([
                        'message' => 'Invalid source parameter.',
                    ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch article details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }





    /**
     * Featch single article from newsapi. 
     * Unfortunately, NewsAPI does not provide a direct way to fetch a single article. 
     * Instead, we need to perform a search query with a unique identifier like the title & content in URL.
     * @param string $id
     * @return json
     */
    private function fetchSingleFromNewsAPI($id)
    {
        $apiKey = config('services.newsapi.api_key');  // API Key

        $response = Http::get('https://newsapi.org/v2/everything', [
            'q' => $id, // Use the article title or URL as the unique identifier
            'apiKey' => $apiKey,
        ]);

        if ($response->ok()) {
            $articles = $response->json()['articles'];
            return count($articles) > 0 ? response()->json($articles[0], 200) // Return the first match
                : response()->json(['message' => 'Article not found.'], 404);
        } else {
            return response()->json([
                'message' => 'Failed to fetch article.',
                'error' => $response->body(),
            ], $response->status());
        }
    }



    /**
     * Fatch single article details using article id from gurdian
     * The Guardian provides a content endpoint to fetch a single article by its ID
     * @param string $id
     * @return json
     */
    private function fetchSingleFromGuardian($id)
    {
        $apiKey = config('services.guardian.api_key');

        $response = Http::get("https://content.guardianapis.com/$id", [
            'api-key' => $apiKey,
        ]);

        if ($response->ok()) {
            $data = $response->json();
            return response()->json($data['response']['content'], 200);
        } else {
            return response()->json([
                'message' => 'Failed to fetch article.',
                'error' => $response->body(),
            ], $response->status());
        }
    }


    /**
     * The New York Times API does not provide a direct endpoint for a single article.
     * Similar to NewsAPI, we can use a search query
     * 
     * @param string $id
     * @return json
     */

    private function fetchSingleFromNYTimes($id)
    {
        $apiKey = config('services.newyorktimes.api_key');

        $response = Http::get('https://api.nytimes.com/svc/search/v2/articlesearch.json', [
            'q' => $id, // Use a unique identifier such as title or part of the URL
            'api-key' => $apiKey,
        ]);

        if ($response->ok()) {
            $docs = $response->json()['response']['docs'];
            return count($docs) > 0
                ? response()->json($docs[0], 200) // Return the first match
                : response()->json(['message' => 'Article not found.'], 404);
        } else {
            return response()->json([
                'message' => 'Failed to fetch article.',
                'error' => $response->body(),
            ], $response->status());
        }
    }
}
