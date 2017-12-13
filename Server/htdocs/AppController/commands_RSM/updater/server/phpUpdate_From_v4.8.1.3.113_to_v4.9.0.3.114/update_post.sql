# Insert the application version with changes in the PHP layer
INSERT INTO `rs_dbchanges` (`RS_ID`, `RS_PREVIOUS_VERSION`, `RS_NEW_VERSION`, `RS_EXECUTION_DATE`, `RS_COMMENTS`) 
VALUES (NULL, '4.8.1.3.113', '4.9.0.3.114', NOW(), 'Added support for Events');

# Insert the new app definitions for catalogs support
INSERT INTO  `rs_item_type_app_definitions` (`RS_ID` ,`RS_NAME`) VALUES ('30',  'catalog');
INSERT INTO  `rs_item_type_app_definitions` (`RS_ID` ,`RS_NAME`) VALUES ('34',  'catalogItem');
INSERT INTO  `rs_item_type_app_definitions` (`RS_ID` ,`RS_NAME`) VALUES ('36',  'catalogCategory');

# Insert the new app properties for catalogs support
INSERT INTO  `rs_property_app_definitions` (`RS_ID` ,`RS_NAME` ,`RS_ITEM_TYPE_ID` ,`RS_DESCRIPTION` ,`RS_DEFAULTVALUE` ,`RS_TYPE` ,`RS_REFERRED_ITEMTYPE`)
VALUES (43,  'catalogCategory.parentID',  36,  'The parent category of this category', NULL ,  'identifier',  36);

INSERT INTO  `rs_property_app_definitions` (`RS_ID` ,`RS_NAME` ,`RS_ITEM_TYPE_ID` ,`RS_DESCRIPTION` ,`RS_DEFAULTVALUE` ,`RS_TYPE` ,`RS_REFERRED_ITEMTYPE`)
VALUES (56,  'catalog.parentCategoryID',  30,  'The parent category for this catalog', NULL ,  'identifiers',  36);

INSERT INTO  `rs_property_app_definitions` (`RS_ID` ,`RS_NAME` ,`RS_ITEM_TYPE_ID` ,`RS_DESCRIPTION` ,`RS_DEFAULTVALUE` ,`RS_TYPE` ,`RS_REFERRED_ITEMTYPE`)
VALUES (57,  'catalogItem.parentID',  34,  'The parent catalog for this item', NULL ,  'identifier',  '30');

# Insert the new app definitions for events support
INSERT INTO  `rs_item_type_app_definitions` (`RS_ID` ,`RS_NAME`) VALUES ('10',  'event');
INSERT INTO  `rs_item_type_app_definitions` (`RS_ID` ,`RS_NAME`) VALUES ('11',  'eventTrigger');
INSERT INTO  `rs_item_type_app_definitions` (`RS_ID` ,`RS_NAME`) VALUES ('29',  'eventCategory');

# Insert the new app properties for events support
INSERT INTO `rs_property_app_definitions` (`RS_ID`, `RS_NAME`, `RS_ITEM_TYPE_ID`, `RS_DESCRIPTION`, `RS_DEFAULTVALUE`, `RS_TYPE`, `RS_REFERRED_ITEMTYPE`)
VALUES (30, 'event.actions', 10, 'Stores the code that will be executed once the action is triggered', NULL, 'longtext', '0');

INSERT INTO `rs_property_app_definitions` (`RS_ID`, `RS_NAME`, `RS_ITEM_TYPE_ID`, `RS_DESCRIPTION`, `RS_DEFAULTVALUE`, `RS_TYPE`, `RS_REFERRED_ITEMTYPE`)
VALUES (42, 'eventCategory.parentCategoryID', 29, 'The parent category of this category', NULL, 'identifier', '29');

INSERT INTO `rs_property_app_definitions` (`RS_ID`, `RS_NAME`, `RS_ITEM_TYPE_ID`, `RS_DESCRIPTION`, `RS_DEFAULTVALUE`, `RS_TYPE`, `RS_REFERRED_ITEMTYPE`)
VALUES (41, 'event.parentCategoryID', 10, 'The parent category for this event', NULL, 'identifier', '29');

