<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\CommonTopicScrapper;
use Kacademy\Models\Subject as SubjectModel;
use Kacademy\Models\Topic as TopicModel;

class NewTopicScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:new-topics")
                ->setDescription("This command scraps all the topic names")
                ->setDefinition(array(
                    new InputOption('refresh', 'r')
                ))
                ->setHelp(<<<EOT
Scraps all topic name

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:topics --refresh</info>
<info>scrap:topics -r</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous subjects. Are you sure, you want to continue this action.?', false);

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
            TopicModel::getQuery()->delete();
        }
        
        // Get all subjects for which the topics have to be scrapped
        $subjects = SubjectModel::where('is_active', 1)
                ->where('node_slug', '<>', NULL)
                ->where('node_slug', '<>', '')
                ->where('topic_scrapped', '=', 0)
                ->get();
        
        // If subjects are not empty
        if(!empty($subjects)) {
            
            foreach ($subjects as $subject) {
                
                // Create scrapper instance
                $scrapper = new CommonTopicScrapper();
                $scrapper->setUrl('https://www.khanacademy.org/api/v1/topic/'.$subject->node_slug);
                $scrapper->runScrapper(function($record) use ($scrapper, $output, $subject) {

                    $totalRecords  = (isset($record['children'])) ? count($record['children']) : 0;

                    // Log the subject name on console for which subjects are being scrapped
                    $output->writeln("<info>Subject:: ".$subject->title."</info>" . PHP_EOL);
                    
                    // If parent record info is not empty
                    if (!empty($record['parent'])) {
                        $parent = $record['parent'];
                        foreach($parent as $key => $value) {
                            $subject->{$key} = $value;
                        }
                        $subject->save();
                    }
                    
                    // If children were found then add them
                    if (!empty($record['children'])) {
                        
                        $children = $record['children'];
                        
                        foreach($children as $child) {
                            $child['subject_id'] = $subject->id;
                            TopicModel::create($child);
                            
                            // Log the scrapped subject on console
                            $output->writeln("---".$child['title']. PHP_EOL);
                        }
                        
                        // Update total scrapped element to their parent table
                        $subject->topic_scrapped = $totalRecords;
                        $subject->save();
                    }
                });
            }
            // Show the completion message on console
            $output->writeln("<info>Topics Scrapping Completed</info>");
        }
        
    }

}
