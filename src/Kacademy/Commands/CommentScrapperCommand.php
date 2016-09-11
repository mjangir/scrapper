<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class CommentScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure()
    {   
        $this->setName("scrap:comments")
             ->setDescription("This command scraps all the comments")
             ->setDefinition(array(
                      new InputOption('only-new', 'o'),
                      new InputOption('only-update', 'u'),
                      new InputOption('add-update', 'e'),
                      new InputOption('refresh', 'r'),
                      new InputOption('skill-id', 's', InputOption::VALUE_OPTIONAL, 'Skill Id Primary Key', false),
                      new InputOption('question-key', 'k', InputOption::VALUE_OPTIONAL, 'Khan Academy Question Key', false),
                      new InputOption('answer-key', 'a', InputOption::VALUE_OPTIONAL, 'Khan Academy  Answer Key', false),
                      new InputOption('tip-key', 't', InputOption::VALUE_OPTIONAL, 'Khan Academy Tip Key', false),
                ))
             ->setHelp(<<<EOT
Scraps all comments by skill, question, answer and tips

Usage:

The following command will only add new records that don't exist in database.
<info>scrap:comments --only-new</info>
<info>scrap:comments -o</info>

The following command will update the existing records only. It will not add new.
<info>scrap:comments --only-update</info>
<info>scrap:comments -u</info>

The following command will update existing records if found otherwise will add a new one
<info>scrap:comments --add-update</info>
<info>scrap:comments -a</info>

The following command will delete all existing records and add from the begining
<info>scrap:comments --refresh</info>
<info>scrap:comments -r</info>

FILTERING:

Grab the comments for a specific skill ID. Provide the database skill ID primary key with the above commands combination.
<info>scrap:comments --skill-id 5</info>
<info>scrap:comments -s 5</info>

Grab the comments for a specific Question. Provide Khan Academy question key primary key with the above commands combination.
<info>scrap:comments --question-key abcdefghijkl</info>
<info>scrap:comments -k abcdefghijkl</info>

Grab the comments for a specific Answer. Provide Khan Academy answer key primary key with the above commands combination.
<info>scrap:comments --answer-key abcdefghijkl</info>
<info>scrap:comments -a abcdefghijkl</info>

Grab the comments for a specific Tip. Provide Khan Academy tip key primary key with the above commands combination.
<info>scrap:comments --tip-key abcdefghijkl</info>
<info>scrap:comments -t abcdefghijkl</info>

EOT
);
    }

    /**
     * Executes the console command
     *
     * @param $input  InputInterface  Instance implementing console InputInterface
     * @param $output OutputInterface Instance implementing console OutputInterface
     * 
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous comments based on filters. Are you sure, you want to continue this action.?', false);

        // Get all inputs
        $refresh = $input->getOption('refresh');

        if ($refresh && !$helper->ask($input, $output, $purgeQuestion)) {
            return;
        }

        $errorStyle = new OutputFormatterStyle('red');
        $successStyle = new OutputFormatterStyle('green');
        $gettingStyle = new OutputFormatterStyle('yellow');

        $output->getFormatter()->setStyle('error', $errorStyle);
        $output->getFormatter()->setStyle('info', $successStyle);
        $output->getFormatter()->setStyle('getting', $gettingStyle);

        $output->writeln(PHP_EOL.'<info>Total Comments Scrapped </info>');
    }
}