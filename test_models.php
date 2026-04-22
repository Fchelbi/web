<?php
$env = parse_ini_file('.env');
$key = $env['GEMINI_API_KEY'];
$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $key;
$models = [];
$nextPageToken = '';

do {
    $currentUrl = $url;
    if ($nextPageToken) {
        $currentUrl .= '&pageToken=' . urlencode($nextPageToken);
    }
    
    $ch = curl_init($currentUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    
    if (isset($data['models'])) {
        foreach ($data['models'] as $m) {
            if (isset($m['supportedGenerationMethods']) && in_array('generateContent', $m['supportedGenerationMethods'])) {
                $models[] = $m['name'];
            }
        }
    }
    
    $nextPageToken = isset($data['nextPageToken']) ? $data['nextPageToken'] : '';
} while ($nextPageToken);

echo "MODELS SUPPORTING generateContent:\n";
foreach ($models as $m) {
    echo $m . "\n";
}
