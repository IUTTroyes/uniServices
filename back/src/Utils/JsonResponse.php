<?php

namespace App\Utils;

use Symfony\Component\HttpFoundation\Response;

abstract class JsonResponse
{

    public static function Error(string $message): Response
    {
        return new Response(
            json_encode(['error' => $message]),
            Response::HTTP_BAD_REQUEST,
            ['Content-Type' => 'application/json']
        );
    }

    public static function Success(string $message, mixed $data = null): Response
    {
        return new Response(
            json_encode(['message' => $message, 'data' => $data !== null ? json_encode($data) : []]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/json']
        );
    }
}
