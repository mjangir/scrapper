<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\CommonTopicScrapper;
use Kacademy\Models\Domain as DomainModel;
use Kacademy\Models\Subject as SubjectModel;

class NewSubjectScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:new-subjects")
                ->setDescription("This command scraps all the subject names")
                ->setDefinition(array(
                    new InputOption('refresh', 'r')
                ))
                ->setHelp(<<<EOT
Scraps all subjects name

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:subjects --refresh</info>
<info>scrap:subjects -r</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous subjects. Are you sure, you want to continue this action.?', false);

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
            SubjectModel::getQuery()->delete();
        }
        
        // Get all domains for which the subjects have to be scrapped
        $domains = DomainModel::where('is_active', 1)
                ->where('node_slug', '<>', NULL)
                ->where('node_slug', '<>', '')
                ->where('subject_scrapped', '=', 0)
                ->get();
        
        // If domains are not empty
        if(!empty($domains)) {
            
            foreach ($domains as $domain) {
                
                // Create scrapper instance
                $scrapper = new CommonTopicScrapper();
                $scrapper->setUrl('https://www.khanacademy.org/api/v1/topic/'.$domain->node_slug);
                $scrapper->runScrapper(function($record) use ($scrapper, $output, $domain) {

                    $totalRecords  = (isset($record['children'])) ? count($record['children']) : 0;

                    // Log the domain name on console for which subjects are being scrapped
                    $output->writeln("<info>Domain:: ".$domain->title."</info>" . PHP_EOL);
                    
                    // If parent record info is not empty
                    if (!empty($record['parent'])) {
                        $parent = $record['parent'];
                        foreach($parent as $key => $value) {
                            $domain->{$key} = $value;
                        }
                        $domain->save();
                    }
                    
                    // If children were found then add them
                    if (!empty($record['children'])) {
                        
                        $children = $record['children'];
                        
                        foreach($children as $child) {
                            $child['domain_id'] = $domain->id;
                            SubjectModel::create($child);
                            
                            // Log the scrapped subject on console
                            $output->writeln("---".$child['title']. PHP_EOL);
                        }
                        
                        // Update total scrapped element to their parent table
                        $domain->subject_scrapped = $totalRecords;
                        $domain->save();
                    }
                });
            }
            // Show the completion message on console
            $output->writeln("<info>Subjects Scrapping Completed</info>");
        }
        
    }

}
