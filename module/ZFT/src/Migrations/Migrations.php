<?php

namespace ZFT\Migrations;

use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Metadata\MetadataInterface;
use Zend\Db\Metadata\Object\TableObject;
use Zend\Db\Metadata\Source\Factory as MetadataFactory;

class Migrations
{
    const MINIMUM_SCHEMA_VERSION = 1;
    const INI_TABLE = 'ini-dev';

    /** @var Adapter */
    private $adapter;

    /** @var PlatformInterface */
    private $platform;

    /** @var MetadataInterface */
    private $metadata;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->platform = $adapter->getPlatform();
        $this->metadata = MetadataFactory::createSourceFromAdapter($adapter);
    }

    public function needsUpdate()
    {
        return ($this->getVersion() < self::MINIMUM_SCHEMA_VERSION);
    }

    private function getVersion()
    {
        $tables = $this->metadata->getTables();

        $iniTable = array_filter($tables, function (TableObject $table) {
            return strcmp($table->getName(), self::INI_TABLE) === 0;
        });

        if (count($iniTable) === 0) {
            return 0;
        }

        $sql = 'SELECT value FROM ' . $this->platform->quoteIdentifier(self::INI_TABLE) . ' WHERE option = :option';

        $result = $this->adapter->query($sql, ['option' => 'ZftSchemaVersion']);
        $result = $result->toArray();
        $version = $result[0]['value'];

        return $version;
    }
}