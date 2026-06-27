<?php
// inspect the certificate chain actually presented to PHP
$ch = curl_init('https://oauth2.googleapis.com/token');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CERTINFO, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // complete the handshake just to read the certs
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_exec($ch);
$info = curl_getinfo($ch, CURLINFO_CERTINFO);
if (!$info) { echo "no cert info"; exit; }
foreach ($info as $i => $cert) {
    echo "--- cert $i ---" . PHP_EOL;
    echo "Subject: " . ($cert['Subject'] ?? '?') . PHP_EOL;
    echo "Issuer:  " . ($cert['Issuer'] ?? '?') . PHP_EOL;
}
