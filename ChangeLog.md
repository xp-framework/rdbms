RDBMS support for the XP Framework: MySQL, Sybase, MSSQL, PostgreSQL, SQLite3, Interbase ChangeLog
========================================================================

## ?.?.? / ????-??-??

## 13.0.0 / 2020-04-10

* Implemented xp-framework/rfc#334: Drop PHP 5.6:
  . **Heads up:** Minimum required PHP version now is PHP 7.0.0
  . Rewrote code base, grouping use statements
  . Converted `newinstance` to anonymous classes
  . Rewrote `isset(X) ? X : default` to `X ?? default`
  (@thekid)

## 12.0.3 / 2020-04-05

* Implemented RFC #335: Remove deprecated key/value pair annotation syntax
  (@thekid)

## 12.0.2 / 2019-12-01

* Made compatible with XP 10 - @thekid
* Refrain from using curly braces used for array offsets - @thekid

## 12.0.1 / 2018-10-06

* Fixed SQLite driver reconnecting on every single query - @thekid

## 12.0.0 / 2018-08-24

* **Heads up**: Deprecated `rdbms.util` package - @thekid
* **Heads up**: Remove `?log=` and `?observer=` functionality from
  connection strings; it required a singleton logger set up. Changed
  `rdbms.ProfilingObserver` and the implementations in `rdbms.sybase`
  to use *LogCategory* instances instead of strings as constructor
  arguments.
  (@thekid)
* Made compatible with `xp-framework/logging` version 9.0.0 - @thekid

## 11.0.0 / 2018-07-15

* Merged PR #46: Remove flags "unbuffered", "storeresult", "persistent"
  and "newlink":
  - Unbuffered queries are run by using open() instead of query() and are
    definitely not a per-connection flag
  - Persistent connections were dropped completely - they have caveats
    regarding locks and transactions described in the PHP Manual here:
    http://php.net/manual/en/features.persistent-connections.php
  - Creating new links is the default now, instantiating two DBConnection
    instances and not creating a new connection seems counter-intuitive
  (@thekid)
* Merged PR #45: Default reconnect to 1 - @thekid
* Merged PR #47: Remove unused affectedRows() method - @thekid
* Merged PR #48: Remove deprecated classes - @thekid

## 10.2.0 / 2018-07-15

* Merged PR #44: Connection handling. All drivers now automatically reconnect
  to database servers and re-run queries when they receive a disconnect. This
  behavior can be controlled by a new DSN parameter, `reconnect`, which specifies
  how many attempts are made, and defaults to 0.
  (@thekid)

## 10.1.0 / 2018-05-30

* Merged PR #43: Handle connection closed by admin as SQLConnectionClosedException
  (@johannes85, @thekid)

## 10.0.2 / 2018-02-20

* Fixed issue #41: Missing default value for lenth field in MySQLi implementation
  (@thekid)

## 10.0.1 / 2017-10-16

* Fixed Sybase and MSSQL `money` and `int4` data types on 64-bit systems
  (@thekid)
* Fixed datetime handling for Sybase and MSSQL
  (@treuter, @thekid)

## 10.0.0 / 2017-05-30

* Added method to discover available drivers to DefaultDrivers - @thekid
* Merged PR #39: XP9 Compatibility - @thekid

## 9.0.8 / 2017-05-20

* Refactored code to use `typeof()` instead of `xp::typeOf()`, see
  https://github.com/xp-framework/rfc/issues/323
  (@thekid)

## 9.0.7 / 2017-03-23

* Merged PR #38: Catch null value (PostgreSQL) - @treuter

## 9.0.6 / 2017-03-11

* Merged PR #37: Add column types json and jsonb, treat them as regular
  text columns
  (@treuter)

## 9.0.5 / 2016-12-15

* Merged PR #36: Implemented TDS_LONGCHAR
  (@johannes85, @thekid)
* Merged PR #35: Fixed name and namespace mismatch for SQLite3DBAdapter
  (@johannes85, @thekid)

## 9.0.4 / 2016-09-03

