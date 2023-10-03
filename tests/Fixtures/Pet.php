<?php

namespace AutoMapper\Bundle\Tests\Fixtures;

use Symfony\Component\Serializer\Annotation\DiscriminatorMap;

#[
    DiscriminatorMap(typeProperty: 'type', mapping: [
        'cat' => Cat::class,
        'dog' => Dog::class,
    ])
]
class Pet
{
    /** @var string */
    public $type;
}
