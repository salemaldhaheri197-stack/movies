<?php
// api/get_episode.php
// Tool endpoint the ElevenLabs agent calls when the user asks about a movie or a specific TV episode.
// Register this URL as a "Server Tool" in the ElevenLabs agent dashboard.

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

function loadEnv($path) {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}
loadEnv(__DIR__ . '/../.env');

$tmdbKey = getenv('TMDB_API_KEY');
if (!$tmdbKey) {
    http_response_code(500);
    echo json_encode(['error' => 'Server not configured: missing TMDB_API_KEY']);
    exit;
}

// The agent should call this with query params, e.g.:
//   ?type=episode&show=Breaking+Bad&season=3&episode=7
//   ?type=movie&title=Inception
$type = $_GET['type'] ?? '';

function tmdbGet($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $res = curl_exec($ch);
    curl_close($ch);
    return json_decode($res, true);
}

if ($type === 'movie') {
    $title = $_GET['title'] ?? '';
    if (!$title) { http_response_code(400); echo json_encode(['error' => 'Missing title']); exit; }

    $search = tmdbGet("https://api.themoviedb.org/3/search/movie?api_key={$tmdbKey}&query=" . urlencode($title));
    if (empty($search['results'])) {
        echo json_encode(['error' => 'Movie not found']);
        exit;
    }
    $movie = $search['results'][0];
    echo json_encode([
        'title'        => $movie['title'],
        'release_date' => $movie['release_date'],
        'overview'     => $movie['overview'],
        'rating'       => $movie['vote_average'],
    ]);
    exit;
}

if ($type === 'episode') {
    $show    = $_GET['show'] ?? '';
    $season  = $_GET['season'] ?? '';
    $episode = $_GET['episode'] ?? '';
    if (!$show || !$season || !$episode) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing show, season, or episode']);
        exit;
    }

    $search = tmdbGet("https://api.themoviedb.org/3/search/tv?api_key={$tmdbKey}&query=" . urlencode($show));
    if (empty($search['results'])) {
        echo json_encode(['error' => 'Show not found']);
        exit;
    }
    $showId = $search['results'][0]['id'];

    $ep = tmdbGet("https://api.themoviedb.org/3/tv/{$showId}/season/{$season}/episode/{$episode}?api_key={$tmdbKey}");
    if (empty($ep['name'])) {
        echo json_encode(['error' => 'Episode not found']);
        exit;
    }
    echo json_encode([
        'show'       => $search['results'][0]['name'],
        'season'     => $season,
        'episode'    => $episode,
        'name'       => $ep['name'],
        'air_date'   => $ep['air_date'] ?? null,
        'overview'   => $ep['overview'] ?? null,
        'rating'     => $ep['vote_average'] ?? null,
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid or missing "type" param (use "movie" or "episode")']);
