<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\SkillGroupScrapper;
use Kacademy\Models\SkillGroup as SkillGroupModel;

class SkillGroupScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure()
    {   
        $this->setName("scrap:skill-groups")
             ->setDescription("This command scraps all the topics of a skill")
             ->setDefinition(array(
                      new InputOption('only-new', 'a'),
                      new InputOption('only-update', 'u'),
                      new InputOption('add-update', 'e'),
                      new InputOption('refresh', 'r'),
                      new InputOption('topic-id', 's', InputOption::VALUE_OPTIONAL, 'Topic Id Primary Key', false)
                ))
             ->setHelp(<<<EOT
Scraps all skill groups (Filters applicable)

Usage:

The following command will only add new records that don't exist in database.
<info>scrap:skill-groups --only-new</info>
<info>scrap:skill-groups -o</info>

The following command will update the existing records only. It will not add new.
<info>scrap:skill-groups --only-update</info>
<info>scrap:skill-groups -u</info>

The following command will update existing records if found otherwise will add a new one
<info>scrap:skill-groups --add-update</info>
<info>scrap:skill-groups -a</info>

The following command will delete all existing records and add from the begining
<info>scrap:skill-groups --refresh</info>
<info>scrap:skill-groups -r</info>

FILTERING:

Grab the skill groups for a specific Topic ID. Provide the database Topic ID primary key with the above commands combination.
<info>scrap:topics --topic-id 5</info>
<info>scrap:topics -t 5</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous skill-groups. Are you sure, you want to continue this action.?', false);

        // Get all inputs
        $onlyNew    = $input->getOption('only-new');
        $onlyUpdate = $input->getOption('only-update');
        $addUpdate  = $input->getOption('add-update');
        $refresh    = $input->getOption('refresh');

        $errorStyle = new OutputFormatterStyle('red');
        $successStyle = new OutputFormatterStyle('green');
        $gettingStyle = new OutputFormatterStyle('yellow');

        $output->getFormatter()->setStyle('error', $errorStyle);
        $output->getFormatter()->setStyle('info', $successStyle);
        $output->getFormatter()->setStyle('getting', $gettingStyle);

        // If user passed refresh, show a confirmation message
        if ($refresh && !$helper->ask($input, $output, $purgeQuestion)) {
            return;
        }
        else
        {
            SkillGroupModel::getQuery()->delete();
        }

        $scrapper = new SkillGroupScrapper();
        $scrapper->setUrl('math/early-math/cc-early-math-counting-topic');
        $scrapper->runScrapper(function($skillGroups) use ($scrapper, $output) {

            if(!empty($skillGroups))
            {
                print_r($skillGroups);
            }
            $output->writeln('<info>Total Skill Groups Scrapped:: '.count($skillGroups).'</info>'.PHP_EOL);
        });
    }
}