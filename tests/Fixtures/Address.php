<?php

namespace AutoMapper\Bundle\Tests\Fixtures;

class Address
{
    /**
     * @var string|null
     */
    private $city;

    public function setCity(?string $city): void
    {
        $this->city = $city;
    }
}
