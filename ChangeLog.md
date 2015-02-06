RDBMS support for the XP Framework: MySQL, Sybase, MSSQL, PostgreSQL, SQLite3, Interbase ChangeLog
========================================================================

## ?.?.? / ????-??-??

* Fixed Sybase still using iso-8859-1 when using `ext/sybase_ct` drivers.
  See pull request #4
  (@kiesel)
* Replaced `DB_ATTRTYPE_*` defines with class constants. See pull request #3
  (@kiesel)
* Fixed repeated EED messages. See pull request #1 - @thekid

## 6.0.0 / 2015-10-01

* Added `foreach` support for rdbms.ResultSet (@thekid)
* Heads up: Changed connections' default charset to UTF-8 (@thekid)
* Heads up: Converted classes to PHP 5.3 namespaces - (@thekid)
