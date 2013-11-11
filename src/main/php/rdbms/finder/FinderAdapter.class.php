<?php namespace rdbms\finder;



/**
 * Adapter that makes rdbms.Peer objects usable as finders.
 *
 * @deprecated  Use rdbms.finder.GenericFinder instead
 * @see      xp://rdbms.Peer
 * @purpose  Finder / Peer Adapter
 */
class FinderAdapter extends Finder {
  protected 
    $peer= null;

  /**
   * Constructor
   *
   * @param   rdbms.Peer peer
   */
  public function __construct($peer) {
    $this->peer= $peer;
  }

  /**
   * Retrieve this finder's peer object
   *
   * @return  rdbms.Peer
   */
  public function getPeer() {
    return $this->peer;
  }
}
