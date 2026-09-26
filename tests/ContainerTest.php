<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Xiaoui\Container\Container;

class ContainerTest extends TestCase
{
    public function testResolvesConcreteClass(): void
    {
        $container = new Container();

        $instance = $container->get(FooService::class);

        $this->assertInstanceOf(FooService::class, $instance);
    }

    public function testAutoWiresDependencies(): void
    {
        $container = new Container();

        $consumer = $container->get(Consumer::class);

        $this->assertInstanceOf(FooService::class, $consumer->service);
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $container = new Container();
        $container->singleton(FooService::class);

        $a = $container->get(FooService::class);
        $b = $container->get(FooService::class);

        $this->assertSame($a, $b);
    }
}

class FooService
{
}

class Consumer
{
    public function __construct(public FooService $service)
    {
    }
}
