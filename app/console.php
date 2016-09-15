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
use Kacademy\Commands\SubjectScrapperCommand;
use Kacademy\Commands\TopicScrapperCommand;
use Kacademy\Commands\SubTopicScrapperCommand;
use Kacademy\Commands\SkillScrapperCommand;
use Kacademy\Commands\TranscriptScrapperCommand;
use Kacademy\Commands\QuestionScrapperCommand;
use Kacademy\Commands\TipsScrapperCommand;
use Kacademy\Commands\CommentScrapperCommand;
use Kacademy\Commands\UserProfileScrapperCommand;

use Kacademy\Commands\NewSubjectScrapperCommand;
use Kacademy\Commands\NewTopicScrapperCommand;
use Kacademy\Commands\NewSubTopicScrapperCommand;

$app = new Application();
$app->add(new SubjectScrapperCommand());
$app->add(new TopicScrapperCommand());
$app->add(new SubTopicScrapperCommand());
$app->add(new SkillScrapperCommand());
$app->add(new TranscriptScrapperCommand());
$app->add(new QuestionScrapperCommand());
$app->add(new TipsScrapperCommand());
$app->add(new CommentScrapperCommand());
$app->add(new UserProfileScrapperCommand());

$app->add(new NewSubjectScrapperCommand());
$app->add(new NewTopicScrapperCommand());
$app->add(new NewSubTopicScrapperCommand());
$app->run();