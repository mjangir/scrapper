<?php

namespace Kacademy\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Kacademy\Scrappers\CommentScrapper;
use Kacademy\Models\Post as PostModel;
use Kacademy\Models\Comment as CommentModel;

class CommentScrapperCommand extends Command {

    /**
     * Configures the console command
     *
     * @return void
     */
    protected function configure() {
        $this->setName("scrap:comments")
                ->setDescription("This command scraps all the comments of a post")
                ->setDefinition(array(
                    new InputOption('refresh', 'r')
                ))
                ->setHelp(<<<EOT
Scraps all post comments (Filters applicable)

Usage:

The following command will delete all existing records and add from the begining
<info>scrap:comments --refresh</info>
<info>scrap:comments -r</info>

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
        $purgeQuestion = new ConfirmationQuestion('This will delete all previous comments. Are you sure, you want to continue this action.?', false);

        // Get all inputs
        $refresh = $input->getOption('refresh');

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
        } else if ($refresh) {
            // If refresh option provided, delete all topics
            Comment::getQuery()->delete();
        }

        // Get all post for which the comment have to be scrapped
        $posts = PostModel::where('is_active', 1)
                ->where('key', '<>', NULL)
                ->where('key', '<>', '')
                ->where('comment_scrapped', '=', 0)
                ->orderBy('id')
                ->get();

        // If posts are found, then start scrapping
        if (!empty($posts)) {
            
            // Create Comment Scrapper Object
            $scrapper = new CommentScrapper();
                
            // Iterate over posts
            $i = 1;
            foreach ($posts as $post) {
                
                $key = $post['key']; 
                $urlToScrap = "https://www.khanacademy.org/api/internal/discussions/{$key}/replies?casing=camel&lang=en&_=1474214345302";
                
                $scrapper->setUrl($urlToScrap);
                $scrapper->runScrapper(function($comments) use ($scrapper, $output, $post, $i) {

                    $postId         = $post->id;
                    $totalComments  = count($comments);
                    
                    // If comments found for the particular subject
                    if (!empty($comments)) {
                        
                        // Log the post name on console for which comments are being scrapped
                        $output->writeln("<info>".$i.". Post:: ".$post->content."</info>" . PHP_EOL);
                        
                        // Log the skill name on console for which tips are being scrapped
//                        $output->writeln($scrapper->getUrl(). PHP_EOL);
//                        $output->writeln('-------'.count($tips));
                    
                        // Iterate over each comments
                        foreach ($comments as $key => $comment) {
                            // Pass post ID as foreign key for each comment
                            $comment['post_id']         = $postId;
                            CommentModel::create($comment);
                            
                            $output->writeln("Comment :: ".$comment['content']. PHP_EOL);
                        }
                        
                        // Save number of comments scrapped for the post
                        $post->comment_scrapped = $totalComments;
                        $post->save();
                    } else {
                        // Not completed
                        $output->writeln("<error>Not Completed - ".$i.". Post:: ".$post->content."</error>" . PHP_EOL);
                    }
                });
                
                $i++;
            }
            
            // Show the completion message on console
            $output->writeln("<info>Comments Scrapping Completed</info>");
        }
    }

}
