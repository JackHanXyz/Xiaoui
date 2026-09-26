<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Xiaoui\Routing\RouteCollection;

class RoutingTest extends TestCase
{
    public function testMatchesRouteWithParameters(): void
    {
        $collection = new RouteCollection();
        $collection->add(['GET'], '/users/{id}', 'handler');

        $match = $collection->match('GET', '/users/42');

        $this->assertNotNull($match);
        $this->assertSame('42', $match['params']['id']);
    }

    public function testReturnsNullOnNoMatch(): void
    {
        $collection = new RouteCollection();
        $collection->add(['GET'], '/users', 'handler');

        $this->assertNull($collection->match('POST', '/users'));
        $this->assertNull($collection->match('GET', '/nope'));
    }
}
