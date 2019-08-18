<?php
use Phalcon\Events\Event;
use Phalcon\Events\Manager as EventsManager;

// Create a events manager
$eventsManager = new EventsManager();

//$eventsManager->attach(
//    'micro:beforeNotFound',
//    function (Event $event, $app) {
//        $app->response->redirect('/404');
//        $app->response->sendHeaders();
//
//        return $app->response;
//    }
//);

// Bind the events manager to the app
$app->setEventsManager($eventsManager);
