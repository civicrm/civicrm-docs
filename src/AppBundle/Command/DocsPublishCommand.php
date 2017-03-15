<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocsPublishCommand extends ContainerAwareCommand {

  protected function configure() {
    $this
      ->setName('docs:publish')
      ->setDescription('Publish one or more books')
      ->addArgument(
          'identifiers',
          InputArgument::IS_ARRAY,
          'One or more book identifiers (e.g. "user/en/master"). Partial '
          . 'identifiers are acceptable (e.g. "user/en" will publish all '
          . 'English versions of the User Guide. If no identifiers are '
          . 'specified, then all versions of all languages in all books will '
          . 'be published.');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    /** @var \AppBundle\Utils\Publisher $publisher */
    $publisher = $this->getContainer()->get('publisher');
    $identifiers = $input->getArgument('identifiers');
    if ($identifiers) {
      foreach ($identifiers as $identifier) {
        $publisher->publish($identifier);
      }
    }
    else {
      $publisher->publish();
    }
    foreach ($publisher->getMessages() as $message) {
      $output->writeln($message['label'] . ': ' . $message['content']);
    }
  }

}
