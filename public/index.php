<?php

require __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use DI\Container;
use Slim\Example\Validator;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});


AppFactory::setContainer($container);
$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
});


$app->get('/users', function ($request, $response) {

    $flash = $this->get('flash')->getMessages();
    $file = 'public/users.json';
    $users = json_decode(file_get_contents($file), true);
    $params = ['users' => $users, 'flash' => $flash];
    return  $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('get users');


$app->post('/users', function ($request, $response) use ($router) {
    $user = $request->getParsedBodyParam('user');
    $id = uniqid();
    $validator = new Validator();
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        $params = ['nickname' => $user['nickname'], 'email' => $user['email'], 'id' => $id];

        $file = 'public/users.json';
        $current = json_decode(file_get_contents($file), true);
        $current[] = $params;
        $current = json_encode($current) . "\n";
        file_put_contents($file, $current);

        $this->get('flash')->addMessage('success', 'User was added successfully');

        return $response->withRedirect($router->urlFor('get users'), 302);
    }

    $params = [
        'id' => $id,
        'nickname' => $user['nickname'],
        'email' => $user['email'],
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'users/newUser.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => ''],
        'errors' => []
    ];

    return  $this->get('renderer')->render($response, 'users/newUser.phtml', $params);
})->setName('new user');


$app->get('/users/{id}', function ($request, $response, $args) {
    $file = 'public/users.json';
    $users = json_decode(file_get_contents($file), true);
    $findUser = array_map(function ($user) use ($args, $response) {
        if ($user['id'] === $args['id']) {
            $params = ['id' => $user['id'], 'nickname' => $user['nickname'], 'email' => $user['email']];
            return $this->get('renderer')->render($response, 'users/show.phtml', $params);
        }
    }, $users);

    return $response->withStatus(404);
})->setName('new user');

$app->run();
