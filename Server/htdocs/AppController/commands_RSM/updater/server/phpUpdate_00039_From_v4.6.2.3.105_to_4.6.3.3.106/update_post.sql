
/*Query para eliminar grupo de las carpetas de test que no estan en la raiz. Para ejecutarlo se ha de obtener de la BD el clientID [1], el itemTypeID del grupo de tests [24] y los propertyID de las propiedades grupo de test [230] y carpeta padre (recursiva) [231]*/

UPDATE `rs_property_identifiers` target INNER JOIN `rs_property_identifiers` source ON target.`RS_ITEM_ID`=source.`RS_ITEM_ID` AND target.`RS_ITEMTYPE_ID`=source.`RS_ITEMTYPE_ID` AND target.`RS_CLIENT_ID`=source.`RS_CLIENT_ID` SET target.`RS_DATA`='0' WHERE target.`RS_CLIENT_ID`=1 AND target.`RS_PROPERTY_ID`=230 AND target.`RS_ITEMTYPE_ID`=24 AND source.`RS_PROPERTY_ID`=231 AND source.`RS_DATA` <> '0';



ALTER TABLE `rs_item_types` ADD `RS_ICON` LONGBLOB NOT NULL;

DELETE FROM `rs_actions_groups` 
WHERE (`RS_ACTION_CLIENT_ID`,`RS_CLIENT_ID`) IN (SELECT `RS_ID`,`RS_CLIENT_ID` FROM `rs_actions_clients` WHERE `RS_ACTION_ID` = (SELECT `RS_ID` FROM `rs_actions` WHERE `RS_NAME` = "rsm.mainpanel.groups.access"));

DELETE FROM `rs_actions_clients` WHERE `RS_ACTION_ID` = (SELECT `RS_ID` FROM `rs_actions` WHERE `RS_NAME` = "rsm.mainpanel.groups.access");

DELETE FROM `rs_actions` WHERE `RS_NAME` = "rsm.mainpanel.groups.access";

DELETE FROM `rs_actions_groups` 
WHERE (`RS_ACTION_CLIENT_ID`,`RS_CLIENT_ID`) IN (SELECT `RS_ID`,`RS_CLIENT_ID` FROM `rs_actions_clients` WHERE `RS_ACTION_ID` = (SELECT `RS_ID` FROM `rs_actions` WHERE `RS_NAME` = "rsm.mainpanel.studies.access"));

DELETE FROM `rs_actions_clients` WHERE `RS_ACTION_ID` = (SELECT `RS_ID` FROM `rs_actions` WHERE `RS_NAME` = "rsm.mainpanel.studies.access");

DELETE FROM `rs_actions` WHERE `RS_NAME` = "rsm.mainpanel.studies.access";

INSERT INTO  `rs_item_type_app_definitions` 
(`RS_ID`, `RS_NAME`)
VALUES 
(NULL, 'onlineStoreAttribute');

INSERT INTO `rs_property_app_definitions` 
(`RS_ID` ,`RS_NAME` ,`RS_ITEM_TYPE_ID` ,`RS_DESCRIPTION` ,`RS_DEFAULTVALUE` ,`RS_TYPE` ,`RS_REFERRED_ITEMTYPE`)
VALUES 
(NULL, 'onlineStoreAttribute.storeProductID', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'onlineStoreAttribute'), '', NULL , 'identifier', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'onlineStoreProduct')),
(NULL, 'onlineStoreAttribute.stockItemID', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'onlineStoreAttribute'), '', NULL , 'identifier', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'stockItem')),  
(NULL, 'onlineStoreAttribute.increment', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'onlineStoreAttribute'), '', NULL , 'float', '0');


INSERT INTO `rs_dbchanges` (`RS_ID`, `RS_PREVIOUS_VERSION`, `RS_NEW_VERSION`, `RS_EXECUTION_DATE`, `RS_COMMENTS`) 
	VALUES 
	(NULL, '4.6.2.3.105', '4.6.3.3.106', NOW(), '');