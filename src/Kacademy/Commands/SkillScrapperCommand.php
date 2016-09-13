<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\SkillScrapper;
use Kacademy\Models\Skill as SkillModel;
use Kacademy\Models\Topic as TopicModel;

class SkillScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure()
    {   
        $this->setName("scrap:skills")
             ->setDescription("This command scraps all the skills of a topic")
             ->setDefinition(array(
                      new InputOption('only-new', 'a'),
                      new InputOption('only-update', 'u'),
                      new InputOption('add-update', 'e'),
                      new InputOption('refresh', 'r'),
                      new InputOption('topic-id', 't', InputOption::VALUE_OPTIONAL, 'Topic Id Primary Key', false)
                ))
             ->setHelp(<<<EOT
Scraps all skills (Filters applicable)

Usage:

The following command will only add new records that don't exist in database.
<info>scrap:skills --only-new</info>
<info>scrap:skills -o</info>

The following command will update the existing records only. It will not add new.
<info>scrap:skills --only-update</info>
<info>scrap:skills -u</info>

The following command will update existing records if found otherwise will add a new one
<info>scrap:skills --add-update</info>
<info>scrap:skills -a</info>

The following command will delete all existing records and add from the begining
<info>scrap:skills --refresh</info>
<info>scrap:skills -r</info>

FILTERING:

Grab the skills for a specific topic ID. Provide the database topic ID primary key with the above commands combination.
<info>scrap:skills --topic-id 5</info>
<info>scrap:skills -t 5</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous skills. Are you sure, you want to continue this action.?', false);

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
            SkillModel::getQuery()->delete();
        }

        // Get all topics
        $topics = TopicModel::where('is_active', 1)
                                ->where('ka_url', '<>', NULL)
                                ->where('ka_url', '<>', '')
                                ->where('parent_id', '<>', NULL)
                                ->get();

        $scrapper = new SkillScrapper();

        if(!empty($topics))
        {
            foreach ($topics as $topic)
            {
                $topicUrl    = $topic->ka_url;
                $topicId     = $topic->id;

                $scrapper->setUrl($topicUrl);
                $scrapper->runScrapper(function($skills) use ($scrapper, $output, $topicId)
                {
                    if(!empty($skills))
                    {
                        foreach ($skills as $skill) {

                            $skill['topic_id']  = $topicId;

                            SkillModel::create($skill);
                        }
                    }
                    $output->writeln('<info>Total Skills Scrapped:: '.count($skills).'</info>'.PHP_EOL);
                });
            }
        }
    }
}