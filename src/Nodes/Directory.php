<?php namespace ChrisCooper\ApacheConfReader\Nodes;
use Illuminate\Support\Collection;

class Directory implements NodeInterface
{
  /** @var string */
  protected $location;

  /** @var Collection */
  protected $params;

  public function __construct($location, $params = [])
  {
    $this->location = $location;
    $this->params = new Collection($params);
  }
}