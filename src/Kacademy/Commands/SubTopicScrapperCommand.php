<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\SubTopicScrapper;
use Kacademy\Models\Topic as TopicModel;

class SubTopicScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure()
    {   
        $this->setName("scrap:sub-topics")
             ->setDescription("This command scraps all the sub topics of a topic")
             ->setDefinition(array(
                      new InputOption('only-new', 'a'),
                      new InputOption('only-update', 'u'),
                      new InputOption('add-update', 'e'),
                      new InputOption('refresh', 'r'),
                      new InputOption('topic-id', 't', InputOption::VALUE_OPTIONAL, 'Topic Id Primary Key', false)
                ))
             ->setHelp(<<<EOT
Scraps all sub topics (Filters applicable)

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

Grab the sub topics for a specific Topic ID. Provide the database Topic ID primary key with the above commands combination.
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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous sub topics. Are you sure, you want to continue this action.?', false);

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
            TopicModel::where('parent_id', '<>', NULL)->delete();
        }

        // Get all topics
        $topics = TopicModel::where('is_active', 1)
                                ->where('ka_url', '<>', NULL)
                                ->where('ka_url', '<>', '')
                                ->where('parent_id', '=', NULL)
                                ->get();

        $scrapper = new SubTopicScrapper();

        if(!empty($topics))
        {
            foreach ($topics as $topic) {

                $topicUrl    = $topic->ka_url;
                $topicId     = $topic->id;
                $subjectId   = $topic->subject_id;

                $scrapper->setUrl($topicUrl);
                $scrapper->runScrapper(function($subTopics) use ($scrapper, $output, $subjectId, $topicId)
                {
                    if(!empty($subTopics))
                    {
                        foreach ($subTopics as $subTopic) {

                            $subTopic['parent_id']  = $topicId;
                            $subTopic['subject_id'] = $subjectId;

                            TopicModel::create($subTopic);
                        }
                    }
                    $output->writeln('<info>Total Sub Topics Scrapped:: '.count($subTopics).'</info>'.PHP_EOL);
                });
            }
        }
    }
}