<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Xiaoui\Validation\Validator;

class ValidationTest extends TestCase
{
    public function testRequiredRule(): void
    {
        $validator = Validator::make(['name' => ''], ['name' => 'required']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors());
    }

    public function testPassesWithValidData(): void
    {
        $validator = Validator::make(
            ['email' => 'foo@example.com'],
            ['email' => 'required|email'],
        );

        $this->assertTrue($validator->passes());
    }

    public function testInRule(): void
    {
        $validator = Validator::make(
            ['status' => 'active'],
            ['status' => 'in:active,inactive'],
        );

        $this->assertTrue($validator->passes());
    }
}
