<?php
$url = 'http://127.0.0.1:8000/post/2/delete';
$data = http_build_query(['_token' => 'dummy']);
$options = [
    'http' => [
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => 'POST',
        'content' => $data,
        'ignore_errors' => true
    ]
];
$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);
file_put_contents('test_output.txt', implode("\n", $http_response_header) . "\n\n" . $result);
echo "Testing done\n";
