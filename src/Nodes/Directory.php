<?php namespace ChrisCooper\ApacheConfReader\Nodes;
use Illuminate\Support\Collection;

class Directory extends Node
{
  /** @var string */
  public $location;

  public function __construct($location, $params = [])
  {
    $this->location = $location;
    $this->params = $params;
  }
}