INSERT INTO `rs_property_app_definitions` (`RS_ID`, `RS_NAME`, `RS_ITEM_TYPE_ID`, `RS_DESCRIPTION`, `RS_DEFAULTVALUE`, `RS_TYPE`, `RS_REFERRED_ITEMTYPE`)
VALUES (37, 'eventTrigger.eventID', 11, 'Reference to the triggered event', NULL, 'identifiers', '10');

INSERT INTO `rs_property_app_definitions` (`RS_ID`, `RS_NAME`, `RS_ITEM_TYPE_ID`, `RS_DESCRIPTION`, `RS_DEFAULTVALUE`, `RS_TYPE`, `RS_REFERRED_ITEMTYPE`)
VALUES (39, 'eventTrigger.type', 11, 'The type of trigger', NULL, 'text', '0');

INSERT INTO `rs_property_app_definitions` (`RS_ID`, `RS_NAME`, `RS_ITEM_TYPE_ID`, `RS_DESCRIPTION`, `RS_DEFAULTVALUE`, `RS_TYPE`, `RS_REFERRED_ITEMTYPE`)
VALUES (40, 'eventTrigger.data', 11, 'Trigger configuration, depending of the type of trigger', NULL, 'text', '0');

# The events module access permission
INSERT INTO `rs_actions` (`RS_ID`, `RS_NAME`, `RS_DESCRIPTION`, `RS_APPLICATION_NAME`, `RS_CONFIGURATION_ITEMTYPE`)
VALUES ('8', 'rsm.mainpanel.events.access', 'This modules configures different triggers and events and actions to execute once the event has been triggered', 'Events', 'configuration.module.generic');

# App trigger types list
INSERT INTO  `rs_lists_app` (`RS_ID` , `RS_NAME`)
VALUES ('1',  'trigger.types');

# App trigger type properties list
INSERT INTO  `rs_lists_values_app` (`RS_ID`, `RS_VALUE`, `RS_LIST_APP_ID`)
VALUES ('4',  'trigger.type.url',  '1'); 

INSERT INTO  `rs_lists_values_app` (`RS_ID`, `RS_VALUE`, `RS_LIST_APP_ID`)
VALUES ('5',  'trigger.type.schedule',  '1'); 

INSERT INTO  `rs_lists_values_app` (`RS_ID`, `RS_VALUE`, `RS_LIST_APP_ID`)
VALUES ('6',  'trigger.type.create.item',  '1'); 

INSERT INTO  `rs_lists_values_app` (`RS_ID`, `RS_VALUE`, `RS_LIST_APP_ID`)
VALUES ('7',  'trigger.type.edit.item',  '1'); 

INSERT INTO  `rs_lists_values_app` (`RS_ID`, `RS_VALUE`, `RS_LIST_APP_ID`)
VALUES ('41',  'trigger.type.delete.item',  '1');

# Create tables in order to support tokens in the API
CREATE TABLE IF NOT EXISTS `rs_tokens` (
  `RS_ID` int(11) unsigned NOT NULL COMMENT 'Starts from 1 for each client',
  `RS_TOKEN` char(32) COLLATE utf8_bin NOT NULL,
  `RS_CLIENT_ID` int(11) unsigned NOT NULL COMMENT 'Client that owns the token',
  `RS_ENABLED` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT 'A token can only be used from the outside if it is enabled',
  PRIMARY KEY (`RS_TOKEN`),
  UNIQUE KEY `RS_ID` (`RS_ID`,`RS_CLIENT_ID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE IF NOT EXISTS `rs_token_permissions` (
  `RS_CLIENT_ID` int(11) unsigned NOT NULL,
  `RS_TOKEN_ID` int(11) unsigned NOT NULL,
  `RS_PROPERTY_ID` int(11) unsigned NOT NULL,
  `RS_PERMISSION` varchar(255) NOT NULL COMMENT 'CREATE / READ / WRITE / DELETE',
  UNIQUE KEY `RS_CLIENT_ID` (`RS_CLIENT_ID`,`RS_TOKEN_ID`,`RS_PROPERTY_ID`,`RS_PERMISSION`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

# Create the API module
INSERT INTO  `rs_actions` (
`RS_ID` ,
`RS_NAME` ,
`RS_DESCRIPTION` ,
`RS_APPLICATION_NAME` ,
`RS_CONFIGURATION_ITEMTYPE`
)
VALUES (
'9',  'rsm.mainpanel.api.access',  'API configuration module',  'Application Programming Interface',  'configuration.module.generic'
);