<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\TopicScrapper;
use Kacademy\Models\Topic as TopicModel;
use Kacademy\Models\Subject as SubjectModel;

class TopicScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:topics")
                ->setDescription("This command scraps all the topics of a skill")
                ->setDefinition(array(
                    new InputOption('refresh', 'r'),
                    new InputOption('subject-id', 's', InputOption::VALUE_OPTIONAL, 'Subject Id Primary Key', false)
                ))
                ->setHelp(<<<EOT
Scraps all topics (Filters applicable)

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:topics --refresh</info>
<info>scrap:topics -r</info>

FILTERING:

Grab the topics for a specific subject ID. Provide the database subject ID primary key with the above commands combination.
<info>scrap:topics --subject-id 5</info>
<info>scrap:topics -s 5</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous topics. Are you sure, you want to continue this action.?', false);

        // Get all inputs
        $refresh = $input->getOption('refresh');

        // Output format styles
        $errorStyle = new OutputFormatterStyle('red');
        $successStyle = new OutputFormatterStyle('green');
        $gettingStyle = new OutputFormatterStyle('yellow');

        // Apply output styles
        $output->getFormatter()->setStyle('error', $errorStyle);
        $output->getFormatter()->setStyle('info', $successStyle);
        $output->getFormatter()->setStyle('getting', $gettingStyle);

        // If user passed refresh, show a confirmation message
        if ($refresh && !$helper->ask($input, $output, $purgeQuestion)) {
            return;
        } else if ($refresh) {
            // If refresh option provided, delete all topics
            TopicModel::getQuery()->delete();
        }

        // Get all subjects for which the topics have to be scrapped
        $subjects = SubjectModel::where('is_active', 1)
                ->where('ka_url', '<>', NULL)
                ->where('ka_url', '<>', '')
                ->where('parent_id', '<>', NULL)
                ->where('topics_scrapped', '=', 0)
                ->get();

        // If subjects are found, then start scrapping
        if (!empty($subjects)) {
            
            // Iterate over subjects
            $i = 1;
            foreach ($subjects as $subject) {
                
                $subjectUrl = $subject->ka_url;
                
                // Create Topic Scrapper Object
                $scrapper = new TopicScrapper();
                $scrapper->setUrl($subjectUrl);
                $scrapper->runScrapper(function($topics) use ($scrapper, $output, $subject, $i) {

                    $subjectId = $subject->id;
                    $totalTopics = count($topics);
                    
                    // Log the subject name on console for which topics are being scrapped
                    $output->writeln("<info>".$i.". Subject:: ".$subject->title."</info>" . PHP_EOL);

                    // If topics found for the particular subject
                    if (!empty($topics)) {
                        
                        // Iterate over each topic
                        foreach ($topics as $key => $topic) {
                            
                            $topicSrNo = $key + 1;
                            // Pass subject ID as foreign key for each topic
                            $topic['subject_id'] = $subjectId;
                            TopicModel::create($topic);
                            
                            // Log the scrapped topic on console
                            $output->writeln("---".$topicSrNo.". ".$topic['title']. PHP_EOL);
                        }
                        
                        // Save number of topics scrapped for the subject
                        $subject->topics_scrapped = $totalTopics;
                        $subject->save();
                    }
                    
                    // Show the completion message on console
                    $output->writeln("<info>Topics Scrapping Completed</info>");
                });
                
                $i++;
            }
        }
    }

}
