<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\UserPreference;


/**
 * UserPreferenceController
 *
 * This controller manages user preferences and provides functionality to:
 * - Save user preferences such as news sources, categories, and authors.
 * - Retrieve the saved preferences for an authenticated user.
 * - Generate a personalized feed of news articles based on user preferences.
 * - Fetch data from various external news APIs like NewsAPI, The Guardian, and The New York Times.
 */
class UserPreferenceController extends Controller
{
    /**
     * @OA\Post(
     *     path="/user/preferences",
     *     summary="Save user preferences",
     *     description="Stores or updates the authenticated user's preferences, including preferred news sources, categories, and authors.",
     *     tags={"User Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="news_sources",
     *                 type="array",
     *                 description="Array of preferred news sources",
     *                 @OA\Items(type="string", example="newsapi")
     *             ),
     *             @OA\Property(
     *                 property="categories",
     *                 type="array",
     *                 description="Array of preferred categories",
     *                 @OA\Items(type="string", example="technology")
     *             ),
     *             @OA\Property(
     *                 property="authors",
     *                 type="array",
     *                 description="Array of preferred authors",
     *                 @OA\Items(type="string", example="John Doe")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Preferences saved successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Preferences saved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="news_sources", type="array", @OA\Items(type="string"), example={"newsapi"}),
     *                 @OA\Property(property="categories", type="array", @OA\Items(type="string"), example={"technology"}),
     *                 @OA\Property(property="authors", type="array", @OA\Items(type="string"), example={"John Doe"})
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication required.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation errors.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="errors", type="object", example={
     *                 "news_sources": {"The news sources field is required."}
     *             })
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $userId = auth()->id(); // Ensure the user is authenticated

    
        $validated = $request->validate([
            'news_sources' => 'array',
            'categories' => 'array',
            'authors' => 'array',
        ]);

        $preferences = UserPreference::updateOrCreate(
            ['user_id' => $userId],
            [
                'news_sources' => json_encode($validated['news_sources']),
                'categories' => json_encode($validated['categories']),
                'authors' => json_encode($validated['authors']),
            ]
        );
    
        return response()->json([
            'message' => 'Preferences saved successfully',
            'data' => $preferences,
        ], 200);
    }


    /**
     * @OA\Get(
     *     path="/user/preferences",
     *     summary="Retrieve user preferences",
     *     description="Fetches the saved preferences for the authenticated user, including selected news sources, categories, and authors.",
     *     tags={"User Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Preferences retrieved successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(
     *                     property="news_sources",
     *                     type="array",
     *                     description="Array of preferred news sources",
     *                     @OA\Items(type="string", example="newsapi")
     *                 ),
     *                 @OA\Property(
     *                     property="categories",
     *                     type="array",
     *                     description="Array of preferred categories",
     *                     @OA\Items(type="string", example="technology")
     *                 ),
     *                 @OA\Property(
     *                     property="authors",
     *                     type="array",
     *                     description="Array of preferred authors",
     *                     @OA\Items(type="string", example="John Doe")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No preferences found.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="No preferences found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication required.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function show()
    {
        $userId = auth()->id();

        $preferences = UserPreference::where('user_id', $userId)->first();

        if (!$preferences) {
            return response()->json([
                'message' => 'No preferences found',
            ], 404);
        }

        return response()->json([
            'data' => [
                'news_sources' => json_decode($preferences->news_sources),
                'categories' => json_decode($preferences->categories),
                'authors' => json_decode($preferences->authors),
            ],
        ], 200);
    }


    /**
     * @OA\Get(
     *     path="/user/personalized-feed",
     *     summary="Generate a personalized news feed",
     *     description="Fetches personalized news articles based on the user's preferences, such as news sources, categories, and authors. Aggregates data from multiple external APIs.",
     *     tags={"User Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Number of articles per page (default: 10)",
     *         @OA\Schema(type="integer", example=10)
     *     ),
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         required=false,
     *         description="Current page number (default: 1)",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Personalized articles retrieved successfully.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Array of personalized articles.",
     *                 @OA\Items(type="object")
     *             ),
     *             @OA\Property(property="total", type="integer", example=100, description="Total number of articles."),
     *             @OA\Property(property="per_page", type="integer", example=10, description="Articles per page."),
     *             @OA\Property(property="current_page", type="integer", example=1, description="Current page number.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid or missing preferences.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User preferences not set")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Authentication required.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error.",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="An error occurred while fetching articles."),
     *             @OA\Property(property="error", type="string", example="Exception message")
     *         )
     *     )
     * )
     */
    public function personalizedFeed(Request $request)
    {
        $user = $request->user();
        $preferences = $user->preferences; // Assuming $user->preferences relation exists


        if (!$preferences) {
            return response()->json(['message' => 'User preferences not set'], 400);
        }

        $newsSources = json_decode($preferences->news_sources, true);
        if (!$newsSources || !is_array($newsSources)) {
            return response()->json(['message' => 'Invalid or missing news sources'], 400);
        }
        
        $articles = [];

        // Fetch from NewsAPI
        if (in_array('newsapi', $newsSources)) {
            $newsapiArticles = $this->fetchFromNewsAPI([
                'categories' => $preferences->categories,
                'authors' => $preferences->authors,
            ]);
           
            $articles = array_merge($articles, $newsapiArticles);
        }

        // Fetch from The Guardian
        if (in_array('guardian', $newsSources)) {
            $guardianArticles = $this->fetchFromGuardian([
                'categories' => $preferences->categories,
                'authors' => $preferences->authors,
            ]);
            $articles = array_merge($articles, $guardianArticles);
        }

        // Fetch from New York Times
        if (in_array('newyorktimes', $newsSources)) {
            $nytArticles = $this->fetchFromNewYorkTimes([
                'categories' => $preferences->categories,
                'authors' => $preferences->authors,
            ]);
            $articles = array_merge($articles, $nytArticles);
        }

        // Paginate results
        $perPage = $request->get('per_page', 10);
        $currentPage = $request->get('page', 1);
        $pagedData = array_slice($articles, ($currentPage - 1) * $perPage, $perPage);

        return response()->json([
            'data' => $pagedData,
            'total' => count($articles),
            'per_page' => $perPage,
            'current_page' => $currentPage,
        ]);
    }



