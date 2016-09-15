<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\TranscriptScrapper;
use Kacademy\Models\Skill as SkillModel;
use Kacademy\Models\SkillTranscript as SkillTranscriptModel;

class TranscriptScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:transcripts")
                ->setDescription("This command scraps all the transcripts of a skill video")
                ->setDefinition(array(
                    new InputOption('refresh', 'r')
                ))
                ->setHelp(<<<EOT
Scraps all skill-transcripts (Filters applicable)

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:skill-transcripts --refresh</info>
<info>scrap:skill-transcripts -r</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous transcripts. Are you sure, you want to continue this action.?', false);

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
            SkillTranscriptModel::getQuery()->delete();
        }

        // Get all skills for which the transcripts have to be scrapped
        $skills = SkillModel::where('is_active', 1)
                ->where('type', '=', 'Video')
                ->where('video_youtube_id', '<>', NULL)
                ->where('transcrispt_scrapped', '=', 0)
                ->get();

        // If skills are found, then start scrapping
        if (!empty($skills)) {
            
            // Iterate over subjects
            $i = 1;
            foreach ($skills as $skill) {
                
                $urlToScrap = "videos/{$skill->video_youtube_id}/transcript?casing=camel&locale=en&lang=en";
                
                // Create Transcript Scrapper Object
                $scrapper = new TranscriptScrapper();
                $scrapper->setUrl($urlToScrap);
                $scrapper->runScrapper(function($transcripts) use ($scrapper, $output, $skill, $i) {

                    $skillId            = $skill->id;
                    $totalTranscripts   = count($transcripts);
                    
                    // Log the skill name on console for which transcripts are being scrapped
                    $output->writeln("<info>".$i.". Skill:: ".$skill->title."</info>" . PHP_EOL);

                    // If transcripts found for the particular subject
                    if (!empty($transcripts)) {
                        
                        // Iterate over each transcript
                        foreach ($transcripts as $key => $transcript) {
                            
                            $srNo = $key + 1;
                            // Pass skill ID as foreign key for each transcript
                            $transcript['skill_id']         = $skillId;
                            $transcript['youtube_video_id'] = $skill->video_youtube_id;
                            SkillTranscriptModel::create($transcript);
                            
                            // Log the scrapped transcript on console
                            $output->writeln("---".$srNo.". ".$transcript['text']. PHP_EOL);
                        }
                        
                        // Save number of transcripts scrapped for the skill
                        $skill->transcrispt_scrapped = $totalTranscripts;
                        $skill->save();
                    }
                    
                    // Show the completion message on console
                    $output->writeln("<info>Transcripts Scrapping Completed</info>");
                });
                
                $i++;
            }
        }
    }

}
