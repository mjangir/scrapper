<?php

// set to run indefinitely if needed
set_time_limit(0);

/* Optional. It’s better to do it in the php.ini file */
date_default_timezone_set('Asia/Kolkata'); 

// include the composer autoloader
require_once __DIR__ . '/../vendor/autoload.php'; 

// include database config file
require_once __DIR__ . '/config/database.php'; 

// import the Symfony Console Application 
use Symfony\Component\Console\Application; 
use Kacademy\Commands\TranscriptScrapperCommand;
use Kacademy\Commands\SubjectScrapperCommand;
use Kacademy\Commands\TopicScrapperCommand;
use Kacademy\Commands\SubTopicScrapperCommand;
use Kacademy\Commands\VideoScrapperCommand;
use Kacademy\Commands\ExerciseScrapperCommand;
use Kacademy\Commands\DomainScrapperCommand;
use Kacademy\Commands\QuestionScrapperCommand;
use Kacademy\Commands\TipScrapperCommand;
use Kacademy\Commands\CommentScrapperCommand;

$app = new Application();
$app->add(new TranscriptScrapperCommand());
$app->add(new SubjectScrapperCommand());
$app->add(new TopicScrapperCommand());
$app->add(new SubTopicScrapperCommand());
$app->add(new VideoScrapperCommand());
$app->add(new ExerciseScrapperCommand());
$app->add(new DomainScrapperCommand());
$app->add(new QuestionScrapperCommand());
$app->add(new TipScrapperCommand());
$app->add(new CommentScrapperCommand());
$app->run();