* Fixed reference to SQLite driver - @thekid

## 9.0.3 / 2016-08-29

* Ensure drivers correctly reconnect after an explicit call to `close()`
  (@thekid)

## 9.0.2 / 2016-08-29

* Fixed drivers selecting mysqlnd-backed mysqli extension, which leads
  to *mysqlnd cannot connect to MySQL 4.1+* errors
  (@thekid)

## 9.0.1 / 2016-08-29

* Added compatibility with xp-framework/networking v8.0.0 - @thekid

## 9.0.0 / 2016-08-28

* Rewrote `call_user_func_array()` indirections to PHP 5.6 varargs and
  argument unpacking syntax - see pull request #17
  (@thekid)
* **Heads up: Dropped PHP 5.5 support!** - @thekid
* Added forward compatibility with XP 8.0.0 - @thekid
* Changed Finder API to raise rdbms.finder.FinderExceptions for nonexistant
  methods instead of lang.Error
  (@thekid)

## 8.0.1 / 2016-07-23

* Fixed issue #34: Close connection when packet no. out of order
  (@thekid)
* Fixed handling of disconnects in MySQL userland driver - @thekid

## 8.0.0 / 2016-07-04

* Merged PR #32: Return `null` instead of `false` from ResultSet::next()
  at EOF. Although this is theoretically a BC break typical code using a
  `while` loop is not affected!
  (@thekid)

## 7.3.2 / 2016-06-24

* Merged PR #31: MySQL: Buffered queries inconsistency - @thekid

## 7.3.1 / 2016-06-05

* Fixed issue #27: Integration tests failing on HHVM - @thekid

## 7.3.0 / 2016-06-04

* Merged PR #26: MySQL: Change charset to utf8mb4 - @lluchs, @thekid
* Ensured deprecation warnings don't affect MySQL integration tests
  (@thekid)

## 7.2.2 / 2016-05-07

* Fixed issue #23: No SQLiteDBAdapter available, by reintroducig the removed
  class as `rdbms.sqlite3.SQLite3DBAdapter`
  (@thekid)
* Fixed issue #25: "Fatal error: Class 'Record' not found" error when using
  projections
  (@johannes85)

## 7.2.1 / 2016-05-02

* Merged pull request #22 - Fix PostgreSQL DB adapter w/ indexes (@kiesel)
* Merged pull request #23 - Fix handling of boolean values in PostgreSQL (@kiesel)

## 7.2.0 / 2016-04-18

* Merged pull request #10 - Add type 'bpchar' (blank-padded char) - @treuter
* Merged pull request #18 - change order of preference for MySQLi over MySQL, if
  extensions available. (@friebe, @kiesel)

## 7.1.0 / 2016-02-21

* Added version compatibility with XP 7 - @thekid

## 7.0.1 / 2016-02-21

* Dropped dependency on xp-framework/collections, which was only used
  in one place inside the test suite!
  (@thekid)

## 7.0.0 / 2016-02-21

* **Adopted semantic versioning. See xp-framework/rfc#300** - @thekid 
* Implemented xp-framework/rfc#309: ResultSets. **Heads up**: The `query()`
  method now always returns a `rdbms.ResultSet` instance.
  (@thekid)

## 6.7.1 / 2016-02-21

* Merged PR #15: Run MSSQL integration tests on AppVeyor
  (@thekid)

## 6.7.0 / 2016-02-20

* Merged PR #14: Run MySQL, PostgreSQL and SQLite integration tests
  (@thekid)
* Removed deprecated and dysfunctional SQLite v2 driver
  (@thekid)
* Fixed SQL cast expression being shadowed by cast function in SQLite3
  by renaming the latter to `marshal`.
  (@thekid)
* Changed rdbms.DriverManager::getConnection() to accept DSN instances
  as well as strings
  (@thekid)

## 6.6.0 / 2016-02-20

* Merged PR #11: Add support for 0x79 tokens - stored procedure return 
  status for Sybase and MSSQL userland drivers
  (@kiesel, @thekid)
