-- phpMyAdmin SQL Dump
-- version 4.2.12deb2+deb8u2
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 30, 2017 at 06:15 PM
-- Server version: 10.0.30-MariaDB-0+deb8u2
-- PHP Version: 5.6.30-0+deb8u1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `rsm1`
--

-- --------------------------------------------------------

--
-- Table structure for table `rs_actions`
--

DROP TABLE IF EXISTS `rs_actions`;
CREATE TABLE IF NOT EXISTS `rs_actions` (
`RS_ID` int(11) unsigned NOT NULL,
  `RS_NAME` varchar(255) NOT NULL,
  `RS_DESCRIPTION` varchar(255) NOT NULL,
  `RS_APPLICATION_NAME` varchar(255) NOT NULL,
  `RS_APPLICATION_LOGO` longblob NOT NULL,
  `RS_CONFIGURATION_ITEMTYPE` varchar(255) NOT NULL DEFAULT 'configuration.module.generic'
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_actions_clients`
--

DROP TABLE IF EXISTS `rs_actions_clients`;
CREATE TABLE IF NOT EXISTS `rs_actions_clients` (
  `RS_ID` int(11) unsigned NOT NULL,
  `RS_CONFIGURATION_ITEM_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_ACTION_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_actions_groups`
--

DROP TABLE IF EXISTS `rs_actions_groups`;
CREATE TABLE IF NOT EXISTS `rs_actions_groups` (
  `RS_ACTION_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_GROUP_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_colors`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_colors`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_colors` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` varchar(6) NOT NULL,
  `RS_FINAL_VALUE` varchar(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_dates`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_dates`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_dates` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` date NOT NULL,
  `RS_FINAL_VALUE` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_datetime`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_datetime`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_datetime` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` datetime NOT NULL,
  `RS_FINAL_VALUE` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_files`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_files`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_files` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_NAME` varchar(255) NOT NULL,
  `RS_INITIAL_SIZE` int(11) NOT NULL,
  `RS_INITIAL_VALUE` longblob NOT NULL,
  `RS_FINAL_NAME` varchar(255) NOT NULL,
  `RS_FINAL_SIZE` int(11) NOT NULL,
  `RS_FINAL_VALUE` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_floats`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_floats`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_floats` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` decimal(24,12) NOT NULL,
  `RS_FINAL_VALUE` decimal(24,12) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_identifiers`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_identifiers`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_identifiers` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` int(11) NOT NULL,
  `RS_FINAL_VALUE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_identifiers_to_itemtypes`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_identifiers_to_itemtypes`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_identifiers_to_itemtypes` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` int(11) NOT NULL,
  `RS_FINAL_VALUE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_identifiers_to_properties`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_identifiers_to_properties`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_identifiers_to_properties` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` int(11) NOT NULL,
  `RS_FINAL_VALUE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_images`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_images`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_images` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_SIZE` int(11) NOT NULL DEFAULT '0',
  `RS_INITIAL_NAME` varchar(255) NOT NULL,
  `RS_INITIAL_VALUE` longblob NOT NULL,
  `RS_FINAL_SIZE` int(11) NOT NULL DEFAULT '0',
  `RS_FINAL_NAME` varchar(255) NOT NULL,
  `RS_FINAL_VALUE` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_integers`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_integers`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_integers` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` int(11) NOT NULL,
  `RS_FINAL_VALUE` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_longtext`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_longtext`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_longtext` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `RS_FINAL_VALUE` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_multiidentifiers`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_multiidentifiers`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_multiidentifiers` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` longtext NOT NULL,
  `RS_FINAL_VALUE` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_passwords`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_passwords`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_passwords` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` text NOT NULL,
  `RS_FINAL_VALUE` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_text`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_text`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_text` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `RS_FINAL_VALUE` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_audit_trail_property_variant`
--

DROP TABLE IF EXISTS `rs_audit_trail_property_variant`;
CREATE TABLE IF NOT EXISTS `rs_audit_trail_property_variant` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_USER_ID` int(11) NOT NULL,
  `RS_TOKEN` char(32) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CHANGED_DATE` datetime NOT NULL,
  `RS_INITIAL_VALUE` longblob NOT NULL,
  `RS_FINAL_VALUE` longblob NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_categories`
--

DROP TABLE IF EXISTS `rs_categories`;
CREATE TABLE IF NOT EXISTS `rs_categories` (
  `RS_CATEGORY_ID` int(11) NOT NULL,
  `RS_NAME` varchar(255) NOT NULL,
  `RS_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_ITEMTYPE_ID` int(11) unsigned NOT NULL,
  `RS_ORDER` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_clients`
--

DROP TABLE IF EXISTS `rs_clients`;
CREATE TABLE IF NOT EXISTS `rs_clients` (
`RS_ID` int(11) NOT NULL COMMENT 'Database Client ID',
  `RS_NAME` varchar(255) CHARACTER SET utf8 NOT NULL COMMENT 'RSM client name',
  `RS_LOGO` longblob
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_dbchanges`
--

DROP TABLE IF EXISTS `rs_dbchanges`;
CREATE TABLE IF NOT EXISTS `rs_dbchanges` (
`RS_ID` int(11) NOT NULL,
  `RS_PREVIOUS_VERSION` varchar(255) NOT NULL,
  `RS_NEW_VERSION` varchar(255) NOT NULL,
  `RS_EXECUTION_DATE` datetime NOT NULL,
  `RS_COMMENTS` longtext
) ENGINE=InnoDB AUTO_INCREMENT=90 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_error_log`
--

DROP TABLE IF EXISTS `rs_error_log`;
CREATE TABLE IF NOT EXISTS `rs_error_log` (
`RS_ID` int(11) unsigned NOT NULL,
  `RS_DATE` datetime NOT NULL,
  `RS_URL` varchar(255) NOT NULL,
  `RS_POST` longtext NOT NULL,
  `RS_RESULT` longtext NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_TYPE` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=65239 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_globals`
--

DROP TABLE IF EXISTS `rs_globals`;
CREATE TABLE IF NOT EXISTS `rs_globals` (
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_NAME` varchar(255) NOT NULL,
  `RS_VALUE` longblob NOT NULL,
  `RS_IMAGE` tinyint(1) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_groups`
--

DROP TABLE IF EXISTS `rs_groups`;
CREATE TABLE IF NOT EXISTS `rs_groups` (
  `RS_GROUP_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_NAME` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_items`
--

DROP TABLE IF EXISTS `rs_items`;
CREATE TABLE IF NOT EXISTS `rs_items` (
  `RS_ITEM_ID` int(11) unsigned NOT NULL,
  `RS_ITEMTYPE_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_ORDER` int(11) unsigned NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_item_properties`
--

DROP TABLE IF EXISTS `rs_item_properties`;
CREATE TABLE IF NOT EXISTS `rs_item_properties` (
  `RS_NAME` varchar(255) NOT NULL,
  `RS_TYPE` varchar(255) DEFAULT NULL,
  `RS_DESCRIPTION` varchar(255) DEFAULT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_CATEGORY_ID` int(11) NOT NULL,
  `RS_ORDER` int(11) unsigned NOT NULL,
  `RS_PROPERTY_ID` int(11) NOT NULL,
  `RS_DEFAULTVALUE` varchar(255) NOT NULL,
  `RS_REFERRED_ITEMTYPE` int(11) DEFAULT NULL,
  `RS_AUDIT_TRAIL` tinyint(1) NOT NULL DEFAULT '0',
  `RS_AUDIT_TRAIL_DESCRIPTION_REQUIRED` tinyint(1) NOT NULL DEFAULT '0',
  `RS_AVOID_DUPLICATION` tinyint(1) NOT NULL DEFAULT '0',
  `RS_SEARCHABLE` tinyint(1) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_item_types`
--

DROP TABLE IF EXISTS `rs_item_types`;
CREATE TABLE IF NOT EXISTS `rs_item_types` (
  `RS_ITEMTYPE_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_NAME` varchar(255) NOT NULL,
  `RS_ORDER` int(11) unsigned NOT NULL,
  `RS_MAIN_PROPERTY_ID` int(11) NOT NULL,
  `RS_ICON` longblob NOT NULL,
  `RS_LAST_ITEM_ID` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_item_type_app_definitions`
--

DROP TABLE IF EXISTS `rs_item_type_app_definitions`;
CREATE TABLE IF NOT EXISTS `rs_item_type_app_definitions` (
`RS_ID` int(11) NOT NULL,
  `RS_NAME` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=70 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_item_type_app_relations`
--

DROP TABLE IF EXISTS `rs_item_type_app_relations`;
CREATE TABLE IF NOT EXISTS `rs_item_type_app_relations` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_ITEMTYPE_APP_ID` int(11) NOT NULL,
  `RS_MODIFIED_DATE` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_item_type_filters`
--

DROP TABLE IF EXISTS `rs_item_type_filters`;
CREATE TABLE IF NOT EXISTS `rs_item_type_filters` (
  `RS_FILTER_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_ITEMTYPE_ID` int(11) unsigned NOT NULL,
  `RS_NAME` varchar(255) NOT NULL,
  `RS_OPERATOR` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_item_type_filter_clauses`
--

DROP TABLE IF EXISTS `rs_item_type_filter_clauses`;
CREATE TABLE IF NOT EXISTS `rs_item_type_filter_clauses` (
  `RS_CLAUSE_ID` int(11) unsigned NOT NULL,
  `RS_FILTER_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_OPERATOR` varchar(255) NOT NULL,
  `RS_VALUE` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_item_type_filter_properties`
--

DROP TABLE IF EXISTS `rs_item_type_filter_properties`;
CREATE TABLE IF NOT EXISTS `rs_item_type_filter_properties` (
  `RS_FILTER_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_lists`
--

DROP TABLE IF EXISTS `rs_lists`;
CREATE TABLE IF NOT EXISTS `rs_lists` (
  `RS_LIST_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_NAME` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_lists_app`
--

DROP TABLE IF EXISTS `rs_lists_app`;
CREATE TABLE IF NOT EXISTS `rs_lists_app` (
`RS_ID` int(11) NOT NULL,
  `RS_NAME` varchar(255) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_lists_relations`
--

DROP TABLE IF EXISTS `rs_lists_relations`;
CREATE TABLE IF NOT EXISTS `rs_lists_relations` (
  `RS_LIST_APP_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_LIST_ID` int(11) NOT NULL,
  `RS_MODIFIED_DATE` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_lists_values_app`
--

DROP TABLE IF EXISTS `rs_lists_values_app`;
CREATE TABLE IF NOT EXISTS `rs_lists_values_app` (
`RS_ID` int(11) NOT NULL,
  `RS_VALUE` varchar(255) NOT NULL,
  `RS_LIST_APP_ID` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_lists_values_relations`
--

DROP TABLE IF EXISTS `rs_lists_values_relations`;
CREATE TABLE IF NOT EXISTS `rs_lists_values_relations` (
  `RS_VALUE_APP_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_VALUE_ID` int(11) NOT NULL,
  `RS_MODIFIED_DATE` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_properties_groups`
--

DROP TABLE IF EXISTS `rs_properties_groups`;
CREATE TABLE IF NOT EXISTS `rs_properties_groups` (
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_GROUP_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_properties_lists`
--

DROP TABLE IF EXISTS `rs_properties_lists`;
CREATE TABLE IF NOT EXISTS `rs_properties_lists` (
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_LIST_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_MULTIVALUES` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_app_definitions`
--

DROP TABLE IF EXISTS `rs_property_app_definitions`;
CREATE TABLE IF NOT EXISTS `rs_property_app_definitions` (
`RS_ID` int(11) NOT NULL,
  `RS_NAME` varchar(255) NOT NULL,
  `RS_ITEM_TYPE_ID` int(11) NOT NULL,
  `RS_DESCRIPTION` varchar(255) NOT NULL,
  `RS_DEFAULTVALUE` varchar(255) DEFAULT NULL,
  `RS_TYPE` varchar(255) NOT NULL,
  `RS_REFERRED_ITEMTYPE` int(11) NOT NULL DEFAULT '0' COMMENT 'The item type ID referred by the property, used to translate the value of it (if 0, the translation will not be done)'
) ENGINE=InnoDB AUTO_INCREMENT=483 DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_app_relations`
--

DROP TABLE IF EXISTS `rs_property_app_relations`;
CREATE TABLE IF NOT EXISTS `rs_property_app_relations` (
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_PROPERTY_APP_ID` int(11) NOT NULL,
  `RS_MODIFIED_DATE` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_colors`
--

DROP TABLE IF EXISTS `rs_property_colors`;
CREATE TABLE IF NOT EXISTS `rs_property_colors` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_DATA` varchar(6) NOT NULL,
  `RS_PROPERTY_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_dates`
--

DROP TABLE IF EXISTS `rs_property_dates`;
CREATE TABLE IF NOT EXISTS `rs_property_dates` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_DATA` date NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_datetime`
--

DROP TABLE IF EXISTS `rs_property_datetime`;
CREATE TABLE IF NOT EXISTS `rs_property_datetime` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_DATA` datetime DEFAULT NULL,
  `RS_PROPERTY_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_files`
--

DROP TABLE IF EXISTS `rs_property_files`;
CREATE TABLE IF NOT EXISTS `rs_property_files` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_NAME` varchar(255) NOT NULL,
  `RS_SIZE` int(11) NOT NULL,
  `RS_DATA` longblob NOT NULL,
  `RS_PROPERTY_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_floats`
--

DROP TABLE IF EXISTS `rs_property_floats`;
CREATE TABLE IF NOT EXISTS `rs_property_floats` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_DATA` decimal(24,12) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_identifiers`
--

DROP TABLE IF EXISTS `rs_property_identifiers`;
CREATE TABLE IF NOT EXISTS `rs_property_identifiers` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_DATA` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_ORDER` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_identifiers_to_itemtypes`
--

DROP TABLE IF EXISTS `rs_property_identifiers_to_itemtypes`;
CREATE TABLE IF NOT EXISTS `rs_property_identifiers_to_itemtypes` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_DATA` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_identifiers_to_properties`
--

DROP TABLE IF EXISTS `rs_property_identifiers_to_properties`;
CREATE TABLE IF NOT EXISTS `rs_property_identifiers_to_properties` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_DATA` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_images`
--

DROP TABLE IF EXISTS `rs_property_images`;
CREATE TABLE IF NOT EXISTS `rs_property_images` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_NAME` varchar(255) NOT NULL,
  `RS_SIZE` int(11) NOT NULL DEFAULT '0',
  `RS_DATA` longblob NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_integers`
--

DROP TABLE IF EXISTS `rs_property_integers`;
CREATE TABLE IF NOT EXISTS `rs_property_integers` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_DATA` int(11) NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_longtext`
--

DROP TABLE IF EXISTS `rs_property_longtext`;
CREATE TABLE IF NOT EXISTS `rs_property_longtext` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_DATA` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_multiIdentifiers`
--

DROP TABLE IF EXISTS `rs_property_multiIdentifiers`;
CREATE TABLE IF NOT EXISTS `rs_property_multiIdentifiers` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_DATA` longtext NOT NULL,
  `RS_PROPERTY_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_ORDER` longtext NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_passwords`
--

DROP TABLE IF EXISTS `rs_property_passwords`;
CREATE TABLE IF NOT EXISTS `rs_property_passwords` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_DATA` text NOT NULL,
  `RS_PROPERTY_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_text`
--

DROP TABLE IF EXISTS `rs_property_text`;
CREATE TABLE IF NOT EXISTS `rs_property_text` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_DATA` TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_values`
--

DROP TABLE IF EXISTS `rs_property_values`;
CREATE TABLE IF NOT EXISTS `rs_property_values` (
  `RS_VALUE_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL DEFAULT '0',
  `RS_LIST_ID` int(11) NOT NULL,
  `RS_VALUE` varchar(255) NOT NULL,
  `RS_ORDER` int(11) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_property_variant`
--

DROP TABLE IF EXISTS `rs_property_variant`;
CREATE TABLE IF NOT EXISTS `rs_property_variant` (
  `RS_ITEMTYPE_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_DATA` longblob NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_server_addresses`
--

DROP TABLE IF EXISTS `rs_server_addresses`;
CREATE TABLE IF NOT EXISTS `rs_server_addresses` (
`RS_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_ADDRESS` varchar(255) NOT NULL,
  `RS_TYPE` varchar(255) NOT NULL,
  `RS_ORDER` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_tokens`
--

DROP TABLE IF EXISTS `rs_tokens`;
CREATE TABLE IF NOT EXISTS `rs_tokens` (
  `RS_ID` int(11) unsigned NOT NULL COMMENT 'Starts from 1 for each client',
  `RS_TOKEN` char(32) COLLATE utf8_bin NOT NULL,
  `RS_CLIENT_ID` int(11) unsigned NOT NULL COMMENT 'Client that owns the token',
  `RS_ENABLED` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'A token can only be used from the outside if it is enabled'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `rs_token_permissions`
--

DROP TABLE IF EXISTS `rs_token_permissions`;
CREATE TABLE IF NOT EXISTS `rs_token_permissions` (
  `RS_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_TOKEN_ID` int(11) unsigned NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_PERMISSION` varchar(255) NOT NULL COMMENT 'CREATE / READ / WRITE / DELETE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rs_users`
--

DROP TABLE IF EXISTS `rs_users`;
CREATE TABLE IF NOT EXISTS `rs_users` (
  `RS_USER_ID` int(11) NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_ITEM_ID` int(11) NOT NULL,
  `RS_LOGIN` varchar(255) NOT NULL DEFAULT '',
  `RS_PASSWORD` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_users_groups`
--

DROP TABLE IF EXISTS `rs_users_groups`;
CREATE TABLE IF NOT EXISTS `rs_users_groups` (
  `RS_GROUP_ID` int(11) unsigned NOT NULL,
  `RS_USER_ID` int(11) unsigned NOT NULL,
  `RS_CLIENT_ID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rs_versions`
--

DROP TABLE IF EXISTS `rs_versions`;
CREATE TABLE IF NOT EXISTS `rs_versions` (
`RS_ID` int(11) unsigned NOT NULL,
  `RS_NAME` varchar(255) NOT NULL DEFAULT '',
  `RS_BUILD` varchar(255) NOT NULL DEFAULT '',
  `RS_OS` varchar(255) DEFAULT NULL,
  `RS_SIGNATURE` int(255) DEFAULT NULL,
  `RS_PUBLIC` int(1) unsigned DEFAULT '0',
  `RS_URL` varchar(255) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=582 DEFAULT CHARSET=latin1;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `rs_actions`
--
ALTER TABLE `rs_actions`
 ADD PRIMARY KEY (`RS_ID`), ADD UNIQUE KEY `name` (`RS_NAME`);

--
-- Indexes for table `rs_actions_clients`
--
ALTER TABLE `rs_actions_clients`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ID`);

--
-- Indexes for table `rs_actions_groups`
--
ALTER TABLE `rs_actions_groups`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_GROUP_ID`,`RS_ACTION_CLIENT_ID`);

--
-- Indexes for table `rs_audit_trail_property_colors`
--
ALTER TABLE `rs_audit_trail_property_colors`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_dates`
--
ALTER TABLE `rs_audit_trail_property_dates`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_datetime`
--
ALTER TABLE `rs_audit_trail_property_datetime`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_files`
--
ALTER TABLE `rs_audit_trail_property_files`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_floats`
--
ALTER TABLE `rs_audit_trail_property_floats`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_identifiers`
--
ALTER TABLE `rs_audit_trail_property_identifiers`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_identifiers_to_itemtypes`
--
ALTER TABLE `rs_audit_trail_property_identifiers_to_itemtypes`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_identifiers_to_properties`
--
ALTER TABLE `rs_audit_trail_property_identifiers_to_properties`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_images`
--
ALTER TABLE `rs_audit_trail_property_images`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_integers`
--
ALTER TABLE `rs_audit_trail_property_integers`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_longtext`
--
ALTER TABLE `rs_audit_trail_property_longtext`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_multiidentifiers`
--
ALTER TABLE `rs_audit_trail_property_multiidentifiers`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_passwords`
--
ALTER TABLE `rs_audit_trail_property_passwords`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_text`
--
ALTER TABLE `rs_audit_trail_property_text`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_audit_trail_property_variant`
--
ALTER TABLE `rs_audit_trail_property_variant`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`,`RS_CHANGED_DATE`);

--
-- Indexes for table `rs_categories`
--
ALTER TABLE `rs_categories`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_CATEGORY_ID`), ADD KEY `itemtype` (`RS_ITEMTYPE_ID`);

--
-- Indexes for table `rs_clients`
--
ALTER TABLE `rs_clients`
 ADD PRIMARY KEY (`RS_ID`);

--
-- Indexes for table `rs_dbchanges`
--
ALTER TABLE `rs_dbchanges`
 ADD PRIMARY KEY (`RS_ID`);

--
-- Indexes for table `rs_error_log`
--
ALTER TABLE `rs_error_log`
 ADD PRIMARY KEY (`RS_ID`);

--
-- Indexes for table `rs_globals`
--
ALTER TABLE `rs_globals`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_NAME`);

--
-- Indexes for table `rs_groups`
--
ALTER TABLE `rs_groups`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_GROUP_ID`);

--
-- Indexes for table `rs_items`
--
ALTER TABLE `rs_items`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`);

--
-- Indexes for table `rs_item_properties`
--
ALTER TABLE `rs_item_properties`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_PROPERTY_ID`), ADD KEY `category` (`RS_CATEGORY_ID`), ADD KEY `referred_itemtype` (`RS_CLIENT_ID`,`RS_REFERRED_ITEMTYPE`);

--
-- Indexes for table `rs_item_types`
--
ALTER TABLE `rs_item_types`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`);

--
-- Indexes for table `rs_item_type_app_definitions`
--
ALTER TABLE `rs_item_type_app_definitions`
 ADD PRIMARY KEY (`RS_ID`), ADD UNIQUE KEY `name` (`RS_NAME`);

--
-- Indexes for table `rs_item_type_app_relations`
--
ALTER TABLE `rs_item_type_app_relations`
 ADD UNIQUE KEY `client_itemtypeapp` (`RS_CLIENT_ID`,`RS_ITEMTYPE_APP_ID`), ADD UNIQUE KEY `client_itemtype` (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`);

--
-- Indexes for table `rs_item_type_filters`
--
ALTER TABLE `rs_item_type_filters`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_FILTER_ID`);

--
-- Indexes for table `rs_item_type_filter_clauses`
--
ALTER TABLE `rs_item_type_filter_clauses`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_CLAUSE_ID`);

--
-- Indexes for table `rs_item_type_filter_properties`
--
ALTER TABLE `rs_item_type_filter_properties`
 ADD PRIMARY KEY (`RS_FILTER_ID`,`RS_CLIENT_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_lists`
--
ALTER TABLE `rs_lists`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_LIST_ID`);

--
-- Indexes for table `rs_lists_app`
--
ALTER TABLE `rs_lists_app`
 ADD PRIMARY KEY (`RS_ID`), ADD UNIQUE KEY `name` (`RS_NAME`);

--
-- Indexes for table `rs_lists_relations`
--
ALTER TABLE `rs_lists_relations`
 ADD UNIQUE KEY `client_list` (`RS_CLIENT_ID`,`RS_LIST_ID`), ADD UNIQUE KEY `client_listApp` (`RS_CLIENT_ID`,`RS_LIST_APP_ID`);

--
-- Indexes for table `rs_lists_values_app`
--
ALTER TABLE `rs_lists_values_app`
 ADD PRIMARY KEY (`RS_ID`), ADD UNIQUE KEY `value` (`RS_VALUE`), ADD KEY `listApp` (`RS_LIST_APP_ID`);

--
-- Indexes for table `rs_lists_values_relations`
--
ALTER TABLE `rs_lists_values_relations`
 ADD UNIQUE KEY `client_value` (`RS_CLIENT_ID`,`RS_VALUE_ID`), ADD UNIQUE KEY `client_valueApp` (`RS_CLIENT_ID`,`RS_VALUE_APP_ID`);

--
-- Indexes for table `rs_properties_groups`
--
ALTER TABLE `rs_properties_groups`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_GROUP_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_properties_lists`
--
ALTER TABLE `rs_properties_lists`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_property_app_definitions`
--
ALTER TABLE `rs_property_app_definitions`
 ADD PRIMARY KEY (`RS_ID`), ADD UNIQUE KEY `name` (`RS_NAME`), ADD KEY `itemtype` (`RS_ITEM_TYPE_ID`);

--
-- Indexes for table `rs_property_app_relations`
--
ALTER TABLE `rs_property_app_relations`
 ADD UNIQUE KEY `client_property` (`RS_CLIENT_ID`,`RS_PROPERTY_ID`), ADD UNIQUE KEY `client_propertyApp` (`RS_CLIENT_ID`,`RS_PROPERTY_APP_ID`);

--
-- Indexes for table `rs_property_colors`
--
ALTER TABLE `rs_property_colors`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_property_dates`
--
ALTER TABLE `rs_property_dates`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_property_datetime`
--
ALTER TABLE `rs_property_datetime`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_property_files`
--
ALTER TABLE `rs_property_files`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_property_floats`
--
ALTER TABLE `rs_property_floats`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_property_identifiers`
--
ALTER TABLE `rs_property_identifiers`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`), ADD KEY `identifier` (`RS_CLIENT_ID`,`RS_PROPERTY_ID`,`RS_DATA`);

--
-- Indexes for table `rs_property_identifiers_to_itemtypes`
--
ALTER TABLE `rs_property_identifiers_to_itemtypes`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`), ADD KEY `identifier` (`RS_CLIENT_ID`,`RS_PROPERTY_ID`,`RS_DATA`);

--
-- Indexes for table `rs_property_identifiers_to_properties`
--
ALTER TABLE `rs_property_identifiers_to_properties`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`), ADD KEY `identifier` (`RS_CLIENT_ID`,`RS_PROPERTY_ID`,`RS_DATA`);

--
-- Indexes for table `rs_property_images`
--
ALTER TABLE `rs_property_images`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_property_integers`
--
ALTER TABLE `rs_property_integers`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_property_longtext`
--
ALTER TABLE `rs_property_longtext`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_property_multiIdentifiers`
--
ALTER TABLE `rs_property_multiIdentifiers`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_property_passwords`
--
ALTER TABLE `rs_property_passwords`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_property_text`
--
ALTER TABLE `rs_property_text`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_property_values`
--
ALTER TABLE `rs_property_values`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_VALUE_ID`), ADD UNIQUE KEY `list_value` (`RS_CLIENT_ID`,`RS_LIST_ID`,`RS_VALUE`);

--
-- Indexes for table `rs_property_variant`
--
ALTER TABLE `rs_property_variant`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_ITEMTYPE_ID`,`RS_ITEM_ID`,`RS_PROPERTY_ID`);

--
-- Indexes for table `rs_server_addresses`
--
ALTER TABLE `rs_server_addresses`
 ADD PRIMARY KEY (`RS_ID`);

--
-- Indexes for table `rs_tokens`
--
ALTER TABLE `rs_tokens`
 ADD PRIMARY KEY (`RS_TOKEN`), ADD UNIQUE KEY `RS_ID` (`RS_ID`,`RS_CLIENT_ID`);

--
-- Indexes for table `rs_token_permissions`
--
ALTER TABLE `rs_token_permissions`
 ADD UNIQUE KEY `RS_CLIENT_ID` (`RS_CLIENT_ID`,`RS_TOKEN_ID`,`RS_PROPERTY_ID`,`RS_PERMISSION`), ADD KEY `token` (`RS_TOKEN_ID`,`RS_PROPERTY_ID`,`RS_PERMISSION`);

--
-- Indexes for table `rs_users`
--
ALTER TABLE `rs_users`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_USER_ID`), ADD UNIQUE KEY `userName` (`RS_CLIENT_ID`,`RS_LOGIN`);

--
-- Indexes for table `rs_users_groups`
--
ALTER TABLE `rs_users_groups`
 ADD PRIMARY KEY (`RS_CLIENT_ID`,`RS_GROUP_ID`,`RS_USER_ID`);

--
-- Indexes for table `rs_versions`
--
ALTER TABLE `rs_versions`
 ADD PRIMARY KEY (`RS_ID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `rs_actions`
--
ALTER TABLE `rs_actions`
MODIFY `RS_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=27;
--
-- AUTO_INCREMENT for table `rs_clients`
--
ALTER TABLE `rs_clients`
MODIFY `RS_ID` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Database Client ID',AUTO_INCREMENT=39;
--
-- AUTO_INCREMENT for table `rs_dbchanges`
--
ALTER TABLE `rs_dbchanges`
MODIFY `RS_ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=90;
--
-- AUTO_INCREMENT for table `rs_error_log`
--
ALTER TABLE `rs_error_log`
MODIFY `RS_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=65239;
--
-- AUTO_INCREMENT for table `rs_item_type_app_definitions`
--
ALTER TABLE `rs_item_type_app_definitions`
MODIFY `RS_ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=70;
--
-- AUTO_INCREMENT for table `rs_lists_app`
--
ALTER TABLE `rs_lists_app`
MODIFY `RS_ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=15;
--
-- AUTO_INCREMENT for table `rs_lists_values_app`
--
ALTER TABLE `rs_lists_values_app`
MODIFY `RS_ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=44;
--
-- AUTO_INCREMENT for table `rs_property_app_definitions`
--
ALTER TABLE `rs_property_app_definitions`
MODIFY `RS_ID` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=483;
--
-- AUTO_INCREMENT for table `rs_server_addresses`
--
ALTER TABLE `rs_server_addresses`
MODIFY `RS_ID` int(11) unsigned NOT NULL AUTO_INCREMENT;
--
-- AUTO_INCREMENT for table `rs_versions`
--
ALTER TABLE `rs_versions`
MODIFY `RS_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=582;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
