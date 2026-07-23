<?php
$env = parse_ini_file('.env');
$k = $env['GEMINI_API_KEY'];
$j = file_get_contents('https://generativelanguage.googleapis.com/v1beta/models?key=' . $k);
$d = json_decode($j, true);
foreach($d['models'] as $m) {
    if (strpos($m['name'], 'gemini-1.5-flash') !== false) {
        echo $m['name'] . " - Supported methods: " . implode(', ', $m['supportedGenerationMethods']) . "\n";
    }
}
