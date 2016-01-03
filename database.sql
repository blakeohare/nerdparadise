-- phpMyAdmin SQL Dump
-- version 4.3.3
-- http://www.phpmyadmin.net
--
-- Generation Time: Jan 03, 2016 at 08:33 PM
-- Server version: 5.3.12-MariaDB
-- PHP Version: 5.6.16-nfsn1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `np10`
--

-- --------------------------------------------------------

--
-- Table structure for table `auto_grader_queue`
--

CREATE TABLE IF NOT EXISTS `auto_grader_queue` (
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(10) NOT NULL,
  `state` varchar(30) NOT NULL,
  `is_not_started` tinyint(4) NOT NULL,
  `time_created` int(11) NOT NULL,
  `time_processed` int(11) NOT NULL,
  `time_finished` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `code` text NOT NULL,
  `problem_id` int(11) NOT NULL,
  `output` text NOT NULL,
  `is_output_truncated` tinyint(4) NOT NULL,
  `callback_arg` text NOT NULL,
  `feature` enum('practice','competition','golf','tinker') NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=57 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `code_problems`
--

CREATE TABLE IF NOT EXISTS `code_problems` (
  `problem_id` int(11) NOT NULL,
  `type` enum('practice','golf','competition','') NOT NULL,
  `competition_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `statement` text NOT NULL,
  `metadata` text NOT NULL,
  `test_json` text NOT NULL,
  `user_solved_count` int(11) NOT NULL,
  `shortest_solution_size` int(11) NOT NULL,
  `shortest_solution` text NOT NULL,
  `shortest_solution_user_id` int(11) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `code_solutions`
--

CREATE TABLE IF NOT EXISTS `code_solutions` (
  `solution_id` int(11) NOT NULL,
  `problem_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `language_id` int(11) NOT NULL,
  `code` text NOT NULL,
  `outcome` varchar(30) NOT NULL,
  `outcome_details` text NOT NULL,
  `solve_time` int(11) NOT NULL,
  `code_size` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `competitions`
--

CREATE TABLE IF NOT EXISTS `competitions` (
  `competition_id` int(11) NOT NULL,
  `key` varchar(30) NOT NULL,
  `title` varchar(100) NOT NULL,
  `applicable_languages` text NOT NULL,
  `results` text NOT NULL,
  `start_time` int(11) NOT NULL,
  `duration` int(11) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `forum_categories`
--

CREATE TABLE IF NOT EXISTS `forum_categories` (
  `category_id` int(11) NOT NULL,
  `name` varchar(40) NOT NULL,
  `key` varchar(20) NOT NULL,
  `description` text NOT NULL,
  `thread_count` int(11) NOT NULL,
  `post_count` int(11) NOT NULL,
  `flags` varchar(20) NOT NULL,
  `last_post_id` int(11) NOT NULL,
  `show_in_recent` tinyint(4) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=17 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `forum_messages`
--

CREATE TABLE IF NOT EXISTS `forum_messages` (
  `user_id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `forum_message_user_to_thread_id`
--

CREATE TABLE IF NOT EXISTS `forum_message_user_to_thread_id` (
  `user_id_cluster` varchar(100) NOT NULL,
  `thread_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `forum_posts`
--

CREATE TABLE IF NOT EXISTS `forum_posts` (
  `post_id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `content_raw` text NOT NULL,
  `content_parsed` text NOT NULL,
  `content_search` text NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `forum_threads`
--

CREATE TABLE IF NOT EXISTS `forum_threads` (
  `thread_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `first_post_id` int(11) NOT NULL,
  `last_post_id` int(11) NOT NULL,
  `post_count` int(11) NOT NULL,
  `view_count` int(11) NOT NULL,
  `flags` varchar(20) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `forum_thread_tags`
--

CREATE TABLE IF NOT EXISTS `forum_thread_tags` (
  `user_id` int(11) NOT NULL,
  `thread_id` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `languages`
--

CREATE TABLE IF NOT EXISTS `languages` (
  `language_id` int(11) NOT NULL,
  `key` varchar(20) NOT NULL,
  `name` varchar(30) NOT NULL,
  `auto_grader_supported` tinyint(4) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=13 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `user_id` int(11) NOT NULL,
  `expiration` int(11) NOT NULL,
  `token` int(11) NOT NULL,
  `ip` varchar(15) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

CREATE TABLE IF NOT EXISTS `sessions` (
  `session_id` varchar(32) NOT NULL,
  `user_id` int(11) NOT NULL,
  `client` varchar(10) NOT NULL,
  `ttl_hours` int(11) NOT NULL,
  `last_ip` varchar(15) NOT NULL,
  `last_visit` int(11) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(20) NOT NULL,
  `login_id` varchar(20) NOT NULL,
  `pass_hash` varchar(40) NOT NULL,
  `email_addr` varchar(80) NOT NULL,
  `image_id` int(11) NOT NULL,
  `post_count` int(11) NOT NULL,
  `flags` varchar(20) NOT NULL,
  `time_registered` int(11) NOT NULL,
  `time_last_online` int(11) NOT NULL,
  `ip_registered` varchar(15) NOT NULL,
  `ip_last` varchar(15) NOT NULL
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `auto_grader_queue`
--
ALTER TABLE `auto_grader_queue`
  ADD PRIMARY KEY (`item_id`), ADD KEY `token` (`token`);

--
-- Indexes for table `code_problems`
--
ALTER TABLE `code_problems`
  ADD PRIMARY KEY (`problem_id`), ADD KEY `type` (`type`), ADD KEY `competition_id` (`competition_id`);

--
-- Indexes for table `code_solutions`
--
ALTER TABLE `code_solutions`
  ADD PRIMARY KEY (`solution_id`), ADD KEY `problem_id` (`problem_id`,`user_id`), ADD KEY `problem_id_2` (`problem_id`,`user_id`,`language_id`);

--
-- Indexes for table `competitions`
--
ALTER TABLE `competitions`
  ADD PRIMARY KEY (`competition_id`), ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `forum_categories`
--
ALTER TABLE `forum_categories`
  ADD PRIMARY KEY (`category_id`), ADD KEY `key` (`key`);

--
-- Indexes for table `forum_messages`
--
ALTER TABLE `forum_messages`
  ADD PRIMARY KEY (`user_id`,`thread_id`);

--
-- Indexes for table `forum_message_user_to_thread_id`
--
ALTER TABLE `forum_message_user_to_thread_id`
  ADD PRIMARY KEY (`user_id_cluster`);

--
-- Indexes for table `forum_posts`
--
ALTER TABLE `forum_posts`
  ADD PRIMARY KEY (`post_id`), ADD KEY `thread_id` (`thread_id`), ADD KEY `user_id` (`user_id`), ADD KEY `post_id` (`post_id`,`thread_id`);

--
-- Indexes for table `forum_threads`
--
ALTER TABLE `forum_threads`
  ADD PRIMARY KEY (`thread_id`), ADD KEY `category_id` (`category_id`), ADD KEY `last_post_id` (`last_post_id`);

--
-- Indexes for table `forum_thread_tags`
--
ALTER TABLE `forum_thread_tags`
  ADD PRIMARY KEY (`user_id`,`thread_id`);

--
-- Indexes for table `languages`
--
ALTER TABLE `languages`
  ADD PRIMARY KEY (`language_id`), ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`token`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`session_id`), ADD KEY `last_visit` (`last_visit`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `auto_grader_queue`
--
ALTER TABLE `auto_grader_queue`
  MODIFY `item_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=57;
--
-- AUTO_INCREMENT for table `code_problems`
--
ALTER TABLE `code_problems`
  MODIFY `problem_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT for table `code_solutions`
--
ALTER TABLE `code_solutions`
  MODIFY `solution_id` int(11) NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `competitions`
--
ALTER TABLE `competitions`
  MODIFY `competition_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `forum_categories`
--
ALTER TABLE `forum_categories`
  MODIFY `category_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=17;
--
-- AUTO_INCREMENT for table `forum_posts`
--
ALTER TABLE `forum_posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `forum_threads`
--
ALTER TABLE `forum_threads`
  MODIFY `thread_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=5;
--
-- AUTO_INCREMENT for table `languages`
--
ALTER TABLE `languages`
  MODIFY `language_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=13;
--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;