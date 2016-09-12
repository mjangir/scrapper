<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\TopicScrapper;
use Kacademy\Models\Topic as TopicModel;
use Kacademy\Models\Subject as SubjectModel;

class TopicScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure()
    {   
        $this->setName("scrap:topics")
             ->setDescription("This command scraps all the topics of a skill")
             ->setDefinition(array(
                      new InputOption('only-new', 'a'),
                      new InputOption('only-update', 'u'),
                      new InputOption('add-update', 'e'),
                      new InputOption('refresh', 'r'),
                      new InputOption('subject-id', 's', InputOption::VALUE_OPTIONAL, 'Subject Id Primary Key', false)
                ))
             ->setHelp(<<<EOT
Scraps all topics (Filters applicable)

Usage:

The following command will only add new records that don't exist in database.
<info>scrap:topics --only-new</info>
<info>scrap:topics -o</info>

The following command will update the existing records only. It will not add new.
<info>scrap:topics --only-update</info>
<info>scrap:topics -u</info>

The following command will update existing records if found otherwise will add a new one
<info>scrap:topics --add-update</info>
<info>scrap:topics -a</info>

The following command will delete all existing records and add from the begining
<info>scrap:topics --refresh</info>
<info>scrap:topics -r</info>

FILTERING:

Grab the topics for a specific subject ID. Provide the database subject ID primary key with the above commands combination.
<info>scrap:topics --subject-id 5</info>
<info>scrap:topics -s 5</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous topics. Are you sure, you want to continue this action.?', false);

        // Get all inputs
        $onlyNew    = $input->getOption('only-new');
        $onlyUpdate = $input->getOption('only-update');
        $addUpdate  = $input->getOption('add-update');
        $refresh    = $input->getOption('refresh');

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
        }
        else
        {
            // If refresh option provided, delete all topics
            TopicModel::getQuery()->delete();
        }

        // Get all subjects
        $subjects = SubjectModel::where('is_active', 1)
                                ->where('ka_url', '<>', NULL)
                                ->where('parent_id', '<>', NULL)
                                ->where('ka_url', '<>', '')
                                ->get();
        if(!empty($subjects))
        {
            foreach ($subjects as $subject) {

                $subjectUrl    = $subject->ka_url;
                $subjectId     = $subject->id;

                $scrapper = new TopicScrapper();
                $scrapper->setUrl($subjectUrl);
                $scrapper->runScrapper(function($topics) use ($scrapper, $output, $subjectId) {

                    if(!empty($topics))
                    {
                        foreach ($topics as $key => $topic) {

                            $topic['subject_id'] = $subjectId;

                            TopicModel::create($topic);
                        }
                    }
                    $output->writeln('<info>Total Topics Scrapped:: '.count($topics).'</info>'.PHP_EOL);
                });
            }
        }                            
    }
}