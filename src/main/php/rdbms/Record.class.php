<?php namespace rdbms;

use AllowDynamicProperties;

/**
 * A Record saves key value pairs
 */
#[AllowDynamicProperties]
class Record {
  
  /**
   * Constructor. Supports the array syntax, where an associative
   * array is passed to the constructor, the keys being the member
   * variables and the values the member's values.
   *
   * @param   array params default NULL
   */
  public function __construct($params= []) {
    foreach (array_keys($params) as $key) {
      $k= substr(strrchr('#'.$key, '#'), 1);
      $this->{$k}= $params[$key];
    }
  }
  
  /**
   * Sets a field's value by the field's name and returns the previous value.
   *
   * @param   string field name
   * @param   var value
   * @return  var previous value
   */
  public function set($field, $value) {
    $prev= $this->{$field};
    $this->{$field}= $value;
    return $prev;
  }

  /**
   * Gets a field's value by the field's name
   *
   * @param   string field name
   */
  public function get($field) {
    return $this->{$field};
  }
}