* **Heads up: Bumped XP version requirement to XP 6.11.0** - @thekid
* Added forward compatibility with XP 7.0 - @thekid
* Replaced deprecated util.HashmapIterator with a local class.
  (@thekid)

## 6.5.5 / 2016-01-24

* Changed code base to no longer use deprecated FILE_MODE_* constants
  (@thekid)

## 6.5.4 / 2016-01-24

* Fix code to use `nameof()` instead of the deprecated `getClassName()`
  method from lang.Generic. See xp-framework/core#120
  (@thekid)

## 6.5.3 / 2016-01-09

* Fixed unbuffered queries in `mysqlx` driver causing fatal errors - @thekid

## 6.5.2 / 2016-01-09

* Fixed issue #10: Call to undefined function rdbms\mysqlx\this() - @thekid

## 6.5.1 / 2015-12-20

* Merged PR #9: Rewrite code to avoid ensure() statements - @thekid

## 6.5.0 / 2015-12-20

* **Heads up: Dropped PHP 5.4 support**. *Note: As the main source is not
  touched, unofficial PHP 5.4 support is still available though not tested
  with Travis-CI*.
  (@thekid)

## 6.4.4 / 2014-12-09

* Rewrote code to ue `literal()` instead of `xp::reflect()`. See
  xp-framework/rfc#298
  (@thekid)

## 6.4.3 / 2015-11-30

* Merged PR #7: Add missing types (Sybase, MySQL) - @kiesel

## 6.4.2 / 2015-09-26

* Merged PR #6: Use short array syntax / ::class in annotations - @thekid

## 6.4.1 / 2015-09-25

* Fixed problem with TDS packets longer than packet size - @thekid

## 6.4.0 / 2015-07-12

* Added forward compatibility with XP 6.4.0 - @thekid
* Fixed `rdbms.finder.FinderMethod` to throw correct exception - @thekid
* Changed rdbms.tds.TdsConnection's `toString()` to use a less-verbose way
  of displaying which protocol is used.
  (@thekid)

## 6.3.2 / 2015-06-24

* Overwrite default socket timeouts with -1 (no timeout). This way SQL
  queries which take longer than 60 seconds will still execute'
  (@thekid)

## 6.3.1 / 2015-06-13

* Fixed issue #5: Support for HHVM - @thekid
* Added forward compatibility with PHP7 - @thekid

## 6.3.0 / 2015-06-02

* Changed the default for *autoconnect* to true, that is, if it is omitted
  from the DSN, it will automatically connect. If the driver should not
  automatically connect, add `?autoconnect=0` to the DSN.
  (@thekid)

## 6.2.2 / 2015-06-01

* Changed MySQL userland protocol to handle case when MySQL server disconnects
  during connection setup phase and give a good error message.
  (@thekid)

## 6.2.1 / 2015-05-22

* Adjusted various places to new coding standards - @thekid
* Fixed incorrect references to TDS protocol exception class - @thekid
* TDS 5.0 protocol: Fixed TDS_ROWFMT handling - @thekid

## 6.2.0 / 2015-05-21

* Changed TDS 5.0 protocol to support long identifiers with Sybase 15.
  Fixes `The identifier ... is too long. Maximum length is 30`
  (@thekid)

## 6.1.1 / 2015-02-12

* Changed dependency to use XP 6.0 (instead of dev-master) - @thekid

## 6.1.0 / 2015-02-06

* Fixed Sybase still using iso-8859-1 when using `ext/sybase_ct` drivers.
  See pull request #4
  (@kiesel)
* Replaced `DB_ATTRTYPE_*` defines with class constants. See pull request #3
  (@kiesel)
* Fixed repeated EED messages. See pull request #1 - @thekid

## 6.0.0 / 2015-01-10

* Added `foreach` support for rdbms.ResultSet (@thekid)
* Heads up: Changed connections' default charset to UTF-8 (@thekid)
* Heads up: Converted classes to PHP 5.3 namespaces - (@thekid)
