<?php

namespace ZFT\Migrations;

use Faker;
use Zend\Console\Charset\Utf8;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Adapter\Platform\PlatformInterface;
use Zend\Db\Metadata\MetadataInterface;
use Zend\Db\Metadata\Object\TableObject;
use Zend\Db\Metadata\Source\Factory as MetadataFactory;
use Zend\Db\Sql\Ddl\Constraint\PrimaryKey;
use Zend\Db\Sql\Insert;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Ddl;
use Zend\Db\Sql\Where;
use Zend\EventManager\EventManager;

class Migrations {

    const MINIMUM_SCHEMA_VERSION = 2;
    const INI_TABLE = 'ini';

    /** @var  Adapter */
    private $adapter;

    /** @var  PlatformInterface */
    private $platform;

    /** @var  MetadataInterface */
    private $metadata;

    /** @var  EventManager */
    private $eventManager;

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->platform = $adapter->getPlatform();
        $this->metadata = MetadataFactory::createSourceFromAdapter($adapter);

        $this->eventManager = new EventManager();
    }

    public function needsUpdate() {
        return ($this->getVersion() < self::MINIMUM_SCHEMA_VERSION);
    }

    private function execute(Ddl\SqlInterface $ddl) {
        $sql = new Sql($this->adapter);
        $sqlString = $sql->buildSqlString($ddl);

        $this->adapter->query($sqlString, Adapter::QUERY_MODE_EXECUTE);
    }

    protected function getVersion() {
        $tables = $this->metadata->getTables('app');

        $iniTable = array_filter($tables, function (TableObject $table) {
            return strcmp($table->getName(), self::INI_TABLE) === 0;
        });

        if (count($iniTable) === 0) {
            return 0;
        }

        $sql = new Sql($this->adapter);
        $select = $sql->select();
        $select->from(self::INI_TABLE);
        $select->where(['option' => 'ZftSchemaVersion']);

        $statement = $sql->prepareStatementForSqlObject($select);
        $result = $statement->execute();
        $result = $result->current();
        $version = $result['value'];


//        $sql = 'SELECT value '.
//        'FROM '.$this->platform->quoteIdentifier(self::INI_TABLE)." ".
//        'WHERE '.$this->platform->quoteIdentifier('option').' = :option';

//        $result = $this->adapter->query($sql, ['option' => 'ZftSchemaVersion']);
//        $result = $result->toArray();
//        $version = $result[0]['value'];

        return $version;

    }

    public function run() {
        $migrationsStartEvent = new MigrationsEvent();
        $migrationsStartEvent->setName(MigrationsEvent::MIGRATIONS_START);
        $migrationsStartEvent->setTarget($this);
        $migrationsStartParams['to'] = $this->getTargetVersion();

        $migrationCalss = new \ReflectionClass(Migrations::class);
        $methods = $migrationCalss->getMethods(\ReflectionMethod::IS_PROTECTED);

        $updates = [];
        array_walk($methods, function(\ReflectionMethod $method) use (&$updates) {
            $version = substr($method->getName(), strpos($method->getName(), '_')+1);
            $version = (int) $version;
            $updates[$version] = $method->getName();
        });

        ksort($updates);

        $currentVersion = (int) $this->getVersion();
        $migrationsStartParams['from'] = $currentVersion;
        $migrationsStartEvent->setParams($migrationsStartParams);
        $this->eventManager->triggerEvent($migrationsStartEvent);

        for($v = $currentVersion+1; $v <= self::MINIMUM_SCHEMA_VERSION; $v++) {
            $update = $updates[$v];
            $this->{$update}();

            $this->setVersion($v);
        }

        return;
    }

    protected function getTargetVersion() {
        return self::MINIMUM_SCHEMA_VERSION;
    }

    protected function setVersion($version){
        $sql = new Sql($this->adapter);
        $schemaVersionUpdate = $sql->update();
        $schemaVersionUpdate->table(self::INI_TABLE);
        $schemaVersionUpdate->set(['value' => $version]);

        $schemaVersionRow = new Where();
        $schemaVersionRow->equalTo('option', 'ZftSchemaVersion');

        $schemaVersionStatement = $sql->prepareStatementForSqlObject($schemaVersionUpdate);
        $schemaVersionStatement->execute();

    }

    public function attach($eventName, callable $listener) {
        $this->eventManager->attach($eventName, $listener);
    }

    protected function update_001() {
        $iniTable = new Ddl\CreateTable(self::INI_TABLE);

        $id = new Ddl\Column\Integer('id');
        $id->setOption('autoincrement', true);
        $option = new Ddl\Column\Varchar('option');
        $value  = new Ddl\Column\Varchar('value');

        $option->setLength(50);
        $value->setLength(50);

        $iniTable->addColumn($id);
        $iniTable->addColumn($option);
        $iniTable->addColumn($value);

        $iniTable->addConstraint(new PrimaryKey('id'));

        $this->execute($iniTable);


        $sql = new Sql($this->adapter);
        $insertInitialVersion = $sql->insert();
        $insertInitialVersion->into(self::INI_TABLE);
//        $insertInitialVersion->columns(array('option', 'value'));
//        $insertInitialVersion->values(array('ZftSchemaVersion', 1));
        $values = [
            'option' => 'ZftSchemaVersion',
            'value' => 1
        ];
        $insertInitialVersion->columns(array_keys($values));
        $insertInitialVersion->values(array_values($values));

        $insertStatement = $sql->prepareStatementForSqlObject($insertInitialVersion);
        $insertStatement->execute();
    }

    protected function update_002() {
        $usersTable = new Ddl\CreateTable('users');

        // mysql version
        $id = new Ddl\Column\Integer('id');
        $id->setOption('autoincrement', true);
        $firstName = new Ddl\Column\Varchar('first_name');
        $surName = new Ddl\Column\Varchar('surname');
        $patronymic = new Ddl\Column\Varchar('patronymic');
        $email = new Ddl\Column\Varchar('email');

        $firstName->setLength(50);
        $surName->setLength(50);
        $patronymic->setLength(50);
        $email->setLength(50);

        // mysql version
        $usersTable->addColumn($id);
        $usersTable->addColumn($firstName);
        $usersTable->addColumn($surName);
        $usersTable->addColumn($patronymic);
        $usersTable->addColumn($email);

        $usersTable->addConstraint(new PrimaryKey('id'));

        $this->execute($usersTable);

//        $this->adapter->query('ALTER TABLE users ADD COLUMN id SERIAL PRIMARY KEY', Adapter::QUERY_MODE_EXECUTE);

        $faker = new Faker\Generator();
        $faker->addProvider(new Faker\Provider\ru_RU\Person($faker));
        $faker->addProvider(new Faker\Provider\ru_RU\Internet($faker));

        $insert = new Insert('users');

        $sql = new Sql($this->adapter);
        for($i = 0; $i < 10; $i++) {
            $name = explode(' ', $faker->name);
            $insert->values([
                'first_name' => $name[0],
                'surname' => $name[2],
                'patronymic' => $name[1],
                'email' => $faker->email
            ]);

            $insertStatement = $sql->prepareStatementForSqlObject($insert);
            $insertStatement->execute();
        }

    }

}
