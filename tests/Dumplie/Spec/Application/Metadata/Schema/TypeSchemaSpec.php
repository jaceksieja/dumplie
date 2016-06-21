<?php

namespace Spec\Dumplie\Application\Metadata\Schema;

use Dumplie\Application\Exception\Metadata\InvalidArgumentException;
use Dumplie\Application\Metadata\Schema\FieldDefinition;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class TypeSchemaSpec extends ObjectBehavior
{
    function it_required_field_names_to_be_unique(FieldDefinition $definition)
    {
        $this->shouldThrow(InvalidArgumentException::class)
            ->during("__construct", ["product", ["sku" => $definition, "SKU" => $definition]]);
    }

    function it_returns_definitions_except_passed_in_array(FieldDefinition $definition)
    {
        $this->beConstructedWith("product", ["sku" => $definition, "name" => $definition, "description" => $definition]);

        $this->getDefinitions(["sku"])->shouldHaveCount(2);
    }
}
