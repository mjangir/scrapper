<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class QuestionScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure()
    {   
        $this->setName("scrap:questions")
             ->setDescription("This command scraps all the Questions of a skill")
             ->setDefinition(array(
                      new InputOption('only-new', 'a'),
                      new InputOption('only-update', 'u'),
                      new InputOption('add-update', 'e'),
                      new InputOption('refresh', 'r'),
                      new InputOption('skill-id', 's', InputOption::VALUE_OPTIONAL, 'Skill Id Primary Key', false)
                ))
             ->setHelp(<<<EOT
Scraps all Questions (Filters applicable)

Usage:

The following command will only add new records that don't exist in database.
<info>scrap:questions --only-new</info>
<info>scrap:questions -o</info>

The following command will update the existing records only. It will not add new.
<info>scrap:questions --only-update</info>
<info>scrap:questions -u</info>

The following command will update existing records if found otherwise will add a new one
<info>scrap:questions --add-update</info>
<info>scrap:questions -a</info>

The following command will delete all existing records and add from the begining
<info>scrap:questions --refresh</info>
<info>scrap:questions -r</info>

FILTERS:

Grab the Questions for a specific skill ID
<info>scrap:questions --skill-id 5</info>
<info>scrap:questions -s 5</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous Questions based on filters. Are you sure, you want to continue this action.?', false);

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

        $output->writeln(PHP_EOL.'<info>Total Questions Scrapped </info>');
    }
}