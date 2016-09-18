<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\QuestionScrapper;
use Kacademy\Models\Skill as SkillModel;
use Kacademy\Models\Post as PostModel;

class QuestionScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:questions")
                ->setDescription("This command scraps all the questions of a skill video")
                ->setDefinition(array(
                    new InputOption('refresh', 'r')
                ))
                ->setHelp(<<<EOT
Scraps all skill-questions (Filters applicable)

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:skill-questions --refresh</info>
<info>scrap:skill-questions -r</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous questions. Are you sure, you want to continue this action.?', false);

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
            Post::where('type', '=', 'question')->delete();
        }

        // Get all skills for which the questions have to be scrapped
        $skills = SkillModel::where('is_active', 1)
                ->where('type', '=', 'Video')
                ->where('youtube_id', '<>', NULL)
                ->where('question_scrapped', '=', 0)
                ->groupBy('node_slug')
                ->orderBy('id')
                ->get();

        // If skills are found, then start scrapping
        if (!empty($skills)) {
            
            // Create Transcript Scrapper Object
            $scrapper = new QuestionScrapper();
                
            // Iterate over subjects
            $i = 1;
            foreach ($skills as $skill) {
                
                $nodeSlug = substr($skill['node_slug'], 2); 
                $urlToScrap = "https://www.khanacademy.org/api/internal/discussions/video/{$nodeSlug}/questions?casing=camel&sort=1&subject=all&limit=50&page=0&lang=en";
                
                $scrapper->setNodeSlug($nodeSlug);
                $scrapper->setUrl($urlToScrap);
                $scrapper->runScrapper(function($questions) use ($scrapper, $output, $skill, $i) {

                    $skillId            = $skill->id;
                    $totalQuestions   = count($questions);
                    
                    // If questions found for the particular subject
                    if (!empty($questions)) {
                        
                        // Log the skill name on console for which questions are being scrapped
                        $output->writeln("<info>".$i.". Skill:: ".$skill->title."</info>" . PHP_EOL);
                        
                        // Log the skill name on console for which questions are being scrapped
//                        $output->writeln($scrapper->getUrl(). PHP_EOL);
//                        $output->writeln('-------'.count($questions));
                    
                        // Iterate over each question
                        foreach ($questions as $key => $question) {
                            if(isset($question['answers'])) {
                                $answers = $question['answers'];
                                unset($question['answers']);
                            }
                            
                            $srNo = $key + 1;
                            // Pass skill ID as foreign key for each question
                            $question['skill_id']         = $skillId;
                            $question = PostModel::create($question);
                            
                            //$output->writeln("Question :: ".$question['content']. PHP_EOL);
                            
                            if(isset($question->id)) {
                                if(isset($answers) && is_array($answers) && !empty($answers)) {
                                    foreach($answers as $answer) {
                                        $answer['parent_id']    = $question->id;
                                        $answer['skill_id']     = $skillId;
                                        $insertedAnswer = PostModel::create($answer);
                                        
                                        if($insertedAnswer) {
                                            $output->writeln("------Answer :: ".$answer['content']. PHP_EOL);
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Save number of questions scrapped for the skill
                        $skill->question_scrapped = $totalQuestions;
                        $skill->save();
                    } else {
                        // Not completed skills
                        $output->writeln("<error>Not Completed - ".$i.". Skill:: ".$skill->title."</error>" . PHP_EOL);
                    }
                });
                
                $i++;
            }
            
            // Show the completion message on console
            $output->writeln("<info>Questions Scrapping Completed</info>");
        }
    }

}
