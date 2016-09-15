<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Models\Domain as DomainModel;

class DomainScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:domains")
                ->setDescription("This command scraps all the domain names")
                ->setDefinition(array(
                    new InputOption('refresh', 'r')
                ))
                ->setHelp(<<<EOT
Scraps all domain name

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:domains --refresh</info>
<info>scrap:domains -r</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous domains. Are you sure, you want to continue this action.?', false);

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
            DomainModel::getQuery()->delete();
        }
        
        // Lets make our domain array
        $domains = array(
            array(
                'node_slug' => 'math',
                'title' => 'Math'
            ),
            array(
                'node_slug' => 'science',
                'title' => 'Science'
            ),
            array(
                'node_slug' => 'computing',
                'title' => 'Computing'
            ),
            array(
                'node_slug' => 'humanities',
                'title' => 'Humanities'
            ),
            array(
                'node_slug' => 'economics-finance-domain',
                'title' => 'Economics &amp; Finance'
            )
            
        );
        
        foreach ($domains as $domain) {
            DomainModel::create($domain);
        }
        
        // Show the completion message on console
        $output->writeln("<info>Domains Scrapping Completed</info>");
        
    }

}
