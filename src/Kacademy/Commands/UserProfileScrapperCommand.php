<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class UserProfileScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure()
    {   
        $this->setName("scrap:users")
             ->setDescription("This command scraps all the users profile")
             ->setDefinition(array(
                      new InputOption('force-update', 'f')
                ))
             ->setHelp(<<<EOT
Scraps all user profiles

Usage:

The following command will scrap all users and dump them into database
<info>scrap:users</info>

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
        $errorStyle = new OutputFormatterStyle('red');
        $successStyle = new OutputFormatterStyle('green');
        $gettingStyle = new OutputFormatterStyle('yellow');

        $output->getFormatter()->setStyle('error', $errorStyle);
        $output->getFormatter()->setStyle('info', $successStyle);
        $output->getFormatter()->setStyle('getting', $gettingStyle);

        $forceUpdate = $input->getOption('force-update');

        $output->writeln('<info>Scraping subjects</info>'.PHP_EOL);

        $array = array(
        	'Maths','English','Social',
        	'Maths','English','Social',
        	'Maths','English','Social',
        	'Maths','English','Social',

        	'Maths','English','Social',
        	'Maths','English','Social',
        );

        foreach ($array as $key => $value) {
        	$output->writeln('<getting>Getting </getting>'.$value);
        }

        $output->writeln(PHP_EOL.'<info>Total Subjects Scrapped </info>'. count($array));
    }
}