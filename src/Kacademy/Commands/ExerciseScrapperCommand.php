<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\ExerciseScrapper;
use Kacademy\Models\SubTopic as SubTopicModel;
use Kacademy\Models\Skill as SkillModel;

class ExerciseScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:exercises")
                ->setDescription("This command scraps all the sub-topic exercises")
                ->setDefinition(array(
                    new InputOption('refresh', 'r'),
                    new InputArgument('slugs', InputArgument::IS_ARRAY, 'Space separated slugs', null)
                ))
                ->setHelp(<<<EOT
Scraps all topic name

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:exercises --refresh</info>
<info>scrap:exercises -r</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous sub topics exercises. Are you sure, you want to continue this action.?', false);

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
            SkillModel::where('type', '=', 'Exercise')->delete();
        }
        
        // Get all sub-topics for which the exercises have to be scrapped
        $slugs = $input->getArgument('slugs');
        
        if(!empty($slugs))
        {
            $subTopics = SubTopicModel::where('is_active', 1)
                        ->where('node_slug', '<>', NULL)
                        ->where('exercise_scrapped', '=', 0)
                        ->whereIn('node_slug', $slugs)
                        ->get();
        }
        else
        {
            $subTopics = SubTopicModel::where('is_active', 1)
                    ->where('node_slug', '<>', NULL)
                    ->where('exercise_scrapped', '=', 0)
                    ->get();
        }
        
        // If sub-topics are not empty
        if($subTopics->count() > 0) {
            
            // Create scrapper instance
            $scrapper = new ExerciseScrapper();
                
            foreach ($subTopics as $subTopic) {
                
                $scrapper->setUrl('https://www.khanacademy.org/api/v1/topic/'.$subTopic->node_slug.'/exercises');
                $scrapper->runScrapper(function($records) use ($scrapper, $output, $subTopic) {

                    $totalRecords  = count($records);

                    // Log the sub-topic name on console for which exercises are being scrapped
                    $output->writeln("<info>Sub Topic:: ".$subTopic->title."</info>" . PHP_EOL);
                    
                    // If exercises are not empty
                    if (!empty($records)) {
                        
                        foreach($records as $record) {
                            
                            $record['sub_topic_id'] = $subTopic->id;
                            SkillModel::create($record);
                            
                            // Log the scrapped exercise on console
                            $output->writeln("---".$record['title']. PHP_EOL);
                        }
                        
                        // Update total scrapped element to their parent table
                        $subTopic->exercise_scrapped = $totalRecords;
                        $subTopic->save();
                    }
                });
            }
            // Show the completion message on console
            $output->writeln("<info>Exercise Scrapping Completed</info>");
        }
        else
        {
            $output->writeln("<info>No Sub Topic Found To Scrap Exercises</info>");
        }
    }
}
