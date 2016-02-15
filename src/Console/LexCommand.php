<?php namespace ChrisCooper\ApacheConfReader\Console;

use ChrisCooper\ApacheConfReader\ApacheConfig;
use ChrisCooper\ApacheConfReader\Lexer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class LexCommand extends Command
{
  protected function configure()
  {
    $this
      ->setName('lex')
      ->setDescription('Parse an apache conf file')
      ->addArgument(
        'file',
        InputArgument::REQUIRED,
        'Name of the file to lex.'
      );
  }

  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $conf = (new ApacheConfig($input->getArgument('file')))->handle();

    dump($conf);

    $output->writeln("Done");
  }
}