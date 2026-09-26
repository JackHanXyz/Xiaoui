<?php

declare(strict_types=1);

namespace Xiaoui\Http;

/**
 * PSR-15 style middleware.
 */
interface MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response;
}
