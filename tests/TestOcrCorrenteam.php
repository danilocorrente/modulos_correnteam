<?php

use Danilocorrente\ModulosCorrenteam\Services\ApiClient;

require __DIR__ . '/../vendor/autoload.php';

$client = new ApiClient();

$response = $client->ocrCnh(__DIR__ . '/../images/cnh_placeholder.jpg');


print_r($response);
