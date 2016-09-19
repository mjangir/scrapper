<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\BadgeScrapper;
use Kacademy\Models\Badge as BadgeModel;

class BadgeScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:badges")
                ->setDescription("This command scraps all the badges")
                ->setDefinition(array(
                    new InputOption('refresh', 'r')
                ))
                ->setHelp(<<<EOT
Scraps all badges name

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:badges --refresh</info>
<info>scrap:badges -r</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous badges. Are you sure, you want to continue this action.?', false);

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
            BadgeModel::getQuery()->delete();
        }
        
        // Create scrapper instance
        $scrapper = new BadgeScrapper();
        $scrapper->setUrl('https://khanacademy.org/api/v1/badges');
        $scrapper->runScrapper(function($badges) use ($scrapper, $output) {

            // If badges are not empty
            if(!empty($badges))
            {
                foreach($badges as $badge) {
                    BadgeModel::create($badge);
                    $output->writeln("<info>Badge:: ".$badge['name']."</info>" . PHP_EOL);
                }
            }
            $output->writeln("<info>Badge Scrapping Completed</info>" . PHP_EOL);
        });
    }

}
