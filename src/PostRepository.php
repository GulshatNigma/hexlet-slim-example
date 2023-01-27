<?php

namespace App;

class PostRepository
{
    public function __construct()
    {
        session_start();
        if (!array_key_exists('users', $_SESSION)) {
            $_SESSION['users'] = [];
        }
    }

    public function all()
    {
        return array_values($_SESSION['users']);
    }

    public function find(string $id)
    {
        if (!isset($_SESSION['users'][$id])) {
            throw new \Exception("Wrong post id: {$id}");
        }

        return $_SESSION['users'][$id];
    }

    public function destroy(string $id)
    {
        unset($_SESSION['users'][$id]);
    }

    public function save(array $item)
    {
        if (empty($item['nickname']) || empty($item['email'])) {
            $json = json_encode($item);
            throw new \Exception("Wrong data: {$json}");
        }
        if (!isset($item['id'])) {
            $item['id'] = uniqid();
        }
        $_SESSION['users'][$item['id']] = $item;
    }
}