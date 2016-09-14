<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
    protected function configure() {
        $this->setName("scrap:sub-topics")
                ->setDescription("This command scraps all the sub topics of a topic")
                ->setDefinition(array(
                    new InputOption('refresh', 'r'),
                    new InputOption('topic-id', 't', InputOption::VALUE_OPTIONAL, 'Topic Id Primary Key', false)
                ))
                ->setHelp(<<<EOT
Scraps all sub topics (Filters applicable)

Usage:

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
    protected function execute(InputInterface $input, OutputInterface $output) {
        $helper = $this->getHelper('question');
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous sub topics. Are you sure, you want to continue this action.?', false);

        // Get all inputs
        $refresh = $input->getOption('refresh');

        $errorStyle = new OutputFormatterStyle('red');
        $successStyle = new OutputFormatterStyle('green');
        $gettingStyle = new OutputFormatterStyle('yellow');

        $output->getFormatter()->setStyle('error', $errorStyle);
        $output->getFormatter()->setStyle('info', $successStyle);
        $output->getFormatter()->setStyle('getting', $gettingStyle);

        // If user passed refresh, show a confirmation message
        if ($refresh && !$helper->ask($input, $output, $purgeQuestion)) {
            return;
        } else if ($refresh) {
            TopicModel::where('parent_id', '<>', NULL)->delete();
        }

        // Get all topics for which the sub-topics have to be scrapped
        $topics = TopicModel::where('is_active', 1)
                ->where('ka_url', '<>', NULL)
                ->where('ka_url', '<>', '')
                ->where('parent_id', '=', NULL)
                ->where('skills_scrapped', '=', 0)
                ->get();

        
        // If topics found for which no sub-topics exists in database
        if (!empty($topics)) {
            
            // Iterate over those topics
            $i = 1;
            foreach ($topics as $topic) {

                $topicUrl = $topic->ka_url;

                // Create Sub Topic Scrapper Object
                $scrapper = new SubTopicScrapper();
                $scrapper->setUrl($topicUrl);
                $scrapper->runScrapper(function($subTopics) use ($scrapper, $output, $topic, $i) {
                    
                    // Log the topic name on console for which the sub-topics are being scrapped
                    $output->writeln($i.". ".$topic->title. PHP_EOL);
                    
                    $totalSubTopics = count($subTopics);

                    // If sub topics found in scrapping
                    if (!empty($subTopics)) {
                        
                        $topicId    = $topic->id;
                        $subjectId  = $topic->subject_id;

                        // Iterate over each sub-topic and insert them into DB
                        foreach ($subTopics as $key => $subTopic) {

                            $subTopicSrNo           = $key + 1;
                            $subTopic['parent_id']  = $topicId;
                            $subTopic['subject_id'] = $subjectId;
                            TopicModel::create($subTopic);
                            
                            // Log the sub-topic on console
                            $output->writeln("---".$subTopicSrNo.". ".$subTopic['title']. PHP_EOL);
                        }
                        
                        // Set total sub topics scrapped for this main topic
                        $topic->sub_topics_scrapped = $totalSubTopics;
                        $topic->save();
                    }
                    // Show the completion message on console
                    $output->writeln("<info>Sub-Topics Scrapping Completed</info>");
                });
                
                $i++;
            }
        }
    }

}
