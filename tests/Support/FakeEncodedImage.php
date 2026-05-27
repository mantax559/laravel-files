<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Support;

final class FakeEncodedImage
{
    public function __construct(
        private readonly string $contents
    ) {}

    public function toString(): string
    {
        return $this->contents;
    }
}
