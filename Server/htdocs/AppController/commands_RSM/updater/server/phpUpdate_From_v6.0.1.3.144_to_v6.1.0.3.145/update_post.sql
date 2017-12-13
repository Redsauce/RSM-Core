# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.0.1.3.144', '6.1.0.3.145', NOW(), 'Support for token identification in audit trail and event execution with user credentials, multiple servers support. Support to load HTML code in the HTML modules if no URL is defined.');

# Add RS_TOKEN field to rs_audit_trail_property_colors table if not exists
ALTER TABLE `rs_audit_trail_property_colors` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_dates table if not exists
ALTER TABLE `rs_audit_trail_property_dates` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_datetime table if not exists
ALTER TABLE `rs_audit_trail_property_datetime` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_files table if not exists
ALTER TABLE `rs_audit_trail_property_files` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_floats table if not exists
ALTER TABLE `rs_audit_trail_property_floats` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_identifiers table if not exists
ALTER TABLE `rs_audit_trail_property_identifiers` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_identifiers_to_itemtypes table if not exists
ALTER TABLE `rs_audit_trail_property_identifiers_to_itemtypes` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_identifiers_to_properties table if not exists
ALTER TABLE `rs_audit_trail_property_identifiers_to_properties` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_images table if not exists
ALTER TABLE `rs_audit_trail_property_images` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_integers table if not exists
ALTER TABLE `rs_audit_trail_property_integers` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_longtext table if not exists
ALTER TABLE `rs_audit_trail_property_longtext` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_multiidentifiers table if not exists
ALTER TABLE `rs_audit_trail_property_multiidentifiers` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_passwords table if not exists
ALTER TABLE `rs_audit_trail_property_passwords` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_text table if not exists
ALTER TABLE `rs_audit_trail_property_text` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add RS_TOKEN field to rs_audit_trail_property_variant table if not exists
ALTER TABLE `rs_audit_trail_property_variant` ADD COLUMN IF NOT EXISTS `RS_TOKEN` CHAR( 32 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL AFTER `RS_USER_ID`;

# Add app property to relate scheculed events with the staff responsible of the execution
REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (470,  'scheduledEvents.userLogin', 40,  'The staff user who created the event', 'identifier',  3);

# Add property to relate the scheduled events scheduled date
REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (117, 'event.lastScheduledDate', 10, 'Stores the last time the event was scheduled', 'datetime', 0);

#Create table for multiple read/write servers support
CREATE TABLE IF NOT EXISTS `rs_server_addresses` (
  `RS_ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `RS_CLIENT_ID` int(11) NOT NULL,
  `RS_ADDRESS` varchar(255) NOT NULL,
  `RS_TYPE` varchar(255) NOT NULL,
  `RS_ORDER` int(11) NOT NULL,
  PRIMARY KEY (`RS_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

# Convert a table to InnoDB
ALTER TABLE rs_property_variant ENGINE=InnoDB;

#Add index for token search
ALTER TABLE `rs_token_permissions` ADD KEY `token` (`RS_TOKEN_ID`,`RS_PROPERTY_ID`,`RS_PERMISSION`);

# Add a property in order to support discounts in every concept
REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (118, 'financial.documents.concept.%discount', 37, 'Discount percentage to apply to the concept total', 'identifier2property', 0);

# Set the right name for the reports
UPDATE rs_item_type_app_definitions SET RS_NAME = 'report' WHERE rs_item_type_app_definitions.RS_ID = 14;

# Add new fields in the table rs_error_log
ALTER TABLE rs_error_log ADD COLUMN IF NOT EXISTS RS_CLIENT_ID INT(11) NOT NULL ;
ALTER TABLE rs_error_log ADD COLUMN IF NOT EXISTS RS_TYPE VARCHAR(255) NOT NULL ;

# Support for reports includes
REPLACE INTO rs_item_type_app_definitions (RS_ID, RS_NAME) VALUES (41, 'reportInclude');
REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (119, 'reportInclude.script', 41, 'Dependencies containing general functions suitable to be used on any report', 'longtext', 0);
REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (120, 'reportInclude.reportIDs', 41, 'IDs pertaining to the reports that use this include', 'identifiers', 14);
REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (125, 'reportInclude.name', 41, 'Name of the dependency', 'text', 0);

# Support for report categories
REPLACE INTO rs_item_type_app_definitions (RS_ID, RS_NAME) VALUES (43, 'reportCategory');
REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (122, 'reportCategory.name', 43, 'Name of the report category', 'text', 0);
REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (123, 'reportCategory.parent', 43, 'ID pertaining to the parent category of the category', 'identifier', 43);

# Linking the report to the category
REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (124, 'report.parentCategory', 14, 'ID pertaining to the parent category of the report', 'identifier', 43);

UPDATE rs_property_app_definitions SET RS_NAME = 'report.name' WHERE RS_ID = 133;
UPDATE rs_property_app_definitions SET RS_NAME = 'report.FullHTMLReport' WHERE RS_ID = 134;
UPDATE rs_property_app_definitions SET RS_NAME = 'report.script' WHERE RS_ID = 457;

#Add switch to properties to determine if they can be used in searchs
ALTER TABLE rs_item_properties ADD COLUMN IF NOT EXISTS RS_SEARCHABLE TINYINT(1) NOT NULL DEFAULT '1' ;

#Allow to execute HTML code in the HTML modules if not URL is defined
REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (121, 'configuration.module.HTMLmodule.HTML', 65, 'HTML code to load if the URL is not defined', 'longtext', '0');

#New order value in identifier properties for childs sorting
ALTER TABLE `rs_property_identifiers` ADD COLUMN IF NOT EXISTS `RS_ORDER` INT(11) NOT NULL ;
ALTER TABLE `rs_property_multiIdentifiers` ADD COLUMN IF NOT EXISTS `RS_ORDER` LONGTEXT NOT NULL ;
