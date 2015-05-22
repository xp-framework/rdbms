<?php namespace rdbms\criterion;

use rdbms\SQLRenderable;

/**
 * Interface for all Prjections - 
 * Projections are built with thee static factory class rdbms.criterion.Projections
 * 
 * @see xp://rdbms.criterion.Projections
 */
interface Projection extends SQLRenderable {
  const AVG=  'avg(%s)';
  const SUM=  'sum(%s)';
  const MIN=  'min(%s)';
  const MAX=  'max(%s)';
  const PROP= '%s';
}
