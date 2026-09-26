<?php

declare(strict_types=1);

namespace Xiaoui\Http;

/**
 * PSR-15 style request handler.
 */
interface RequestHandlerInterface
{
    public function handle(Request $request): Response;
}
