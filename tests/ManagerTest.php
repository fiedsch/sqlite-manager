<?php

namespace Fiedsch\SqliteManager\Tests;

use PHPUnit\Framework\TestCase;
use Fiedsch\SqliteManager\Manager;
use Doctrine\DBAL\Connection;

class ManagerTest extends TestCase
{

    /**
     * @var \Fiedsch\SqliteManager\Manager
     */
    protected $manager;

    /**
     * @var string
     */
    protected $dbpath = __DIR__ . '/assets/test.db';

    protected function setUp()
    {
        $this->manager = new Manager();
        register_shutdown_function(function() {
            if(file_exists($this->dbpath)) {
                unlink($this->dbpath);
            }
        });
    }

    protected function tearDown()
    {
        // nothing to do at the moment
    }

    /*
     * If we do not set the optional parameter to true connectTo()
     * the existance of the database file does will not be checked
     * and connecting to a SQLite database without performing any
     * queries will not creaate the database file
     */
    public function testGetConnection()
    {
        $connection = $this->manager->connectTo($this->dbpath);
        $this->assertInstanceOf(Connection::class, $connection);
    }

    /*
     * If we set the optional parameter to true connectTo() will throw
     * a \RuntimeException if the database file does not exist.
     */
    public function testGetConnectionWithException()
    {
        $this->expectException(\RuntimeException::class);
        $this->manager->connectTo($this->dbpath, Manager::DB_FILE_MUST_EXIST);
    }

    /*
     * This test creates a table in the database causing the SQLite
     * database file to be finally created. The file will be removed
     * in a shutdown function---see setUp().
     */
    public function testCreateTable()
    {
        $connection = $this->manager->connectTo($this->dbpath);
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertFalse($this->manager->assertDbPath($this->dbpath));
        $colspec = [
            'bar' => [
                    'type' => 'TEXT'
            ]
        ];
        $expected = $this->manager->getCreateTableSql('foo', $colspec);
        $connection->executeQuery($expected);
        $this->assertTrue($this->manager->assertDbPath($this->dbpath));
    }

    /**
     * Invalid column type specifications shall throw an exception.
     * We only allow 'INTEGER', 'TEXT', 'BLOB', 'REAL' or 'NUMERIC'
     * see https://www.sqlite.org/datatype3.html
     */
    public function testInvalidColumnTypeInColspec()
    {
        $colspec = [
            'foo' => [
                'type' => 'VARCHAR(10)'
            ]
        ];
        $this->expectException(\RuntimeException::class);
        $this->manager->getCreateTableSql('foo', $colspec);
    }

    /**
     * As we always add a column `id` for internal usage the column name
     * `id` is not allowed in $colspec. An exception will be thrown.
     */
    public function testIdIsNotAllowedInColspec()
    {
        $colspec = [
            'id' => [
                'type' => 'TEXT'
            ]
        ];
        $this->expectException(\RuntimeException::class);
        $this->manager->getCreateTableSql('foo', $colspec);
    }

    public function testGetCreateTableSql()
    {
        $tablename = 'foobar';
        $colspec = [
            'bar' => [
                'type' => 'TEXT'
            ],
            'baz' => [
                'type' => 'INTEGER',
                'unique' => true,
            ]
        ];
        $expected = 'CREATE TABLE IF NOT EXISTS foobar ';
        $expected .= '(id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,';
        $expected .= 'bar TEXT,baz INTEGER';
        $expected .= ',CONSTRAINT unique_baz_constraint UNIQUE (baz))';
        $this->assertEquals($expected, $this->manager->getCreateTableSql($tablename, $colspec));
    }

}
