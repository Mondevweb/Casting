<?php
$ch = curl_init('https://127.0.0.1:8000/api/media_objects');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/ld+json',
    'Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJpYXQiOjE3NzI4MDA4NjAsImV4cCI6MTc3MzQwNTY2MCwicm9sZXMiOlsiUk9MRV9DQU5ESURBVEUiLCJST0xFX1VTRVIiXSwidXNlcm5hbWUiOiJjYW5kaWRhdGUwQGNhc3RpbmcuY29tIn0.E6PTuXhoy7TKMi7OIUgF-jsMpWBlO2jWkbB8jym-JMiuP1EbI7TTC_qLtHZvVckBzl7n7AL5noImJLQHYhEnTKkDqq8KKyboSyS6fUA6w_fz9XbNPmvTxJoqpKubzHsDyMBbtxqs5LfDxCNvREBFgcqq6d_zLEeK0FZeYbqgkWz35tQzMym66kbOM5z3LZepCrYWg2b6I6jtF9tsPe0onpp2MOPtejRzHMPSIUZ1gHGhhV1iRm0ypxEaqLjxGe_Bxanq4Dmv7BJ05xbtGRr6CE-x2SrcdJaPXkjIPbh44UvP2fjNEr_OhXKiMrR4wxmgc-VltLLEP51yXSuUHjZqHng7aNE9Exs3YNFJV1eI5unTjxS1xEeoaGrkvBO_rSBktAw1RHDS7pyXrJhntTjwozJiaIJetSlBixtRZWvfb_rMxZD0zICDtCFWnSgwiT2UxOtCiNKTI-GSasyPMdr9QjmGrx21dNonlksl5dHc_oWMnUYVVn1FtcMx9gHh6ZLmV03Q0XxG-8A80mXAMp7TaRkC1vDI95ycHVavNRqHae--buqY3Q73CTNhFSTy7PNO6GhjcueQVHuGzhmz8RQE-0MybNt4QM1zpA6cbVCV-wepNrBmSKsXjx4IMfYMPrXu1lAD_ORl_N0cw-kiEfz1jvhZbfGotOTKi24LEM5kT2Q'
]);

$response = curl_exec($ch);
if(curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
}
curl_close($ch);

echo json_encode(json_decode($response), JSON_PRETTY_PRINT);
