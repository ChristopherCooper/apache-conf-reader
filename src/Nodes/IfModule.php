<?php namespace ChrisCooper\ApacheConfReader\Nodes;

class IfModule extends Node
{
  /** @var string */
  public $module;

  public function __construct($module, $params = [])
  {
    $this->module = $module;
    $this->params = $params;
  }
}