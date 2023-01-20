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

$app->post('/users', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');
    $id = uniqid();
    $errors = validate($user);

    $params = [
        'id' => $id,
        'nickname' => $user['nickname'],
        'email' => $user['email'],
        'errors' => $errors
    ];

    if (count($errors) === 0) {
        $file = 'public/users.json';
        $current = file_get_contents($file);
        $current .= json_encode($params) . "\n";
        file_put_contents($file, $current);

        return $response->withRedirect('/users', 302);
    }

    return $response->withStatus(422)->write(json_encode($errors));
});

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['nickname' => '', 'email' => ''],
        'errors' => []
    ];
    return  $this->get('renderer')->render($response, 'users/newUser.phtml', $params);
});
$app->run();

function validate($user)
{
    $errors = [];

    if (empty($user['nickname'])) {
        $errors['nickname'] = "Can't be blank";
    }

    if (empty($user['email'])) {
        $errors['email'] = "Can't be blank";
    }

    return $errors;
}