    /**
     * Fetch articles from NewsAPI.
     *
     * This method interacts with the NewsAPI service to retrieve articles based on the provided filter parameters.
     * It uses the `everything` endpoint of NewsAPI and supports filtering by categories and authors.
     *
     * @param array $params Filter parameters including:
     *  - `categories` (optional): JSON-encoded array of category names to filter the articles.
     *  - `authors` (optional): JSON-encoded array of author names to filter the articles.
     *
     * Query Parameters:
     * - `q`: Comma-separated string of category names derived from `categories` parameter.
     * - `authors`: Comma-separated string of author names derived from `authors` parameter.
     * - `apiKey`: The API key for authenticating with NewsAPI, retrieved from the application's configuration.
     *
     * @return array List of articles retrieved from NewsAPI. Returns an empty array if the request fails or no articles are found.
     *
     * Usage:
     * - Call this method from other controllers or services to fetch articles.
     * - Ensure that `newsapi.api_key` is set correctly in the application's configuration file.
     *
     * External Dependencies:
     * - Requires the `Http` facade to make HTTP requests.
     *
     * Example:
     * ```php
     * $params = [
     *     'categories' => json_encode(['technology', 'science']),
     *     'authors' => json_encode(['John Doe', 'Jane Smith']),
     * ];
     * $articles = $this->fetchFromNewsAPI($params);
     * ```
     */
    protected function fetchFromNewsAPI($params)
    {
        $apiKey = config('services.newsapi.api_key');  // API Key;

        // Decode authors if provided
        $authors = isset($params['authors']) ? json_decode($params['authors'], true) : [];
        $authorsQuery = is_array($authors) ? implode(', ', $authors) : '';

        // Decode categories if provided
        $cat = isset($params['categories']) ? json_decode($params['categories'], true) : [];
        $catQuery = is_array($cat) ? implode(', ', $cat) : '';

        $queryParams = [
            'q' => $catQuery,
            'authors' => $authorsQuery,
            'apiKey' => $apiKey,
        ];

        $response = Http::get('https://newsapi.org/v2/everything', $queryParams);

        if ($response->successful()) {
            return $response->json()['articles'] ?? [];
        }

        return [];
    }


