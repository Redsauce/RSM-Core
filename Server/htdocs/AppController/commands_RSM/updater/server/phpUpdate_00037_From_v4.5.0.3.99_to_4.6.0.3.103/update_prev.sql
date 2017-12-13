
INSERT INTO `rs_property_app_definitions` 
(`RS_NAME`, `RS_ITEM_TYPE_ID`, `RS_DESCRIPTION`, `RS_DEFAULTVALUE`, `RS_TYPE`, `RS_REFERRED_ITEMTYPE`) 
VALUES 
('stockItem.amount', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'stockItem'), '', NULL, 'integer', '0'),
('stockItem.amountSold', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'stockItem'), '', NULL, 'integer', '0'),
('pendingStock.amount', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'pendingStock'), '', NULL, 'integer', '0');

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'pendingStock.ownerID');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='pendingStock.ownerID';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID` IN (SELECT RS_ID FROM rs_property_app_definitions WHERE RS_ITEM_TYPE_ID = (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'stockVolumeItem'));

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_ITEM_TYPE_ID`=(SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'stockVolumeItem');

DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID=(SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'stockVolumeItem');

DELETE FROM rs_item_type_app_definitions WHERE RS_NAME = 'stockVolumeItem';


DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'stockItem.ownerID');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='stockItem.ownerID';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'stockItem.saleDate');

DELETE FROM `rs_property_app_definitions` 
WHERE `RS_NAME`='stockItem.saleDate';



INSERT INTO `rs_property_app_definitions` 
(`RS_NAME`, `RS_ITEM_TYPE_ID`, `RS_DESCRIPTION`, `RS_DEFAULTVALUE`, `RS_TYPE`, `RS_REFERRED_ITEMTYPE`) 
VALUES 
('concepts.pendingStockID', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'concepts'), '', NULL, 'identifier', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'pendingStock'));


INSERT INTO  `rs_item_type_app_definitions` 
(`RS_ID`, `RS_NAME`)
VALUES 
(NULL, 'configuration.module.generic'),
(NULL, 'revisionHistory'),
(NULL, 'productBuild');


INSERT INTO `rs_property_app_definitions` 
(`RS_ID` ,`RS_NAME` ,`RS_ITEM_TYPE_ID` ,`RS_DESCRIPTION` ,`RS_DEFAULTVALUE` ,`RS_TYPE` ,`RS_REFERRED_ITEMTYPE`)
VALUES 
(NULL, 'configuration.module.generic.name', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.generic'), '', NULL , 'text', '0'), 
(NULL, 'configuration.module.generic.description', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.generic'), '', NULL , 'text', '0'), 
(NULL, 'configuration.module.generic.logo', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.generic'), '', NULL , 'image', '0');


INSERT INTO  `rs_item_type_app_definitions` 
(`RS_ID`, `RS_NAME`)
VALUES 
(NULL, 'configuration.module.genericModule');


INSERT INTO `rs_property_app_definitions` 
(`RS_ID` ,`RS_NAME` ,`RS_ITEM_TYPE_ID` ,`RS_DESCRIPTION` ,`RS_DEFAULTVALUE` ,`RS_TYPE` ,`RS_REFERRED_ITEMTYPE`)
VALUES 
(NULL, 'configuration.module.genericModule.name', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.genericModule'), '', NULL , 'text', '0'), 
(NULL, 'configuration.module.genericModule.description', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.genericModule'), '', NULL , 'text', '0'), 
(NULL, 'configuration.module.genericModule.logo', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.genericModule'), '', NULL , 'image', '0'), 
(NULL, 'configuration.module.genericModule.allowed', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.genericModule'), '', NULL , 'text', '0'), 
(NULL, 'configuration.module.genericModule.base', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'configuration.module.genericModule'), '', NULL , 'text', '0');


ALTER TABLE `rs_actions` ADD `RS_CONFIGURATION_ITEMTYPE` VARCHAR( 255 ) NOT NULL DEFAULT 'configuration.module.generic';

INSERT INTO `rs_actions` (`RS_NAME`, `RS_DESCRIPTION`, `RS_APPLICATION_NAME`, `RS_APPLICATION_LOGO`, `RS_CONFIGURATION_ITEMTYPE`) VALUES
('rsm.mainpanel.generic.access', 'Generic module', 'Generic module', '', 'configuration.module.genericModule');

ALTER TABLE `rs_actions_clients` ADD `RS_ID` INT( 11 ) UNSIGNED NOT NULL FIRST ,
ADD `RS_CONFIGURATION_ITEM_ID` INT( 11 ) UNSIGNED NOT NULL AFTER `RS_ID`;

ALTER TABLE `rs_actions_groups` ADD `RS_ACTION_CLIENT_ID` INT( 11 ) UNSIGNED NOT NULL FIRST; 


