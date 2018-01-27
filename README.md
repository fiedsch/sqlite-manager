# SQLite Manager (Helper Library)


## Purpose

Helper classes for working with SQLite Databases.


## Examples


### Connect to a database

```php
use Fiedsch\SqliteManager\Manager;

$dbpath = '/path/to/your/sqlite-database.db';
$manager = new Manager();
// @var Doctrine\DBAL\Connection 
$connection = $manager->connectTo($dbpath);
```


### Create a table in a database

```php
$manager = new Manager(); 
$columns = [
    'foo' => [
        'type' => 'TEXT'
    ],
    'bar' => [
            'type' => 'REAL'
     ]
];
$sql = $manager->getCreateTableSql('mytable', $columns);
// "CREATE TABLE mytable (id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT, foo TEXT, bar REAL)"
// Note the id column that will always be created!
```


For column types see https://www.sqlite.org/datatype3.html

For more Examples see the unit tests located in `tests`.


## TODO (Roadmap)

* Test existing table matches a column configuration
* Add columns if above test fails
