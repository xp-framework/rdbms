RDBMS support for the XP Framework
========================================================================

[![Build Status on TravisCI](https://secure.travis-ci.org/xp-framework/rdbms.svg)](http://travis-ci.org/xp-framework/rdbms)
[![XP Framework Mdodule](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Required PHP 5.4+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-5_4plus.png)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-framework/rdbms/version.png)](https://packagist.org/packages/xp-framework/rdbms)

RDBMS access APIs, connection manager, reverse engineering, O/R mapping.

The DriverManager model
-----------------------
To retrieve a connection class from the driver manager, you need to use the rdbms.DriverManager class.

```php
use rdbms\DriverManager;

$conn= DriverManager::getConnection('sybase://user:pass@server/NICOTINE');
```

The DriverManager class expects a unified connection string (we call it DSN).

Supported drivers
-----------------
The DriverManager will select an appropriate driver from the DSN string via its name. This will load an implemenation class which is either based on a PHP extension or implements the protocol to communicate with the database system in userland code. For the latter case, you need not do anything to your PHP setup; if there's a hard dependency on a PHP extension, you need to install that before you can use the driver.

| *Database system*  | *DSN name* | *PHP Extensions*        | *Userland driver*  |
| ------------------ | ---------- | ----------------------- | ------------------ |
| MySQL              | `mysql`    | ext/mysql or ext/mysqli | :white_check_mark: |
| PostgreSQL         | `pgsql`    | ext/pgsql               |                    |
| SQLite3            | `sqlite`   | ext/sqlite3             |                    |
| Interbase/FireBird | `ibase`    | ext/sqlite3             |                    |
| Sybase             | `sybase`   | ext/sybase-ct           | :white_check_mark: |
| MSSQL              | `mssql`    | ext/mssql or ext/sqlsrv | :white_check_mark: |

Basics
------
Once we have fetched a specific database connection class, we can now invoke a number of methods on it.

### Selecting
Selecting can be done with the "one-stop" method `select()` which will return all results into an array. Alternatively, the `query()` method allows iterative fetching.

```php
$news= $conn->select('news_id, caption, author_id from news');
// $news= [
//   [
//     'news_id'   => 12,
//     'caption'   => 'Hello World',
//     'author_id' => 1549
//   ]
// ]

$q= $conn->query('select news_id, caption, author_id from news');
while ($record= $q->next()) {
  // $record= [
  //   'news_id'   => 12,
  //   'caption'   => 'Hello World',
  //   'author_id' => 1549
  // ]
}
```

### Inserting
To "bind" parameters to an SQL query, the query, select, update, delete and insert methods offer a printf style tokenizer and support varargs syntax. These take care of NULL, type handling and proper escaping for you.

```php
$conn->insert('
  into news (
    caption, author_id, body, extended, created_at
  ) values (
    %s, -- caption
    %d, -- author_id
    %s, -- body
    %s, -- extended
    %s  -- created_at
  )',
  $caption,
  $authorId,
  $body,
  $extended,
  Date::now()
);
```

### Updating
The `update()` and `delete()` methods will return the number of affected rows, in case you're interested.

```php
$conn->update('news set author_id= %d where author_id is null', $authorId);
```

### Deleting
Even if your RDBMS requires you to use single quotes (or what-else), the API will take care of rewriting string literals for you.

```php
$conn->delete('from news where caption = "[DELETE]"');
```

Exceptions
----------
All of the above methods will throw exceptions for failed SQL queries, syntax errors, connection failure etc. All these exceptions are subclasses of `rdbms.SQLException`, so to catch all possible errors, use it in the catch clause:


```
+ rdbms.SQLException
|-- rdbms.ConnectionNotRegisteredException
|-- rdbms.SQLConnectException
|-- rdbms.SQLStateException
`-- rdbms.SQLStatementFailedException
    |-- rdbms.SQLConnectionClosedException
    `-- rdbms.SQLDeadlockException
```

Transactions
------------
To start a transaction, you can use the connection's `begin()`, `commit()` and `rollback()` methods as follows:

```php
public function createAuthor(...) {
  try {
    $tran= $conn->begin(new Transaction('create_author'));

    $id= $conn->insert('into author ...');
    $conn->insert('into notify ...');

    $tran->commit();
    return $id;
  } catch (SQLException $e) {
    $tran && $tran->rollback();
    throw $e;
  }
}
```

*Note: Not all database systems support transactions, and of those that do, not all support nested transactions. Be sure to read the manual pages of the RDBMS you are accessing.*
