<?php namespace ChrisCooper\ApacheConfReader\Nodes;
use Illuminate\Support\Collection;

class VirtualHost implements NodeInterface
{
  /** @var string */
  protected $address;

  /** @var Collection */
  protected $params;

  public function __construct($address, $params = [])
  {
    $this->address = $address;
    $this->params = new Collection($params);
  }
}