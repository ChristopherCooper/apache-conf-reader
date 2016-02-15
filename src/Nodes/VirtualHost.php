<?php namespace ChrisCooper\ApacheConfReader\Nodes;

class VirtualHost extends Node
{
  /** @var string */
  public $address;

  public function __construct($address, $params = [])
  {
    $this->address = $address;
    $this->params = $params;
  }
}