    /**
     * Fetch articles from The Guardian API.
     *
     * This method communicates with The Guardian's Content API to retrieve articles based on the provided filter parameters.
     * It uses the `search` endpoint to query articles and supports filtering by categories.
     *
     * @param array $params Filter parameters including:
     *  - `categories` (optional): JSON-encoded array of category names to filter the articles.
     *
     * Query Parameters:
     * - `q`: Comma-separated string of category names derived from `categories` parameter.
     * - `api-key`: The API key for authenticating with The Guardian API, retrieved from the application's configuration file.
     *
     * @return array List of articles retrieved from The Guardian API. Returns an empty array if the request fails or no articles are found.
     *
     * Behavior:
     * - Decodes the `categories` parameter if provided.
     * - Converts the decoded array into a comma-separated string for the query.
     * - Sends an HTTP GET request to The Guardian API with the constructed query parameters.
     * - Returns the `results` array from the API's `response` object if the request is successful.
     *
     * External Dependencies:
     * - Requires the `Http` facade to make HTTP requests.
     * - Requires a valid API key configured in `services.guardian.api_key`.
     *
     * Example Usage:
     * ```php
     * $params = [
     *     'categories' => json_encode(['politics', 'science']),
     * ];
     * $articles = $this->fetchFromGuardian($params);
     * ```
     *
     * API Documentation:
     * - For more details on The Guardian API, refer to their documentation at:
     *   https://open-platform.theguardian.com/documentation/
     */
    protected function fetchFromGuardian($params)
    {
        $apiKey = config('services.guardian.api_key');

        // Decode categories if provided
        $cat = isset($params['categories']) ? json_decode($params['categories'], true) : [];
        $catQuery = is_array($cat) ? implode(', ', $cat) : '';
        
        $queryParams = [
            'q' => $catQuery,
            'api-key' => $apiKey,
        ];

        $response = Http::get('https://content.guardianapis.com/search', $queryParams);

        if ($response->successful()) {
            return $response->json()['response']['results'] ?? [];
        }

        return [];
    }

    /**
     * Fetch articles from The New York Times API.
     *
     * This method queries The New York Times Article Search API to retrieve articles based on the provided filter parameters.
     * It supports filtering by categories and constructs a query for searching articles.
     *
     * @param array $params Filter parameters including:
     *  - `categories` (optional): JSON-encoded array of categories to filter articles.
     *
     * Query Parameters:
     * - `q`: A comma-separated string of categories derived from the `categories` parameter.
     * - `api-key`: The API key required for authentication with The New York Times API, configured in the application's configuration file.
     *
     * @return array List of articles retrieved from The New York Times API. If the request fails or no articles are found, an empty array is returned.
     *
     * Behavior:
     * - Decodes the `categories` parameter if provided.
     * - Converts the decoded array into a comma-separated string for the API query.
     * - Sends an HTTP GET request to the Article Search API endpoint with the constructed query parameters.
     * - If the response is successful, returns the `docs` array from the API's response.
     *
     * External Dependencies:
     * - Requires the `Http` facade to perform the HTTP request.
     * - A valid API key must be configured in the `services.newyorktimes.api_key` configuration.
     *
     * Example Usage:
     * ```php
     * $params = [
     *     'categories' => json_encode(['world', 'business']),
     * ];
     * $articles = $this->fetchFromNewYorkTimes($params);
     * ```
     *
     * API Documentation:
     * - For more details on The New York Times API, refer to their official documentation at:
     *   https://developer.nytimes.com/docs/articlesearch-product/1/overview
     */
    protected function fetchFromNewYorkTimes($params)
    {
        $apiKey = config('services.newyorktimes.api_key');

        // Decode categories if provided
        $cat = isset($params['categories']) ? json_decode($params['categories'], true) : [];
        $catQuery = is_array($cat) ? implode(', ', $cat) : '';

        $queryParams = [
            'q' => $catQuery,
            'api-key' => $apiKey,
        ];

        $response = Http::get('https://api.nytimes.com/svc/search/v2/articlesearch.json', $queryParams);

        if ($response->successful()) {
            return $response->json()['response']['docs'] ?? [];
        }

        return [];
    }

    
}
