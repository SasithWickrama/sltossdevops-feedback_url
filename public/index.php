<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Selective\BasePath\BasePathMiddleware;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/db.php';

date_default_timezone_set('Asia/Kolkata');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__. '/../');
$dotenv->load();

$app = AppFactory::create();

// Add Slim routing middleware
$app->addRoutingMiddleware();

// Set the base path to run the app in a subdirectory.
// This path is used in urlFor().
$app->add(new BasePathMiddleware($app));

$app->addErrorMiddleware(true, true, true);

/*
$app->get('/',function(Request $request, Response $response){
    $response->getBody()->write("Hello, World!");
    return $response;
});*/

require __DIR__ . '/../routes/url.php';
require __DIR__ . '/../routes/customer360/cusapi.php';

 $app->any('{route:.*}', function(Request $request, Response $response) {
        $response = $response->withStatus(404, 'Method not found');
        return $response;
    });

$app->run();
