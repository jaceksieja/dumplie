<?php

declare (strict_types = 1);

namespace Dumplie\Metadata\Tests\Integration\InMemory;

use Dumplie\Metadata\Infrastructure\InMemory\InMemoryStorage;
use Dumplie\Metadata\Storage;
use Dumplie\Metadata\Tests\Integration\Generic\AssociationTypeTestCase;

class AssociationTypeTest extends AssociationTypeTestCase
{
    public function createStorage() : Storage
    {
        return new InMemoryStorage();
    }
}
