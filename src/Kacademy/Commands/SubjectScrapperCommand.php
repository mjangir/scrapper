<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\SubjectScrapper;
use Kacademy\Models\Subject as SubjectModel;

class SubjectScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:subjects")
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

        // Create scrapper instance
        $scrapper = new SubjectScrapper();
        $scrapper->setUrl('');
        $scrapper->runScrapper(function($subjects) use ($scrapper, $output) {

            $totalSubjects      = count($subjects);
            $totalChildSubjects = 0;

            // If subjects are not empty
            if (!empty($subjects)) {
                $i = 1;
                foreach ($subjects as $key => $mainSubject) {

                    // Create Subject Model Instance and create main subject
                    $subjectModel = new SubjectModel();
                    $saveMain = $subjectModel->create(
                        array(
                            'title'     => $mainSubject['title'],
                            'slug'      => $mainSubject['slug'],
                            'ka_url'    => $mainSubject['ka_url']
                        )
                    );
                    
                    // Log the main subject on console
                    $output->writeln($i. ". ".$mainSubject['title']. PHP_EOL);

                    // If the main subject has some child subjects
                    if (isset($mainSubject['children']) && !empty($mainSubject['children'])) {
                        
                        $childrenCount       = count($mainSubject['children']);
                        
                        // Save each child subject in the database
                        foreach ($mainSubject['children'] as $key => $childSubject) {
                            $saveMain->children()->create($childSubject);
                            $childNumber = $key + 1;
                            $output->writeln("---".$childNumber. " ".$childSubject['title']. PHP_EOL);
                        }
                        
                        //Save child subjects scrapped field
                        $saveMain->child_subjects_scrapped = $childrenCount;
                        $saveMain->save();
                    }

                    $i++;
                }
            }
            // Show the completion message on console
            $output->writeln("<info>Subjects Scrapping Completed</info>");
        });
    }

}
