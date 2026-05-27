<?php

declare(strict_types=1);

namespace Mantax559\LaravelFiles\Tests\Support;

final class FakeImage
{
    public function __construct(
        private int $width = 1,
        private int $height = 1,
        private string $encodedContents = 'encoded-image'
    ) {}

    public function cover(int $width, int $height): self
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    public function encodeUsingFileExtension(string $extension, int $quality): FakeEncodedImage
    {
        return new FakeEncodedImage($this->encodedContents.'-'.$extension.'-'.$quality);
    }

    public function height(): int
    {
        return $this->height;
    }

    public function scale(?int $width = null, ?int $height = null): self
    {
        if (! empty($width)) {
            $this->width = $width;
        }

        if (! empty($height)) {
            $this->height = $height;
        }

        return $this;
    }

    public function width(): int
    {
        return $this->width;
    }
}
