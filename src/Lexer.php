<?php namespace ChrisCooper\ApacheConfReader;

use ChrisCooper\ApacheConfReader\Nodes\Directory;
use ChrisCooper\ApacheConfReader\Nodes\IfModule;
use ChrisCooper\ApacheConfReader\Nodes\Node;
use ChrisCooper\ApacheConfReader\Nodes\Rewrite;
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

  protected $regexs = [];

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

    $current_node = [
      static::T_ROOT
    ];
    $current_node_level = 0;

    /** @var Node|null $node */
    $node = null;
    $nodes = [];

    /** @var Rewrite|null $rewrite */
    $rewrite = null;

    $variable_key = $variable_value = null;

    $config = new Collection([]);

    foreach ($tokens as $token) {
      $line_context = " (".$filename."#".$token[1].")";
      switch ($token[0]) {
        /** Nodes */
        case static::T_NODE_OPEN:
          if (
            (
              !in_array($current_node[$current_node_level], [static::T_ROOT]) &&
              in_array($token[3][1], ['VirtualHost', 'Directory'])
            )
          ) {
            throw new SyntaxErrorException(
              'Syntax error, expected T_ROOT given [Node: '.$token[3][1].'] '.
              $this->get_constant_name($current_node[$current_node_level]).' '.$line_context
            );
          }
          if (
            (
              !in_array($current_node[$current_node_level], [static::T_NODE_OPEN]) &&
              in_array($token[3][1], ['IfModule'])
            )
          ) {
            throw new SyntaxErrorException(
              'Syntax error, expected T_NODE_OPEN given [Node: '.$token[3][1].'] '.
              $this->get_constant_name($current_node[$current_node_level]).' '.$line_context
            );
          }
          switch ($token[3][1]) {
            case 'VirtualHost':
              $node = new VirtualHost($token['3']['2']);
              break;
            case 'Directory':
              $node = new Directory($token['3']['2']);
              break;
            case 'IfModule':
              if (empty($node) || ! $node instanceof Node) {
                throw new SyntaxErrorException('No current node defined '.$line_context);
              }
              $nodes[$current_node_level] = $node;

              $current_node_level++;
              $node = new IfModule($token['3']['2']);
              break;
          }
          $current_node[$current_node_level] = static::T_NODE_OPEN;
          break;
        case static::T_NODE_CLOSE:
          if ($current_node_level > 0) {
            $current_node_level--;
            $nodes[$current_node_level]->children[] = $node;
            $node = $nodes[$current_node_level];
            unset($nodes[$current_node_level]);
          } else {
            if (!in_array($current_node[$current_node_level], [static::T_NODE_OPEN])) {
              throw new SyntaxErrorException(
                'Syntax error, expected T_NODE_OPEN given '.
                $this->get_constant_name($current_node[$current_node_level]).$line_context
              );
            }

            $config[] = $node;
            $current_node[$current_node_level] = static::T_ROOT;
            unset($node);
          }
          break;
        case static::T_VARIABLE:
          if (!in_array($current_node[$current_node_level], [static::T_NODE_OPEN])) {
            throw new SyntaxErrorException(
              'Syntax error, expected T_NODE_OPEN given '.
              $this->get_constant_name($current_node[$current_node_level]).$line_context
            );
          }

          $variable_key = trim($token[3][1]);
          $variable_value = trim($token[3][2]);

          $current_node[$current_node_level] = static::T_VARIABLE;
          break;
        case static::T_CONTINUE_VARIABLE:
          if (!in_array($current_node[$current_node_level], [static::T_VARIABLE])) {
            throw new SyntaxErrorException(
              'Syntax error, expected T_VARIABLE given '.
              $this->get_constant_name($current_node[$current_node_level]).$line_context
            );
          }
          if ($variable_key === null || $variable_value === null) {
            throw new SyntaxErrorException(
              'Syntax error, no variable key or value found '.$line_context
            );
          }

          $variable_value .= ' '.trim($token[3][1]);

          break;
        case static::T_LINEBREAK:
          switch ($current_node[$current_node_level]) {
            case static::T_VARIABLE:
              if ($variable_key === null || $variable_value === null) {
                throw new SyntaxErrorException(
                  'Syntax error, no variable key or value found '.$line_context
                );
              }
              if ($node === null) {
                throw new SyntaxErrorException(
                  'No node defined '.$line_context
                );
              }

              if (in_array($variable_key, ['RewriteCond', 'RewriteRule'])) {
                $rewrite || $rewrite = new Rewrite();

                switch ($variable_key) {
                  case 'RewriteCond':
                    $rewrite->conditions[] = $variable_value;
                    break;
                  case 'RewriteRule':
                    $rewrite->rules[] = $variable_value;

                    if (preg_match('/,?L\]$/', $variable_value)) {
                      $node['Rewrite'] = $rewrite;
                      $rewrite = null;
                    }
                    break;
                }
              } else {
                if ($rewrite !== null) {
                  $node['Rewrite'] = $rewrite;
                  $rewrite = null;
                }

                $node[$variable_key] = $variable_value;
              }

              $variable_key = $variable_value = null;
              $current_node[$current_node_level] = static::T_NODE_OPEN;
              break;
          }
          break;
      }
    }

    if ($current_node[0] != static::T_ROOT) {
      $line_context = " (".$filename."#EOF)";
      throw new SyntaxErrorException(
        'Syntax error, expected T_ROOT given '.
        $this->get_constant_name($current_node[0]).$line_context
      );
    }

    return $config;
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