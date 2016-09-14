<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
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
    protected function configure() {
        $this->setName("scrap:skills")
                ->setDescription("This command scraps all the skills of a topic")
                ->setDefinition(array(
                    new InputOption('refresh', 'r'),
                    new InputOption('topic-id', 't', InputOption::VALUE_OPTIONAL, 'Topic Id Primary Key', false)
                ))
                ->setHelp(<<<EOT
Scraps all skills (Filters applicable)

Usage:

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
    protected function execute(InputInterface $input, OutputInterface $output) {
        $helper = $this->getHelper('question');
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous skills. Are you sure, you want to continue this action.?', false);

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
            SkillModel::getQuery()->delete();
        }

        // Get all sub-topics for which skills have to be scrapped
        $topics = TopicModel::where('is_active', 1)
                ->where('ka_url', '<>', NULL)
                ->where('ka_url', '<>', '')
                ->where('parent_id', '<>', NULL)
                ->where('skills_scrapped', '=', 0)
                ->get();

        // If sub-topics found with 0 skills
        if (!empty($topics)) {
            
            // Iterate over them
            $i = 1;
            foreach ($topics as $topic) {
                
                $topicUrl = $topic->ka_url;

                $scrapper = new SkillScrapper();
                $scrapper->setUrl($topicUrl);
                $scrapper->runScrapper(function($skills) use ($scrapper, $output, $topic, $i) {
                    
                    // Log the topic name on console for which the skills are being scrapped
                    $output->writeln($i.". ".$topic->title. PHP_EOL);
                
                    $totalSkills = count($skills);

                    // If skills found for the sub topic
                    if (!empty($skills)) {
                        
                        $topicId = $topic->id;

                        // Iterate over skills
                        foreach ($skills as $key => $skill) {
                            
                            $skillSrNo          = $key + 1;
                            $skill['topic_id']  = $topicId;
                            SkillModel::create($skill);
                            
                            // Log the skill on console
                            $output->writeln("---".$skillSrNo.". ".$skill['title']. PHP_EOL);
                        }
                        
                        // Set total skills scrapped for this subtopic
                        $topic->skills_scrapped = $totalSkills;
                        $topic->save();
                    }
                    // Show the completion message on console
                    $output->writeln("<info>Skills Scrapping Completed</info>");
                });
                
                $i++;
            }
        }
    }

}
