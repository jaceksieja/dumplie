<?php

namespace Spec\Dumplie\Application\Metadata;

use Dumplie\Application\Metadata\Schema\Field\TextField;
use Dumplie\Application\Metadata\Schema\TypeSchema;
use Dumplie\Application\Metadata\Storage;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class MetadataAccessObjectSpec extends ObjectBehavior
{
    function let(Storage $storage)
    {
        $type = new TypeSchema("product", [
            "sku" => new TextField()
        ]);
        $this->beConstructedWith($storage, $type);
    }
}
