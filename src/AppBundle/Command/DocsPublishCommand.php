<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DocsPublishCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
          ->setName('docs:publish')
          ->setDescription('...')
          ->addArgument('paths', InputArgument::IS_ARRAY,
            'One or more book expressions ("book/lang/branch"). (Default: all)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \AppBundle\BookLoader $books */
        $books = $this->getContainer()->get('book.loader');

        /** @var \AppBundle\Utils\Publisher $publisher */
        $publisher = $this->getContainer()->get('publisher');

        $rows = $books->findAsList();

        if (empty($rows)) {
            $output->writeln("<error>No books found</error>");
        }

        $rowKeys = $input->getArgument('paths') ? $input->getArgument('paths') : array_keys($rows);

        foreach ($rowKeys as $rowKey) {
            $row = $rows[$rowKey];
            $output->writeln("");
            $output->writeln(sprintf("Publish [%s/%s/%s] (from %s)",
              $row['book'], $row['lang'], $row['branch'], $row['repo']));

            $publisher->publish($row['book'], $row['lang'], $row['branch']);

            foreach ($publisher->getMessages() as $message) {
                $output->writeln($message['label'] . ': ' . $message['content']);
            }
            $publisher->clearMessages();

        }
    }

}
