<?php

declare(strict_types=1);

namespace App\Controllers;

use Xiaoui\Http\Request;
use Xiaoui\Http\Response;

class HomeController
{
    public function index(Request $request): Response
    {
        return Response::json([
            'framework' => 'Xiaoui',
            'message' => 'Hello, World!',
            'time' => date('c'),
        ]);
    }

    /**
     * Demo of a route parameter.
     */
    public function show(Request $request, string $id): Response
    {
        return Response::json([
            'id' => $id,
            'requested_at' => date('c'),
        ]);
    }

    /**
     * Demo of JSON input handling.
     */
    public function store(Request $request): Response
    {
        return Response::json(
            ['received' => $request->json()],
            Response::HTTP_CREATED,
        );
    }
}
