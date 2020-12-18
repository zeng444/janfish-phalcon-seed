<?php
/**
 * Not found handler
 */
$app->notFound(function () use ($app) {
    //跨域的OPTIONS设置
    $app->response->setHeader("Content-type", "text/html; charset=utf-8");
    $app->response->setHeader("Access-Control-Allow-Origin", '*');
    $app->response->setHeader("Access-Control-Allow-Methods", 'GET, POST, OPTIONS, PUT, DELETE');
    $app->response->setHeader("Access-Control-Allow-Headers", 'Access-Token,Content-Type,Access-Control-Request-Method');
    if ($app->request->isOptions()) {
        $app->response->setStatusCode('200', "OK");
    } else {
        $app->response->setStatusCode(Application\Core\Components\Internet\Http\Response::HTTP_NOT_FOUND_CODE, "Not Found");
        $app->response->setJsonContent($app->apiResponse->error("不存在的接口！"));
    }
    $app->response->send();
});

/**
 * 路由REST请求
 */
$request = $app->getService("request");
if ($_url = $request->getQuery("_url")) {
    $pathInfo = explode("/", $_url);
} else {
    $uri = parse_url($request->getURI());
    $pathInfo = explode("/", $uri["path"]);
}
if (isset($pathInfo[1])) {
    $filename = sprintf('%s'.'controllers/%sController.php', API_PATH, ucfirst($pathInfo[1]));
    if (file_exists($filename)) {
        include_once $filename;
    }
    $app->get('/', function () use ($app) {
        //        echo $app['view']->render('index');
        return $app->apiResponse->success([
            "version" => "1.0",
            "timestamp" => (string)time(),
            'pm' => $app->config->application->name
        ]);
    });
}

/**
 * 请求后执行
 */
$app->after(function () use ($app) {
    //跨域的OPTIONS设置
    //        $app->response->setStatusCode($app->apiResponse->getHttpCode(), "OK");
    $app->response->setHeader("Content-type", "text/html; charset=utf-8");
    $app->response->setHeader("Access-Control-Allow-Origin", '*');
    $app->response->setHeader("Access-Control-Allow-Methods", 'GET, POST, OPTIONS, PUT, DELETE');
    $app->response->setHeader("Access-Control-Allow-Headers", 'Access-Token,Content-Type,Access-Control-Request-Method');

    $returnValue = $app->getReturnedValue();
    if (is_array($returnValue)) {
        $app->response->setJsonContent($returnValue);
        $jsonp = $app->request->get("callback");
        if ($jsonp) {
            $app->response->setContent($jsonp.'('.$app->response->getContent().')');
        }
    } else {
        $app->response->setContent($returnValue);
    }

    $app->response->send();
});
