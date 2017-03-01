<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocsListCommand extends ContainerAwareCommand {

  protected function configure() {
    $this->setName('docs:list')->setDescription('List available books');
  }

  protected function execute(InputInterface $input, OutputInterface $output) {
    /** @var \AppBundle\Model\Library $library */
    $library = $this->getContainer()->get('library');
    $table = new Table($output);
    $table->setHeaders(array('book', 'language', 'repo', 'branch'));
    $table->addRows($library->booksAsTable());
    $table->render();
  }

}
