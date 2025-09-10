-- phpMyAdmin SQL Dump
-- version 2.9.1.1
-- http://www.phpmyadmin.net
-- 
-- Host: localhost
-- Generation Time: Jan 14, 2008 at 06:39 PM
-- Server version: 5.0.27
-- PHP Version: 5.2.0
-- 
-- Database: `db_ajax`
-- 

-- --------------------------------------------------------

-- 
-- Table structure for table `city`
-- 

CREATE TABLE `city` (
  `id` tinyint(4) NOT NULL auto_increment,
  `city` varchar(50) default NULL,
  `stateid` tinyint(4) default NULL,
  `countryid` tinyint(4) NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM  AUTO_INCREMENT=5 ;

-- 
-- Dumping data for table `city`
-- 

INSERT INTO `city` VALUES (1, 'Los Angales', 2, 1);
INSERT INTO `city` VALUES (2, 'New York', 1, 1);
INSERT INTO `city` VALUES (3, 'Toranto', 4, 2);
INSERT INTO `city` VALUES (4, 'Vancovour', 3, 2);

-- --------------------------------------------------------

-- 
-- Table structure for table `country`
-- 

CREATE TABLE `country` (
  `id` tinyint(4) NOT NULL auto_increment,
  `country` varchar(20) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM  AUTO_INCREMENT=3 ;

-- 
-- Dumping data for table `country`
-- 

INSERT INTO `country` VALUES (1, 'USA');
INSERT INTO `country` VALUES (2, 'Canada');

-- --------------------------------------------------------

-- 
-- Table structure for table `state`
-- 

CREATE TABLE `state` (
  `id` tinyint(4) NOT NULL auto_increment,
  `countryid` tinyint(4) NOT NULL,
  `statename` varchar(40) NOT NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM  AUTO_INCREMENT=5 ;

-- 
-- Dumping data for table `state`
-- 

INSERT INTO `state` VALUES (1, 1, 'New York');
INSERT INTO `state` VALUES (2, 1, 'Los Angeles');
INSERT INTO `state` VALUES (3, 2, 'British Columbia');
INSERT INTO `state` VALUES (4, 2, 'Toranto');
