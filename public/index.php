<?php

require __DIR__ . "/../vendor/autoload.php";

use Slim\Factory\AppFactory;
use Slim\Views\PhpRenderer;
use DI\Container;
use Slim\Example\Validator;
use Slim\Middleware\MethodOverrideMiddleware;

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
$app->add(MethodOverrideMiddleware::class);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
});


$app->get('/users', function ($request, $response) {
    $flash = $this->get('flash')->getMessages();

    $users = json_decode($request->getCookieParam('user', json_encode([])), true);
    $search = $request->getQueryParams('term');

    if (!empty($search)) {
        $users = collect($users)->filter(function ($user) use ($search) {
            return str_starts_with(strtolower($user['nickname']), strtolower($search['term'])) !== false;
            })->toArray();
    }

    $params = ['users' => $users, 'flash' => $flash];
    return  $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('get users');


$app->post('/users', function ($request, $response) use ($router) {
    $user = $request->getParsedBodyParam('user');
    $user['id'] = uniqid();
    $validator = new Validator();
    $errors = $validator->validate($user);

    if (count($errors) === 0) {
        $users = json_decode($request->getCookieParam('user', json_encode([])), true);
        $users[] = $user;
        $users = json_encode($users);

        $this->get('flash')->addMessage('success', 'User was added successfully');

        return $response->withHeader('Set-Cookie', "user={$users}")->withRedirect($router->urlFor('get users'), 302);
    }

    $params = [
        'user' => $user, 
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {

    $params = [
        'user' => ['nickname' => '', 'email' => ''],
        'errors' => []
    ];

    return  $this->get('renderer')->render($response, 'users/new.phtml', $params);
})->setName('new user');


$app->get('/users/{id}', function ($request, $response, $args) {    
    $users = json_decode($request->getCookieParam('user', json_encode([])), true);

    $findUser = array_map(function ($user) use ($args, $response) {
        if ($user['id'] === $args['id']) {
            $params = ['id' => $user['id'], 'nickname' => $user['nickname'], 'email' => $user['email']];
            return $this->get('renderer')->render($response, 'users/show.phtml', $params);
        }
    }, $users);

    return $response->withStatus(404);
});

$app->get('/users/{id}/edit', function ($request, $response, $args) {
    $id = $args['id'];

    $users = json_decode($request->getCookieParam('user', json_encode([])), true);

    $findUser = array_map(function ($user) use ($args, $response) {
        if ($user['id'] === $args['id']) {
            $params = ['id' => $user['id'], 'nickname' => $user['nickname'], 'email' => $user['email']];
            return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
        }
    }, $users);

    return $response->withStatus(404);
})->setName('update user form');

$app->patch('/users/{id}', function ($request, $response, $args) use ($router) {
    $updateUser = $request->getParsedBodyParam('users');
    $updateUser['id'] = $args['id'];
    $validator = new Validator();
    $errors = $validator->validate($updateUser);

    if (count($errors) === 0) {
        $users = json_decode($request->getCookieParam('user', json_encode([])), true);
        $currentnew = array_map(function ($user) use ($updateUser) {
            if ($user['id'] === $updateUser['id']) {
                $user['nickname'] = $updateUser['nickname'];
                $user['email'] = $updateUser['email'];
            }
            return $user;
        }, $users);
        $current = json_encode($currentnew);
        file_put_contents($file, $current);

        $this->get('flash')->addMessage('success', 'User was update successfully');

        return $response->withRedirect($router->urlFor('get users'));
    }

    $params = [
        'id' => $id,
        'nickname' => $user['nickname'],
        'email' => $user['email'],
        'errors' => $errors
    ];

    return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);

})->setName('update user');

$app->delete('/users/{id}', function ($request, $response, $args) use ($router) {
    $id = $args['id'];

    $users = json_decode($request->getCookieParam('users', json_encode([])), true);

    $deleteUser = array_filter($users, function ($user) {
        return $user['id'] !== $id;
    });
    $current = json_encode($deleteUser);
    file_put_contents($file, $current);

    $this->get('flash')->addMessage('success', 'Users has been deleted');
    return $response->withRedirect($router->urlFor('get users'));
})->setName('delete user');

$app->get('/session', function ($request, $response) {
    $flash = $this->get('flash')->getMessages();

    $params = ['flash' => $flash, 'session' => $_SESSION];
    return $this->get('renderer')->render($response, 'users/authentication.phtml', $params);
})->setName('session');

$app->post("/session", function ($request, $response) {
    $userData = $request->getParsedBodyParam('user');
    $emailData = $userData['email'];

    $users = json_decode($request->getCookieParam('users', json_encode([])), true);

    foreach ($users as $user) {
        if ($user['email'] === $emailData) {
            $_SESSION['email'] = $emailData;
        }
    }

    if (!isset($_SESSION['email'])) {
        $this->get('flash')->addMessage('false', 'Wrong password or name');
    }

    return $response->withRedirect('/session');
});

$app->delete('/session', function ($request, $response) {
    $_SESSION = [];
    session_destroy();
    return $response->withRedirect('/session');
})->setName('session');

$app->run();
