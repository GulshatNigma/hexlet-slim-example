<?php

require __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
});

/*$app->get('/users', function ($request, $response) {
    return $response->withStatus(302)->write('GET /users');
});*/

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->get('/users', function ($request, $response) {
    $users = ['mike', 'mishel', 'adel', 'keks', 'kamila'];
    $search = $request->getQueryParams('term');
    $filterUsers = collect($users)->filter(function ($user) use ($search) {
        return str_contains($user, $search['term']) !== false;
    })->toArray();
    $params = ['users' => $filterUsers];
    return  $this->get('renderer')->render($response, 'users/index.phtml', $params);
});

$app->run();
