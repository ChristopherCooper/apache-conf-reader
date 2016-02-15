<?php namespace ChrisCooper\ApacheConfReader;

use ChrisCooper\ApacheConfReader\Nodes\Directory;
use ChrisCooper\ApacheConfReader\Nodes\NodeInterface;
use ChrisCooper\ApacheConfReader\Nodes\VirtualHost;
use Illuminate\Support\Collection;
use Phlexy\LexerFactory\Stateless\UsingPregReplace;
use Phlexy\LexerDataGenerator;

class Lexer
{
  const T_ROOT = -1;
  const T_COMMENT = 0;
  const T_HORI_WHITESPACE = 4;
  const T_NODE_OPEN = 5;
  const T_NODE_CLOSE = 6;
  const T_VARIABLE = 7;
  const T_CONTINUE_VARIABLE = 1;
  const T_LINEBREAK = 10;

  /** @var UsingPregReplace */
  protected $factory;

  /** @var \Phlexy\Lexer\Stateless\UsingPregReplace */
  public $lexer;

  public function __construct()
  {
    $this->regexs = [
      '#[^\r\n]*' => self::T_COMMENT,
      '\h+' => self::T_HORI_WHITESPACE,
      '<([^\r\n\>]+) ([^\r\n\>\ ]*)?>' => self::T_NODE_OPEN,
      '<\/([^\r\n\>]+)>' => self::T_NODE_CLOSE,
      '\\\\\h*\r?\n\h*([^\r\n]+)' => self::T_CONTINUE_VARIABLE,
      '([a-zA-Z]+)\h+([^\r\n]+)(?<!\\\\)' => self::T_VARIABLE,
      '\r?\n' => self::T_LINEBREAK,
    ];
    $this->factory = new UsingPregReplace(new LexerDataGenerator);
    $this->lexer = $this->factory->createLexer($this->regexs);
  }

  public function parse($filename)
  {
    if (!file_exists($filename)) {
      throw new \Exception('File not found "'.$filename.'"');
    }

    $tokens = $this->lexer->lex(file_get_contents($filename));

    $current_node = static::T_ROOT;

    $node = null;

    $config = new Collection([
      'VirtualHosts' => new Collection([]),
      'Directories' => new Collection([]),
    ]);

    foreach ($tokens as $token) {
      $line_context = " (Line #".$token[1].")";
      switch ($token[0]) {
        /** Nodes */
        case static::T_NODE_OPEN:
          if (!in_array($current_node, [static::T_ROOT])) {
            throw new SyntaxErrorException(
              'Syntax error, expected T_ROOT'.
              $this->get_constant_name($current_node).$line_context
            );
          }
          switch ($token[3][1]) {
            case 'VirtualHost':
              $node = new VirtualHost($token['3']['2']);
              break;
            case 'Directory':
              $node = new Directory($token['3']['2']);
              break;
          }
          break;
        case static::T_NODE_CLOSE:
          if (in_array($current_node, [static::T_VARIABLE])) {
            throw new SyntaxErrorException(
              'Syntax error, expected one of T_VARIABLE given '.
              $this->get_constant_name($current_node).$line_context
            );
          }

          switch ($token[3][1]) {
            case 'VirtualHost':
              $config['VirtualHosts'][] = $node;
              break;
            case 'Directory':
              $config['Directories'][] = $node;
              break;
          }
          unset($node);
          break;
      }
    }

      dd($config);

    exit;
    return $conf;
  }

  protected function get_constant_name($constant_value)
  {
    $class = new \ReflectionClass(static::class);
    foreach ($class->getConstants() as $name => $value) {
      if ($value == $constant_value) {
        return $name;
      }
    }
    return null;
  }
}