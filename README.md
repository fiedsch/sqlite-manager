# SQLite Manager (Helper Library)


## Purpose

Helper classes for working with SQLite Databases.


## Examples


### Connect to a database

```php
$dbpath = '/path/to/your/sqlite-database.db';
$manager = new \Fiedsch\SqliteManager\Manager();
// @var Doctrine\DBAL\Connection 
$connection = $manager->connectTo($dbpath);
```
which is mostly syntactic sugar that wraps `\Doctrine\DBAL\DriverManager::getConnection()`.


### Create a table in a database

```php
$manager = new \Fiedsch\SqliteManager\Manager();
// columns configuration 
// keys are the column names, 
// values are the respective column's configuration settings 
$columns = [
    'foo' => [
        'type' => 'TEXT',
    ],
    'bar' => [
            'type'      => 'REAL',
            'mandatory' => true,
            'unique'    => true,
     ]
];
$sql = $manager->getCreateTableSql('mytable', $columns);
// "CREATE TABLE mytable (id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, foo TEXT, bar REAL NOT NULL,)"
// Note the id column that will always be created!
```

Note: what is stored in `$columns[<colname>]['type']` is SQLite's "affinity" 
(see. "Type Affinity" in https://www.sqlite.org/datatype3.html).


### Add a column to an existing table
```php
$manager = new \Fiedsch\SqliteManager\Manager();
// column configuration 
$colconfig = [
    'type'      => 'REAL',
    'mandatory' => true,
    'unique'    => true,
];
$sql = $manager->getAddColumnSql('foo', 'bar', $colconfig);
// "ALTER TABLE foo ADD COLUMN bar REAL NOT NULL UNIQUE"
// 
```

For column types see https://www.sqlite.org/datatype3.html

For more Examples see the unit tests located in `tests`.


## TODO (Roadmap)

* Test existing table matches a column configuration
* Add columns if above test fails
* Add new features if required. Feel free to open an issue or create a pull request.