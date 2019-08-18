<?php

$app->get('/tests', function () use ($app) {

    return $app->apiResponse->success(['tests' => "Hello World"]);
});
