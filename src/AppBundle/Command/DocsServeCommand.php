<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DocsServeCommand
 * @package AppBundle\Command
 *
 * This is a dumb wrapper around `mkdocs serve`. It basically just cd's into the
 * appropriate folder and then passes-through the args.
 */
class DocsServeCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
          ->setName('docs:serve')
          ->setDescription('Run the builtin docs development server')
          ->addOption('dev-addr', 'a', InputOption::VALUE_OPTIONAL,
            'IP address and port to serve documentation')
          ->addOption('strict', 's', InputOption::VALUE_NONE,
            'Enable strict mode. This will cause MkDocs to abort the build on any warnings.')
          ->addOption('theme', 't', InputOption::VALUE_OPTIONAL,
            'The theme to use when building your documentation [cosmo|cyborg|readthedocs|yeti|journal|bootstrap|readable|united|simplex|flatly|spacelab|amelia|cerulean|slate|mkdocs')
          ->addOption('livereload', null, InputOption::VALUE_NONE,
            'Enable the live reloading in the development server.')
          ->addOption('no-livereload', null, InputOption::VALUE_NONE,
            'Enable the live reloading in the development server.')
          ->addOption('quiet', 'q', InputOption::VALUE_NONE,
            'Silence warnings')//          ->addOption('verbose', 'v', InputOption::VALUE_NONE, 'Enable verbose output')
          ->addArgument('[book/lang/[branch]]', InputArgument::REQUIRED,
            'A book expression (e.g. "dev/en/master" or "user/fr/4.6"). The [book] and [lang] are mandatory. If [branch] is specified, it will be checked out.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (preg_match(';^(\w+)/(\w+)?$;', $input->getArgument('[book/lang/[branch]]'), $matches)) {
            $book = $matches[1];
            $lang = $matches[2];
            $branch = NULL;
        } elseif (preg_match(';^(\w+)/(\w+)/(\w+)$;', $input->getArgument('[book/lang/[branch]]'), $matches)) {
            $book = $matches[1];
            $lang = $matches[2];
            $branch = $matches[3];
        } else {
            $output->writeln("<error>Malformed [book/lang/branch]</error>");
            return 1;
        }

        $repoDir = $this->getContainer()->getParameter('publisher_repos_dir') . "/$book/$lang";

        if (file_exists($repoDir)) {
            $output->writeln("<info>Found git repo for $book/$lang in \"$repoDir\"</info>");
        } else {
            $output->writeln("<error>Failed to find git repo for $book/$lang in \"$repoDir\".</error>");
            $output->writeln("<error>Perhaps you should run \"docs:publish\" first?</error>");
            return 1;
        }

        $command = 'mkdocs serve';
        foreach (array('dev-addr', 'theme') as $option) {
            if ($input->getOption($option)) {
                $command .= " --{$option} " . escapeshellarg($input->getOption($option));
            }
        }
        foreach (array('strict', 'livereload', 'no-livereload', 'quiet') as $option) {
            if ($input->getOption($option) ) {
                $command .= " --{$option}";
            }
        }

        if ($branch) {
            $output->writeln("<info>Check out branch \"$branch\"</info>");
            self::passthruOK($repoDir, "git checkout $branch");
        }
        $output->writeln("<info>Launch \"$command\"</info>");
        self::passthruOK($repoDir, $command);
    }

    protected function passthruOK($newcwd, $command) {
        $oldcwd = getcwd();
        chdir($newcwd);
        passthru($command, $return);
        chdir($oldcwd);
        if ($return) {
            throw new \RuntimeException("Received error from command ($command)");
        }
    }

}
