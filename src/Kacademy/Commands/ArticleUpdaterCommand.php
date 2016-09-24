<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\ArticleUpdaterScrapper;
use Kacademy\Models\Skill as SkillModel;

class ArticleUpdaterCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("update:articles")
                ->setDescription("This command will update all the articles")
                ->setHelp(<<<EOT
Update all the articles
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

        $errorStyle = new OutputFormatterStyle('red');
        $successStyle = new OutputFormatterStyle('green');
        $gettingStyle = new OutputFormatterStyle('yellow');

        $output->getFormatter()->setStyle('error', $errorStyle);
        $output->getFormatter()->setStyle('info', $successStyle);
        $output->getFormatter()->setStyle('getting', $gettingStyle);

        // Get all articles which have to be updated
        $articles = SkillModel::where('is_active', 1)
                ->where('type', '=', 'Article')
                ->get();
        
        // If articles are not empty
        if(!empty($articles)) {
            
            $updater = new ArticleUpdaterScrapper();
            
            foreach ($articles as $article) {
                
                // Create updater instance
                $updaterUrl = $updater->getBaseUrl().$article->ka_url;
                $updater->setKaUrl($article->ka_url);
                $updater->setUrl($updaterUrl);
                $updater->runScrapper(function($record) use ($updater, $output, $article) {

                    $output->writeln("<info>Article Updating:: ".$article->title."</info>" . PHP_EOL);
                    
                    // If parent record info is not empty
                    if (!empty($record)) {
                        foreach($record as $key => $value) {
                            $article->{$key} = $value;
                        }
                        $article->save();
                    }
                });
            }
            // Show the completion message on console
            $output->writeln("<info>Articles Updating Completed</info>");
        }
        
    }

}
