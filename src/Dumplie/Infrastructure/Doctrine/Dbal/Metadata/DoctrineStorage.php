<?php

declare(strict_types = 1);

namespace Dumplie\Infrastructure\Doctrine\Dbal\Metadata;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema as DBALSchema;
use Dumplie\Application\Metadata\Schema;
use Dumplie\Application\Metadata\Schema\TypeSchema;
use Dumplie\Application\Metadata\Storage;

class DoctrineStorage implements Storage
{
    const TABLE_PREFIX = 'metadata';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var TypeRegistry
     */
    private $typeRegistry;

    /**
     * DoctrineStorage constructor.
     *
     * @param Connection   $connection
     * @param TypeRegistry $typeRegistry
     */
    public function __construct(Connection $connection, TypeRegistry $typeRegistry)
    {
        $this->connection = $connection;
        $this->typeRegistry = $typeRegistry;
    }

    /**
     * @param Schema $schema
     *
     * @throws DoctrineStorageException
     */
    public function create(Schema $schema)
    {
        $currentSchema = $this->connection->getSchemaManager()->createSchema();

        foreach ($schema->types() as $type) {
            $tableName = $this->tableName($schema->name(), $type->name());

            if ($currentSchema->hasTable($tableName)) {
                throw DoctrineStorageException::tableAlreadyExists($tableName);
            }

            $this->createTable($currentSchema, $tableName, $type);
        }

        $queries = $currentSchema->toSql($this->connection->getDatabasePlatform());
        $this->executeQueries($queries);
    }

    /**
     * @param Schema $schema
     */
    public function alter(Schema $schema)
    {
        $currentSchema = $this->connection->getSchemaManager()->createSchema();
        $targetSchema = clone $currentSchema;

        foreach ($schema->types() as $type) {
            $tableName = $this->tableName($schema->name(), $type->name());

            if ($targetSchema->hasTable($tableName)) {
                $targetSchema->dropTable($tableName);
            }

            $this->createTable($targetSchema, $tableName, $type);
        }

        $queries = $currentSchema->getMigrateToSql($targetSchema, $this->connection->getDatabasePlatform());
        $this->executeQueries($queries);
    }

    /**
     * @param Schema $schema
     */
    public function drop(Schema $schema)
    {
        $currentSchema = $this->connection->getSchemaManager()->createSchema();
        $targetSchema = clone $currentSchema;

        foreach ($schema->types() as $type) {
            $targetSchema->dropTable($this->tableName($schema->name(), $type->name()));
        }

        $queries = $currentSchema->getMigrateToSql($targetSchema, $this->connection->getDatabasePlatform());
        $this->executeQueries($queries);
    }

    /**
     * Needs to return metadata in following format:
     * [
     *   'id' => 'e94e4c36-3ffb-49b6-b8a5-973fa5c4aee6',
     *   'sku' => 'DUMPLIE_SKU_1',
     *   'name' => 'Product name'
     * ]
     * Key 'id' is required.
     *
     * @param string $schema
     * @param string $typeName
     * @param array  $criteria
     *
     * @return array
     */
    public function findBy(string $schema, string $typeName, array $criteria = []) : array
    {
        $builder = $this->connection->createQueryBuilder();
        $builder->select('*');
        $builder->from($this->tableName($schema, $typeName));

        foreach ($criteria as $field => $value) {
            $builder->andWhere(sprintf('%1$s = :%1$s', $field));
            $builder->setParameter($field, $value);
        }

        return $builder->execute()->fetchAll();
    }

    /**
     * @param string $schema
     * @param string $typeName
     * @param string $id
     * @param array  $metadata
     */
    public function save(string $schema, string $typeName, string $id, array $metadata = [])
    {
        if ($this->has($schema, $typeName, $id)) {
            $this->update($schema, $typeName, $id, $metadata);

            return;
        }

        $this->insert($schema, $typeName, $id, $metadata);
    }

    /**
     * @param string $schema
     * @param string $typeName
     * @param string $id
     *
     * @return bool
     */
    public function has(string $schema, string $typeName, string $id) : bool
    {
        return !!$this->connection->createQueryBuilder()
            ->select('id')
            ->from($this->tableName($schema, $typeName))
            ->where('id = :id')
            ->setParameter('id', $id)
            ->execute()
            ->fetchColumn();
    }

    /**
     * @param string $schema
     * @param string $typeName
     * @param string $id
     */
    public function delete(string $schema, string $typeName, string $id)
    {
        $this->connection->createQueryBuilder()
            ->delete($this->tableName($schema, $typeName))
            ->where('id = :id')
            ->setParameter('id', $id);
    }

    /**
     * @param string $schemaName
     * @param string $typeName
     *
     * @return string
     */
    private function tableName(string $schemaName, string $typeName): string
    {
        return implode('_', [self::TABLE_PREFIX, $schemaName, $typeName]);
    }

    /**
     * @param DBALSchema $schema
     * @param string     $tableName
     * @param TypeSchema $type
     *
     * @throws DoctrineStorageException
     */
    private function createTable(DBALSchema $schema, string $tableName, TypeSchema $type)
    {
        $table = $schema->createTable($tableName);
        $table->addColumn('id', 'string');
        $table->setPrimaryKey(['id']);

        foreach ($type->getDefinitions(['id']) as $field => $definition) {
            $this->typeRegistry->map($table, $field, $definition);
        }
    }

    /**
     * @param string $schema
     * @param string $typeName
     * @param string $id
     * @param array  $metadata
     */
    private function insert(string $schema, string $typeName, string $id, array $metadata)
    {
        $builder = $this->connection->createQueryBuilder();

        $builder->insert($this->tableName($schema, $typeName));
        $builder->setValue('id', $builder->createNamedParameter($id));

        foreach ($metadata as $field => $value) {
            $builder->setValue($field, $builder->createNamedParameter($value));
        }

        $builder->execute();
    }

    /**
     * @param string $schema
     * @param string $typeName
     * @param string $id
     * @param array  $metadata
     */
    private function update(string $schema, string $typeName, string $id, array $metadata)
    {
        $builder = $this->connection->createQueryBuilder();

        $builder->update($this->tableName($schema, $typeName));
        $builder->where('id = :id');
        $builder->setParameter('id', $id);

        foreach ($metadata as $field => $value) {
            $builder->set($field, $builder->createNamedParameter($value));
        }

        $builder->execute();
    }

    /**
     * @param array $queries
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function executeQueries(array $queries)
    {
        foreach ($queries as $query) {
            $this->connection->prepare($query)->execute();
        }
    }
}
