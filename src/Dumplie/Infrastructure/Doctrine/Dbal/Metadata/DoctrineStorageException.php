<?php

declare (strict_types = 1);

namespace Dumplie\Infrastructure\Doctrine\Dbal\Metadata;

use Dumplie\Application\Metadata\Schema\Type;

class DoctrineStorageException extends \Exception
{
    /**
     * @param string $table
     *
     * @return DoctrineStorageException
     */
    public static function tableAlreadyExists(string $table): DoctrineStorageException
    {
        return new static(sprintf('Table "%s" already exists', $table));
    }

    /**
     * @param Type $type
     *
     * @return DoctrineStorageException
     */
    public static function unableToMapType(Type $type): DoctrineStorageException
    {
        return new static(sprintf('Unable to map type schema "%s" to doctrine field type', (string) $type));
    }
}
