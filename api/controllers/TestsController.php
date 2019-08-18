<?php

use Application\Core\Components\Internet\Http\Response as ApiResponse;

$app->get('/tests', function () use ($app) {

    return $app->apiResponse->success(['tests' => "Hello World"]);
});
