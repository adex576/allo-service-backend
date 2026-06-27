<?php
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_exec($ch);
echo curl_errno($ch) ? 'CURL ERROR: ' . curl_error($ch) : 'SSL OK';
echo PHP_EOL . 'curl.cainfo = ' . (ini_get('curl.cainfo') ?: '(empty)');
echo PHP_EOL . 'openssl.cafile = ' . (ini_get('openssl.cafile') ?: '(empty)');
