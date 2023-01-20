<?php

require __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use DI\Container;
use Slim\Example\Validator;

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


$app->get('/users', function ($request, $response) {
    $file = 'public/users.json';
    $users = json_decode(file_get_contents($file), true);
    $params = ['users' => $users];
    return  $this->get('renderer')->render($response, 'users/index.phtml', $params);
});


$app->post('/users', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');
    $id = uniqid();
    $validator = new Validator();
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        $params = ['nickname' => $user['nickname'], 'email' => $user['email'], 'id' => $id,];

        $file = 'public/users.json';
        $current = json_decode(file_get_contents($file), true);
        $current[] = $params;
        $current = json_encode($current) . "\n";
        file_put_contents($file, $current);

        return $response->withRedirect('/users', 302);
    }

    $params = [
        'id' => $id,
        'nickname' => $user['nickname'],
        'email' => $user['email'],
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response->withStatus(422), 'users/newUser.phtml', $params);
});


$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => ''],
        'errors' => []
    ];
    return  $this->get('renderer')->render($response, 'users/newUser.phtml', $params);
});

$app->run();
