<?php

namespace Fiedsch\SqliteManager\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Assert;
use Fiedsch\SqliteManager\Manager;
use Doctrine\DBAL\Connection;

/**
 * Class ManagerTest
 *
 * @package Fiedsch\SqliteManager\Tests
 */
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

    /**
     * Make sure column specifications that make no sense
     * causes an exception to be thrown.
     */
    protected function setUp()
    {
        $this->manager = new Manager();
        register_shutdown_function(function() {
            if (file_exists($this->dbpath)) {
                unlink($this->dbpath);
            }
        });
    }

    /**
     *
     */
    protected function tearDown()
    {
        // nothing to do at the moment
    }

    /**
     * If we do not set the optional parameter of connectTo() to true
     * the existance of the database file does will not be checked
     * and connecting to a SQLite database without performing any
     * queries will not create the database file.
     */
    public function testGetConnection()
    {
        $connection = $this->manager->connectTo($this->dbpath);
        Assert::assertInstanceOf(Connection::class, $connection);
    }

    /**
     * Same as testGetConnection() but this time with check for
     * existance of the database file--which does not exist.
     */
    public function testGetConnectionWithException()
    {
        $this->expectException(\RuntimeException::class);
        $this->manager->connectTo($this->dbpath, Manager::DB_FILE_MUST_EXIST);
    }

    /**
     * As a side effect of this test the SQLite database file will be finally
     * created. The file will be removed again in a shutdown function---see setUp().
     *
     * Tests that require this file to exist have to set '@ depends testCreateTable'!
     */
    public function testCreateTable()
    {
        $connection = $this->manager->connectTo($this->dbpath);
        Assert::assertInstanceOf(Connection::class, $connection);
        Assert::assertFalse($this->manager->assertDbFile($this->dbpath));
        $columns = [
            'bar' => [
                'type' => 'TEXT',
            ],
        ];
        $expected = $this->manager->getCreateTableSql('foo', $columns);
        $connection->executeQuery($expected);
        Assert::assertTrue($this->manager->assertDbFile($this->dbpath));
    }

    /**
     * The column specifications have to make sense.
     */
    public function testInvalidColumsSpecification()
    {
        $columns = [
            'bar' => [
                'type'      => 'TEXT',
                'mandatory' => true,
                'default'   => '42',
                'unique'    => true,
            ],
        ];
        // you can not have a unique column with a default value
        $this->expectException(\RuntimeException::class);
        $this->manager->getCreateTableSql('foo', $columns);
    }

    /**
     * The column specifications have to make sense (II).
     */
    public function testInvalidColumsSpecificationInCreateTablePart2()
    {
        $columns = [
            'bar' => [
                'type'      => 'TEXT',
                'mandatory' => false,
                'default'   => '42',
                'unique'    => true,
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->manager->getCreateTableSql('foo', $columns);
    }

    /**
     * Invalid column type specifications have to cause an exception to be
     * thrown.
     * We only allow 'INTEGER', 'TEXT', 'BLOB', 'REAL' or 'NUMERIC'
     * see https://www.sqlite.org/datatype3.html
     */
    public function testInvalidColumnTypeInColspec()
    {
        $colspec = [
            'foo' => [
                'type' => 'VARCHAR(10)',
            ],
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
                'type' => 'TEXT',
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->manager->getCreateTableSql('foo', $colspec);
    }

    /**
     * If we set the optional parameter to true connectTo() will throw
     * a \RuntimeException if the database file does not exist.
     */
    public function testGetCreateTableSql()
    {
        $tablename = 'foobar';
        $colspec = [
            'bar' => [
                'type'   => 'TEXT',
                'unique' => true,
            ],
            'baz' => [
                'type'      => 'INTEGER',
                'unique'    => false,
                'mandatory' => true,
                'default'   => 42
             ],
        ];
        $expected = 'CREATE TABLE IF NOT EXISTS foobar';
        $expected .= ' (';
        $expected .= 'id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT';
        $expected .= ',bar TEXT UNIQUE';
        $expected .= ",baz INTEGER NOT NULL DEFAULT '42'";
        //$expected .= ',CONSTRAINT unique_barbaz_constraint UNIQUE (bar,baz)';
        $expected .= ')';
        Assert::assertEquals($expected, $this->manager->getCreateTableSql($tablename, $colspec));
    }

    /**
     *
     */
    public function testGetAddColumnSql()
    {
        $colconfig = [
            'type'      => 'REAL',
            'mandatory' => true,
            'default'   => 1.5,
        ];
        $sql = $this->manager->getAddColumnSql('tablename', 'colname', $colconfig);
        $expected = "ALTER TABLE tablename ADD COLUMN colname REAL NOT NULL DEFAULT '1.5'";
        Assert::assertEquals($expected, $sql);

        // you can not add a UNIQUE column
        $colconfig = [
            'type'      => 'REAL',
            'unique'    => true,
        ];
        $this->expectException(\RuntimeException::class);
        $this->manager->getAddColumnSql('tablename', 'colname', $colconfig);
    }

    /**
     * Add a column with default value to the table and insert a row.
     * Then check if we were successful.
     *
     * Make sure, these tests run before this one:
     * @depends testCreateTable
    */
    public function testAddColumnSql()
    {
        $colconfig = [
            'type'      => 'REAL',
            'mandatory' => true,
            'default'   => '42',
        ];
        $sql = $this->manager->getAddColumnSql('foo', 'newcol', $colconfig);
        $connection = $this->manager->connectTo($this->dbpath);
        $connection->executeUpdate($sql);
        $connection->executeUpdate("INSERT INTO foo (bar) VALUES ('bar')");
        $result = $connection->query("SELECT * FROM foo");
        $expectedrow = [
            'id'     => 1,
            'bar'    => 'bar',
            'newcol' => 42.0,
        ];
        Assert::assertEquals($expectedrow, $result->fetch());
    }

}
