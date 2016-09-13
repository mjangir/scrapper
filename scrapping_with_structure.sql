/*
SQLyog Ultimate v11.33 (64 bit)
MySQL - 10.1.16-MariaDB : Database - khanacademy_clone
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
CREATE DATABASE /*!32312 IF NOT EXISTS*/`khanacademy_clone` /*!40100 DEFAULT CHARACTER SET latin1 */;

USE `khanacademy_clone`;

/*Table structure for table `author_posts` */

DROP TABLE IF EXISTS `author_posts`;

CREATE TABLE `author_posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_id` int(11) NOT NULL,
  `type` enum('question','answer','tip','comment') NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `quality_kind` varchar(255) DEFAULT NULL,
  `permalink` varchar(255) DEFAULT NULL,
  `focus_url` varchar(255) DEFAULT NULL,
  `author_id` int(11) NOT NULL,
  `ka_key` text NOT NULL,
  `ka_expand_key` text NOT NULL,
  `appears_as_deleted` tinyint(1) DEFAULT '0',
  `is_answered` tinyint(1) DEFAULT '0',
  `is_flagged` tinyint(1) DEFAULT '0',
  `is_old` tinyint(1) DEFAULT '0',
  `is_spam` tinyint(1) DEFAULT '0',
  `is_down_voted` tinyint(1) DEFAULT '0',
  `is_up_voted` tinyint(1) DEFAULT '0',
  `is_from_video_author` tinyint(1) DEFAULT '0',
  `low_quality_score` decimal(9,6) DEFAULT NULL,
  `posted_on` datetime DEFAULT NULL,
  `last_answered_on` datetime DEFAULT NULL,
  `comment_count` int(5) DEFAULT NULL,
  `flag_count` int(5) DEFAULT NULL,
  `upvote_count` int(5) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `authors` */

DROP TABLE IF EXISTS `authors`;

CREATE TABLE `authors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `ka_id` varchar(255) DEFAULT NULL,
  `slug` varchar(255) NOT NULL,
  `ka_profile_url` varchar(255) DEFAULT NULL,
  `joined_on` date DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `skill_transcripts` */

DROP TABLE IF EXISTS `skill_transcripts`;

CREATE TABLE `skill_transcripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `skill_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `youtube_video_id` varchar(100) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `skills` */

DROP TABLE IF EXISTS `skills`;

CREATE TABLE `skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `topic_id` int(11) NOT NULL COMMENT 'Topic ID in which this skill exists',
  `ka_id` int(11) DEFAULT NULL COMMENT 'KA ID',
  `type` enum('Video','Exercise') NOT NULL COMMENT 'Type of the skill',
  `name` varchar(100) DEFAULT NULL COMMENT 'Wether the skill is a video or practice',
  `title` varchar(255) NOT NULL COMMENT 'Slug identifier of the skill',
  `display_name` varchar(255) DEFAULT NULL COMMENT 'Khan Academy URl',
  `short_display_name` varchar(255) DEFAULT NULL,
  `pretty_display_name` varchar(255) DEFAULT NULL COMMENT 'About text of the skill',
  `description` text,
  `creation_date` datetime DEFAULT NULL,
  `date_added` datetime DEFAULT NULL,
  `ka_url` varchar(255) NOT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `keywords` text,
  `license_name` varchar(150) DEFAULT NULL,
  `license_full_name` varchar(150) DEFAULT NULL,
  `license_url` varchar(255) DEFAULT NULL,
  `license_logo_url` varchar(255) DEFAULT NULL,
  `slug` varchar(100) NOT NULL,
  `node_slug` varchar(100) NOT NULL,
  `ka_relative_url` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `thumbnail_default` varchar(255) DEFAULT NULL,
  `thumbnail_filtered` varchar(255) DEFAULT NULL,
  `video_youtube_id` varchar(50) DEFAULT NULL,
  `video_duration` int(5) DEFAULT NULL,
  `video_download_size` int(11) DEFAULT NULL,
  `video_download_urls` text,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=latin1;

/*Table structure for table `subjects` */

DROP TABLE IF EXISTS `subjects`;

CREATE TABLE `subjects` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `parent_id` int(11) DEFAULT NULL COMMENT 'Parent Subject ID',
  `title` varchar(100) NOT NULL COMMENT 'Name of the subject',
  `slug` varchar(100) NOT NULL COMMENT 'Slug identifier of the subject',
  `description` text COMMENT 'About text of subject',
  `ka_url` varchar(255) NOT NULL COMMENT 'Khan Academy URL',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=48 DEFAULT CHARSET=latin1;

/*Table structure for table `topics` */

DROP TABLE IF EXISTS `topics`;

CREATE TABLE `topics` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Primary Key',
  `subject_id` int(11) NOT NULL COMMENT 'Subject ID for the topic',
  `parent_id` int(11) DEFAULT NULL COMMENT 'It will not be null if it is a sub topic',
  `title` varchar(255) NOT NULL COMMENT 'Topic Title',
  `slug` varchar(255) NOT NULL COMMENT 'Slug identifier of the topic',
  `icon` varchar(255) DEFAULT NULL COMMENT 'Icon of the topic',
  `description` text COMMENT 'Description about the topic',
  `ka_url` varchar(255) NOT NULL COMMENT 'Khan Academy URl',
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2132 DEFAULT CHARSET=latin1;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
