<?php

namespace Fiedsch\SqliteManager;

use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Configuration;
use PHPUnit\Framework\MockObject\RuntimeException;

/**
 * Class Manager
 *
 * @package Fiedsch\SqliteManager
 *
 *
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
     * @param string $dbpath
     * @param boolean $dbMustExist
     * @throws \RuntimeException
     * @throws \Doctrine\DBAL\DBALException
     * @return \Doctrine\DBAL\Connection
     */
    public function connectTo($dbpath, $dbMustExist = false)
    {
        $params = [
            'driver' => 'pdo_sqlite',
            'path'   => $dbpath,
        ];
        if ($dbMustExist && !$this->assertDbPath($dbpath)) {
            throw new \RuntimeException("'$dbpath' does not exist");
        }
        $config = new Configuration();
        return DriverManager::getConnection($params, $config);
    }

    /**
     * Assert that the specified file---a SQLIte database---exists.
     * TODO: we might also want to test if an existing file is a SQLite database
     *
     * @param string $dbpath
     * @return boolean
     */
    public function assertDbPath($dbpath)
    {
        return file_exists($dbpath);
    }

    /**
     * Create the "CREATE TABLE" SQL for $table which's columns are described be $colspec
     *
     * TODO: allow 'NOT NULL' constraint
     * TODO: allow multicolumn UNIQUE constraints
     *
     * @param string $tablename Name of the database table
     * @param array $colspec Specification of the table's columns (name and data type)
     * @return string SQL to create the table
     */
    public function getCreateTableSql($tablename, $colspec)
    {
        $idcolumn = self::ID_COLUMN_NAME . ' ' . self::ID_COLUMN_TYPE;
        $columns = $this->getColSpecsSql($colspec);
        $constraints = $this->getUniqueConstraintsSql($colspec);
        return sprintf("CREATE TABLE %s %s (%s%s%s)",
            'IF NOT EXISTS', // TODO optional
            $tablename,
            $idcolumn,
            $columns ? ",$columns" : '',
            $constraints ? ",$constraints" : ''
        );
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
    protected function getUniqueConstraintsSql($colspecs)
    {
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
    }

    /**
     * @param array $colspecs columns specifications
     * @throws \RuntimeException
     * @return string
     */
    protected function getColSpecsSql($colspecs)
    {
        $result = [];
        foreach ($colspecs as $colname => $colconfig) {
            if (self::ID_COLUMN_NAME === $colname) {
                throw new \RuntimeException("column name '$colname' is reserved for internal usage");
            }
            $result[] = trim(sprintf("%s %s %s %s",
                $colname,
                $this->checkColtype($colconfig),
                $this->getNullContraint($colconfig),
                $this->getUniqueContraint($colconfig)
            ));
        }
        return join(',', $result);
    }

    /**
     * TODO: implement and document what is expected in $colconfig
     *
     * @param array $colconfig
     * @return string
     */
    protected function getNullContraint($colconfig)
    {
        return ''; // TODO: 'NOT NULL' if ..., '' otherwise
    }

    /**
     * TODO: implement and document what is expected in $colconfig
     *
     * @param array $colconfig
     * @return string
     */
    protected function getUniqueContraint($colconfig)
    {
        return ''; // TODO
    }

    /**
     * For supported column types see https://www.sqlite.org/datatype3.html
     *
     * @param array $colconfig
     * @throws \RuntimeException
     * @return string
     */
    protected function checkColtype($colconfig)
    {
        $type = isset($colconfig['type']) ? $colconfig['type'] : 'column type not set';
        if (in_array(strtoupper($type), ['INTEGER', 'TEXT', 'BLOB', 'REAL', 'NUMERIC'])) {
            return $type;
        }
        throw new RuntimeException("column type '$type' is not supported'");
    }

}
