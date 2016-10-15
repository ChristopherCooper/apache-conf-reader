<?php
/**
 * Created by PhpStorm.
 * User: chris
 * Date: 14/02/2016
 * Time: 18:34
 */

namespace tests\ChrisCooper\ApacheConfReader;


use ChrisCooper\ApacheConfReader\ApacheConfig;
use ChrisCooper\ApacheConfReader\Nodes\Directory;
use ChrisCooper\ApacheConfReader\Nodes\IfModule;
use ChrisCooper\ApacheConfReader\Nodes\VirtualHost;
use ChrisCooper\ApacheConfReader\SyntaxErrorException;

class LexerTest extends \PHPUnit_Framework_TestCase
{

  public function test_successful_simple_conf()
  {
    $conf = (new ApacheConfig('stubs/simple.conf'))->handle();

    $this->assertInstanceOf(VirtualHost::class, $conf[0]);
    /** @var VirtualHost $vhost */
    $vhost = $conf[0];
    $this->assertEquals('127.0.0.1:8008', $vhost->address);
    $this->assertEquals(0, count($vhost->children));

    $this->assertInstanceOf(Directory::class, $conf[1]);
    /** @var Directory $dir */
    $dir = $conf[1];
    $this->assertEquals('/export/default_site/html/', $dir->location);
    $this->assertEquals(0, count($dir->children));
  }

  public function test_exception_thrown_simple_conf()
  {
    try {
      $conf = (new ApacheConfig('stubs/simple-exception.conf'))->handle();

      $this->fail('Exception '.SyntaxErrorException::class.' expected, none thrown');
    } catch (SyntaxErrorException $e) {
      $this->assertEquals('Syntax error, expected T_ROOT given T_VARIABLE (stubs/simple-exception.conf#EOF)', $e->getMessage());
    }
  }

  public function test_successful_sub_node_conf()
  {
    $conf = (new ApacheConfig('stubs/sub-node.conf'))->handle();

    $this->assertInstanceOf(Directory::class, $conf[0]);
    /** @var Directory $dir */
    $dir = $conf[0];
    $this->assertEquals('/export/test.co.uk/html/blog', $dir->location);
    $this->assertEquals(1, count($dir->children));
    $this->assertInstanceOf(IfModule::class, $dir->children[0]);
    /** @var IfModule $if_module */
    $if_module = $dir->children[0];
    $this->assertEquals('mod_rewrite.c', $if_module->module);
  }
  
}
