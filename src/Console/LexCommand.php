<?php namespace ChrisCooper\ApacheConfReader\Console;

use ChrisCooper\ApacheConfReader\ApacheConfig;
use ChrisCooper\ApacheConfReader\Lexer;
use ChrisCooper\ApacheConfReader\Nodes\Directory;
use ChrisCooper\ApacheConfReader\Nodes\VirtualHost;
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

    /** @var \ChrisCooper\ApacheConfReader\Nodes\VirtualHost $virtual_host */
    foreach ($conf['VirtualHosts'] as $virtual_host) {
      dump($virtual_host);
    }

    /** @var \ChrisCooper\ApacheConfReader\Nodes\Directory $directory */
    foreach ($conf['Directories'] as $directory) {
      dump($directory);
    }

    $output->writeln("Done");
  }
}