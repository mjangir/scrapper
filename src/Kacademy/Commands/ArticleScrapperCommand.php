<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\ArticleScrapper;
use Kacademy\Models\SubTopic as SubTopicModel;
use Kacademy\Models\Skill as SkillModel;

class ArticleScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:articles")
                ->setDescription("This command scraps all the sub-topic articles")
                ->setDefinition(array(
                    new InputOption('refresh', 'r')
                ))
                ->setHelp(<<<EOT
Scraps all articles

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:articles --refresh</info>
<info>scrap:articles -r</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous sub topics articles. Are you sure, you want to continue this action.?', false);

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
            SkillModel::where('type', '=', 'Article')->delete();
        }
        
        // Get all sub-topics for which the articles have to be scrapped
        $subTopics = SubTopicModel::where('is_active', 1)
                ->where('node_slug', '<>', NULL)
                ->where('node_slug', '<>', '')
                ->where('title', '=', 0)
                ->get();
        
        // If sub-topics are not empty
        if(!empty($subTopics)) {
            
            // Create scrapper instance
            $scrapper = new ArticleScrapper();
                
            foreach ($subTopics as $subTopic) {
                $url = $subTopic->ka_url;
                $scrapper->setNodeSlug($subTopic->node_slug);
                $scrapper->setUrl($url);
                $scrapper->runScrapper(function($records) use ($scrapper, $output, $subTopic) {

                    $totalRecords  = count($records);

                    // Log the sub-topic name on console for which articles are being scrapped
                    $output->writeln("<info>Sub Topic:: ".$subTopic->title."</info>" . PHP_EOL);
                    
                    // If articles are not empty
                    if (!empty($records)) {
                        
                        foreach($records as $record) {
                            
                            $record['sub_topic_id'] = $subTopic->id;
                            SkillModel::create($record);
                            
                            // Log the scrapped exercise on console
                            $output->writeln("---".$record['title']. PHP_EOL);
                        }
                        
                        // Update total scrapped element to their parent table
                        $subTopic->article_scrapped = $totalRecords;
                        $subTopic->save();
                    }
                });
            }
            // Show the completion message on console
            $output->writeln("<info>Article Scrapping Completed</info>");
        }
    }
}
