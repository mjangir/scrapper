<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\TipScrapper;
use Kacademy\Models\Skill as SkillModel;
use Kacademy\Models\Post as PostModel;

class TipScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:tips")
                ->setDescription("This command scraps all the tips of a skill")
                ->setDefinition(array(
                    new InputOption('refresh', 'r')
                ))
                ->setHelp(<<<EOT
Scraps all skill-tips (Filters applicable)

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:skill-tips --refresh</info>
<info>scrap:skill-tips -r</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous tips. Are you sure, you want to continue this action.?', false);

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
            Post::where('type', '=', 'tip')->delete();
        }

        // Get all skills for which the tips have to be scrapped
        $skills = SkillModel::where('is_active', 1)
                ->where('type', '=', 'Video')
                ->where('youtube_id', '<>', NULL)
                ->where('tip_scrapped', '=', 0)
                ->groupBy('node_slug')
                ->orderBy('id')
                ->get();

        // If skills are found, then start scrapping
        if (!empty($skills)) {
            
            // Create Tip Scrapper Object
            $scrapper = new TipScrapper();
                
            // Iterate over subjects
            $i = 1;
            foreach ($skills as $skill) {
                
                $nodeSlug = substr($skill['node_slug'], 2); 
                $urlToScrap = "https://www.khanacademy.org/api/internal/discussions/video/{$nodeSlug}/comments?casing=camel&sort=1&subject=all&limit=50&page=0&lang=en";
                
                $scrapper->setNodeSlug($nodeSlug);
                $scrapper->setUrl($urlToScrap);
                $scrapper->runScrapper(function($tips) use ($scrapper, $output, $skill, $i) {

                    $skillId     = $skill->id;
                    $totalTips   = count($tips);
                    
                    // If tips found for the particular subject
                    if (!empty($tips)) {
                        
                        // Log the skill name on console for which tips are being scrapped
                        $output->writeln("<info>".$i.". Skill:: ".$skill->title."</info>" . PHP_EOL);
                        
                        // Log the skill name on console for which tips are being scrapped
//                        $output->writeln($scrapper->getUrl(). PHP_EOL);
//                        $output->writeln('-------'.count($tips));
                    
                        // Iterate over each tips
                        foreach ($tips as $key => $tip) {
                            $srNo = $key + 1;
                            // Pass skill ID as foreign key for each tip
                            $tip['skill_id']         = $skillId;
                            $tip = PostModel::create($tip);
                            
                            $output->writeln("Tip :: ".$tip['content']. PHP_EOL);
                        }
                        
                        // Save number of tips scrapped for the skill
                        $skill->tip_scrapped = $totalTips;
                        $skill->save();
                    } else {
                        // Not completed
                        $output->writeln("<error>Not Completed - ".$i.". Skill:: ".$skill->title."</error>" . PHP_EOL);
                    }
                });
                
                $i++;
            }
            
            // Show the completion message on console
            $output->writeln("<info>Tips Scrapping Completed</info>");
        }
    }

}
