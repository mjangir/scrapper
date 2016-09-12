<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
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
    protected function configure()
    {   
        $this->setName("scrap:subjects")
             ->setDescription("This command scraps all the subject names")
             ->setDefinition(array(
                      new InputOption('only-new', 'a'),
                      new InputOption('only-update', 'u'),
                      new InputOption('add-update', 'e'),
                      new InputOption('refresh', 'r')
                ))
             ->setHelp(<<<EOT
Scraps all subjects name

Usage:

The following command will only add new records that don't exist in database.
<info>scrap:subjects --only-new</info>
<info>scrap:subjects -o</info>

The following command will update the existing records only. It will not add new.
<info>scrap:subjects --only-update</info>
<info>scrap:subjects -u</info>

The following command will update existing records if found otherwise will add a new one
<info>scrap:subjects --add-update</info>
<info>scrap:subjects -a</info>

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
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous subjects. Are you sure, you want to continue this action.?', false);

        // Get all inputs
        $onlyNew    = $input->getOption('only-new');
        $onlyUpdate = $input->getOption('only-update');
        $addUpdate  = $input->getOption('add-update');
        $refresh    = $input->getOption('refresh');

        $errorStyle = new OutputFormatterStyle('red');
        $successStyle = new OutputFormatterStyle('green');
        $gettingStyle = new OutputFormatterStyle('yellow');

        $output->getFormatter()->setStyle('error', $errorStyle);
        $output->getFormatter()->setStyle('info', $successStyle);
        $output->getFormatter()->setStyle('getting', $gettingStyle);

        // If user passed refresh, show a confirmation message
        if ($refresh && !$helper->ask($input, $output, $purgeQuestion)) {
            return;
        }
        else
        {
            SubjectModel::getQuery()->delete();
        }

        // Create scrapper instance
        $scrapper = new SubjectScrapper();
        $scrapper->setUrl('');
        $scrapper->runScrapper(function($subjects) use ($scrapper, $output) {

            $totalSubjects      = count($subjects);
            $totalChildSubjects = 0;

            if(!empty($subjects))
            {
              foreach ($subjects as $key => $mainSubject) {

                $subjectModel = new SubjectModel();

                $saveMain = $subjectModel->create(
                    array(
                      'title'     => $mainSubject['title'],
                      'slug'      => $mainSubject['slug'],
                      'ka_url'    => $mainSubject['ka_url']
                    )
                );

                $withChildren = '';

                if(isset($mainSubject['children']) && !empty($mainSubject['children']))
                {
                  $childrenCount        = count($mainSubject['children']);
                  $totalChildSubjects   += $childrenCount;
                  $saveMain->children()->createMany($mainSubject['children']);
                  $withChildren         = ' With'.$childrenCount.' Children';
                }

                // Debug
                $output->writeln('--'.$mainSubject['title'].' Scrapped'. $withChildren.PHP_EOL);
              }
            }

            $output->writeln('<info>Total '.$totalSubjects.' Main And '.$totalChildSubjects.' Child Subjects Scrapped</info>'.PHP_EOL);
        });
    }
}