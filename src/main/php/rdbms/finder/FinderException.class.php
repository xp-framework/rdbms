<?php namespace rdbms\finder;

/**
 * Indicates an exception occured while using the Finder API. All
 * methods will wrap exceptions into an instance of this class or
 * a subclass of it. The causing exception is available via the 
 * getCause() method.
 *
 */
class FinderException extends \lang\XPException {

}