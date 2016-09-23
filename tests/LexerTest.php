<?php
/**
 * Created by PhpStorm.
 * User: chris
 * Date: 14/02/2016
 * Time: 18:34
 */

namespace tests\ChrisCooper\ApacheConfReader;


use ChrisCooper\ApacheConfReader\ApacheConfig;

class LexerTest extends \PHPUnit_Framework_TestCase
{

  public function test_successful_simple_conf()
  {
    $conf = new ApacheConfig('stubs/simple.conf');

    $conf->handle();
    $this->assertTrue(true);
  }
  
}
