<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Models\BadgeCategory as BadgeCategoryModel;
use Kacademy\Scrappers\BadgeCategoryScrapper;

class BadgeCategoryScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:badge-categories")
                ->setDescription("This command scraps all the badge categories")
                ->setDefinition(array(
                    new InputOption('refresh', 'r')
                ))
                ->setHelp(<<<EOT
Scraps all badge categories

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:badge-categories --refresh</info>
<info>scrap:badge-categories -r</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous badge-categories. Are you sure, you want to continue this action.?', false);

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
            BadgeCategoryModel::getQuery()->delete();
        }
        
       // Create scrapper instance
        $scrapper = new BadgeCategoryScrapper();
        $scrapper->setUrl('https://www.khanacademy.org/api/v1/badges/categories');
        $scrapper->runScrapper(function($categories) use ($scrapper, $output) {

            // If categories are not empty
            if(!empty($categories))
            {
                foreach($categories as $category) {
                    BadgeCategoryModel::create($category);
                    $output->writeln("<info>Badge Category:: ".$category['description']."</info>" . PHP_EOL);
                }
            }
            
            $output->writeln("<info>Badge Category Scrapping Completed</info>" . PHP_EOL);
        });
    }

}
