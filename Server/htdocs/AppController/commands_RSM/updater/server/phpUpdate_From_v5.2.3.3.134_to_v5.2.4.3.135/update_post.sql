# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.2.3.3.134', '5.2.4.3.135', NOW(), 'Support for task groups');

INSERT INTO  `rs_item_type_app_definitions` 
(`RS_ID`, `RS_NAME`)
VALUES 
(28, 'tasksGroup');

INSERT INTO `rs_property_app_definitions` 
(`RS_ID` ,`RS_NAME` ,`RS_ITEM_TYPE_ID` ,`RS_DESCRIPTION` ,`RS_DEFAULTVALUE` ,`RS_TYPE` ,`RS_REFERRED_ITEMTYPE`)
VALUES 
(NULL, 'tasksGroup.name', 28, '', NULL , 'text', '0'), 
(NULL, 'tasksGroup.projectID', 28, '', NULL , 'identifier', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'projects')), 
(NULL, 'tasksGroup.parentID', 28, '', NULL , 'identifier', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'tasksGroup')),
(NULL, 'tasksGroup.staff', 28, '', NULL , 'identifiers', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'staff')),
(NULL, 'tasksGroup.status', 28, '', NULL , 'text', '0'), 
(NULL, 'tasksGroup.startDate', 28, '', NULL , 'date', '0'),
(NULL, 'tasksGroup.endDate', 28, '', NULL , 'date', '0'),
(NULL, 'tasksGroup.currentTime', 28, '', NULL , 'float', '0'),
(NULL, 'tasksGroup.totalTime', 28, '', NULL , 'float', '0');

UPDATE `rs_property_app_definitions` SET `RS_REFERRED_ITEMTYPE` = (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'tasksGroup') WHERE `rs_property_app_definitions`.`RS_NAME` = 'tasks.parentID';

# Commented lines due to an error when executing on server. Seems the value is already in the database
# Support for names and prices in catalog products
# INSERT INTO `rsm`.`rs_property_app_definitions`
# (`RS_ID`, `RS_NAME`, `RS_ITEM_TYPE_ID`, `RS_DESCRIPTION`, `RS_DEFAULTVALUE`, `RS_TYPE`)
# VALUES (NULL, 'catalogItem.name', '34', 'The name of the item', NULL, 'text');

# INSERT INTO `rsm`.`rs_property_app_definitions`
# (`RS_ID`, `RS_NAME`, `RS_ITEM_TYPE_ID`, `RS_DESCRIPTION`, `RS_DEFAULTVALUE`, `RS_TYPE`, `RS_REFERRED_ITEMTYPE`)
# VALUES (NULL, 'catalogItem.price', '34', 'The price of the item', NULL, 'float', '');