<?php

namespace Fiedsch\SqliteManager;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use PHPUnit\Framework\MockObject\RuntimeException;

/**
 * Class Manager
 * A Helper class for working with SQLite Databases.
 *
 * @package Fiedsch\SqliteManager
 */
class Manager
{
    /**
     * @var string ID_COLUMN_NAME Name of the id column
     */
    const ID_COLUMN_NAME = 'id';

    /**
     * @var string ID_COLUMN_TYPE Type of the id column
     */
    const ID_COLUMN_TYPE = 'INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT';

    /**
     * @var boolean
     */
    const DB_FILE_MUST_EXIST = true;

    /**
     * Manager constructor.
     */
    public function __construct()
    {
        // nothing to do
    }

    /**
     * Connect to a (file based) SQLite Database
     *
     * @param string $dbpath Filepath of the SQLite database
     * @param boolean $dbMustExist specifies wether or not the $dbpath is expected to exist
     * @throws \RuntimeException if $dbMustExist is true and the file at $dbpath does not exist
     * @throws \Doctrine\DBAL\DBALException
     * @return \Doctrine\DBAL\Connection
     */
    public function connectTo($dbpath, $dbMustExist = false)
    {
        $params = [
            'driver' => 'pdo_sqlite',
            'path'   => $dbpath,
        ];
        if ($dbMustExist && !$this->assertDbFile($dbpath)) {
            throw new \RuntimeException("'$dbpath' does not exist");
        }
        $config = new Configuration();
        return DriverManager::getConnection($params, $config);
    }

    /**
     * Assert that the specified file---a SQLIte database---exists.
     * TODO: we might also want to test if an existing file is a SQLite database
     * maybe use file foo.db => "foo.db: SQLite 3.x database, ..."
     *
     * @param string $dbpath Filepath of the SQLite database
     * @return boolean
     */
    public function assertDbFile($dbpath)
    {
        return file_exists($dbpath);
    }

    /**
     * Create the "CREATE TABLE" SQL for $table which's columns are described
     * by $colspec
     *
     * TODO: allow multicolumn UNIQUE constraints
     *
     * @param string $tablename Name of the database table
     * @param array $colspec Specification of the table's columns (name and data type)
     * @return string SQL to create the table
     */
    public function getCreateTableSql($tablename, array $colspec)
    {
        $idcolumn = self::ID_COLUMN_NAME . ' ' . self::ID_COLUMN_TYPE;
        $columns = $this->getColSpecsSql($colspec);
        $constraints = $this->getUniqueConstraintsSql($colspec);
        return sprintf("CREATE TABLE %s %s (%s%s%s)",
            'IF NOT EXISTS', // TODO make optional
            $tablename,
            $idcolumn,
            $columns ? ",$columns" : '',
            $constraints ? ",$constraints" : ''
        );
    }

    /**
     * Get the SQL for the table's columns as specified in $colspecs
     *
     * @param array $colspecs columns specifications
     * @throws \RuntimeException
     * @return string
     */
    protected function getColSpecsSql(array $colspecs)
    {
        $result = [];
        foreach ($colspecs as $colname => $colconfig) {
            if (self::ID_COLUMN_NAME === $colname) {
                throw new \RuntimeException("column name '$colname' is reserved for internal usage");
            }
            $result[] = $this->getColSql($colname, $colconfig);
        }
        return join(',', $result);
    }

    /**
     * Get the SQL for the column $colname as specified in $colsconfig
     *
     * @param string $colname The column's name
     * @param array $colconfig The column's specification
     * @throws \RuntimeException if the configuration is not valid
     * @return null|string|string[]
     */
    protected function getColSql($colname, array $colconfig)
    {
        $configChecker = new ColumnConfguration($colconfig);
        $checkedConfig = $configChecker->getConfiguration();
        if ($configChecker->hasErrors()) {
            throw new \RuntimeException(sprintf("there were errors in your configuration: '%s'",
                json_encode($configChecker->getErrors())
            ));
        }
        return preg_replace("/\s+/", ' ', trim(sprintf("%s %s %s %s %s",
            $colname,
            $checkedConfig['type'],
            $this->getNullContraint($checkedConfig),
            $this->getDefaultSetting($checkedConfig),
            $this->getUniqueContraint($checkedConfig)
        )));
    }

    /**
     * Adds 'NOT NULL' constraint if $colconfig['mandatory'] is true
     *
     * @param array $colconfig The column's specification
     * @return string
     */
    protected function getNullContraint(array $colconfig)
    {
        if (true === $colconfig['mandatory']) {
            return 'NOT NULL';
        }
        return '';
    }

    /**
     * 'DEFAULT ...' constraint if $colconfig['default'] is set, '' otherwise
     *
     * @param array $colconfig The column's specification
     * @return string
     */
    protected function getDefaultSetting(array $colconfig)
    {
        if (!is_null($colconfig['default'])) {
            return sprintf("DEFAULT '%s'", $colconfig['default']);
        }
        return '';
    }

    /**
     * 'UNIQUE' constraint if $colconfig['unique'] is true, '' otherwise
     *
     * @param array $colconfig The column's specification
     * @return string
     */
    protected function getUniqueContraint(array $colconfig)
    {
        if (true === $colconfig['unique']) {
            return 'UNIQUE';
        }
        return '';
    }

    /**
     * Create the UNIQUE constraints as specified in $colspecs
     * See also https://www.sqlite.org/lang_createtable.html (table-constraint)
     *
     * TODO: '...,CONSTRAINT constraint_name UNIQUE (uc_col1, uc_col2, ... uc_col_n)'
     * vs. ',FOO TEXT UNIQUE, bar INTEGER,...'
     * as the former only really makes sense for multi column constraints
     *
     * @param array $colspecs Specifications for the columns
     * @return string
     */
    protected function getUniqueConstraintsSql(array $colspecs)
    {
        return '';
        // TODO: for multicolumn constraints only!
        /*
        return implode(',',
            array_map(
                function($el) {
                    return sprintf('CONSTRAINT unique_%s_constraint UNIQUE (%s)', $el, $el);
                },
                array_keys(array_filter($colspecs, function($el) {
                    return isset($el['unique']) && $el['unique'];
                }))
            )
        );
        */
    }

    /**
     * Create the "ADD COLUMN ..." SQL for $table and $colname
     *
     * @param string $tablename Name of the database table
     * @param string $colname Name of the column to be added
     * @param array $colconfig Configuration for $colname
     * @throws \RuntimeException
     * @retunr string
     */
    public function getAddColumnSql($tablename, $colname, array $colconfig)
    {
        $configChecker = new ColumnConfguration($colconfig);
        $checkedConfig = $configChecker->getConfiguration();
        if ($configChecker->hasErrors()) {
            throw new \RuntimeException(sprintf("there were errors in your configuration: '%s'",
                json_encode($configChecker->getErrors())
            ));
        }
        if (true === $checkedConfig['unique']) {
            throw new \RuntimeException("you can not add a UNIQUE column");
        }
        if (true === $checkedConfig['mandatory'] && is_null($checkedConfig['default'])) {
            throw new \RuntimeException("you can not add a NOT NULL column with default value NULL");
        }
        return sprintf('ALTER TABLE %s ADD COLUMN %s',
            $tablename,
            $this->getColSql($colname, $colconfig)
        );
    }

}
