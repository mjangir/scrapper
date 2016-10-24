<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\UserScrapper;
use Kacademy\Models\User as UserModel;
use Kacademy\Models\UserBadge as UserBadgeModel;
use Illuminate\Database\Capsule\Manager as Capsule;

class UserScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:users")
                ->setDescription("This command scraps all the post users")
                ->setDefinition(array(
                    new InputOption('refresh', 'r')
                ))
                ->setHelp(<<<EOT
Scraps all post users

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:users --refresh</info>
<info>scrap:users -r</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous post users. Are you sure, you want to continue this action.?', false);

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
            UserModel::getQuery()->delete();
        }
        
        // Get all user ids from post and comments
        $userIds = Capsule::select("SELECT DISTINCT author_ka_id
                    FROM (
                        (SELECT author_ka_id FROM posts WHERE user_scrapped = 0 limit 500)
                        UNION
                        (SELECT author_ka_id FROM comments WHERE user_scrapped = 0 limit 500)
                    ) a");
        
        if(!empty($userIds)) {
            
            $scrapper = new UserScrapper();
            
            foreach($userIds as $userId) {
                $authorKaId = $userId->author_ka_id;
                
                // Create scrapper instance
                
                $scrapper->setUrl('https://www.khanacademy.org/api/internal/user/profile?kaid='.$authorKaId.'&lang=en');
                $scrapper->runScrapper(function($record) use ($scrapper, $output, $authorKaId, $userId) {

                    if(!empty($record)) {
                        
                        $output->writeln("<info>Scrapping :: {$record['username']}</info>");
                        
                        $badges = (isset($record['badges'])) ? $record['badges']  : NULL;
                        unset($record['badges']);
                        
                        $record['author_ka_id'] = $authorKaId;
                        $user = UserModel::create($record);
                        
                        // Update posts user scrapped 
                        $posts = \Kacademy\Models\Post::where('author_ka_id', '=', $authorKaId)->get();
                        if(!empty($posts))
                        {
                            foreach($posts as $post)
                            {
                                $post->user_scrapped = 1;
                                $post->save();
                            }
                        }
                        
                        // Update comments user scrapped 
                        $comments = \Kacademy\Models\Comment::where('author_ka_id', '=', $authorKaId)->get();
                        if(!empty($comments))
                        {
                            foreach($comments as $comment)
                            {
                                $comment->user_scrapped = 1;
                                $comment->save();
                            }
                        }
                        
                        if(!empty($badges)) {
                            foreach ($badges as $badge) {
                                $badge['user_id'] = $user->id;
                                UserBadgeModel::create($badge);
                            }
                        }
                    }
                });
            }
            
            $output->writeln("<info>User Scrapping Done!!</info>");
        }
    }

}
