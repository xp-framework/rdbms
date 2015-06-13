RDBMS support for the XP Framework: MySQL, Sybase, MSSQL, PostgreSQL, SQLite3, Interbase ChangeLog
========================================================================

## ?.?.? / ????-??-??

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

* Changed dependency to use XP ~6.0 (instead of dev-master) - @thekid

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
