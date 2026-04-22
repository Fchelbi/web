<?php
$env = parse_ini_file('.env');
$key = $env['GEMINI_API_KEY'];

$modelsToTest = [
    'gemini-2.5-flash',
    'gemini-2.0-flash-lite',
    'gemma-3-12b-it',
    'gemini-2.0-flash-001'
];

foreach ($modelsToTest as $model) {
    echo "TESTING MODEL: $model\n";
    $ch = curl_init("https://generativelanguage.googleapis.com/v1beta/models/$model:generateContent?key=" . $key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = ['contents' => [['parts' => [['text' => 'hello']]]]];
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if ($httpCode == 200) {
        echo "SUCCESS! Model $model works perfectly.\n\n";
        break; // Stop testing if we found one that works
    } else {
        echo "FAILED. HTTP Code: $httpCode\n";
        $errData = json_decode($response, true);
        echo "Error: " . (isset($errData['error']['message']) ? $errData['error']['message'] : $response) . "\n\n";
    }
}
