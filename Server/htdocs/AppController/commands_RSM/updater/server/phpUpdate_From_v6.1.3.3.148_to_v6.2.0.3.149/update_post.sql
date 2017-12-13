# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.1.3.3.148', '6.2.0.3.149', NOW(), 'Remove POS module references');

UPDATE `rs_property_app_definitions` SET `RS_TYPE` = 'float' WHERE `rs_property_app_definitions`.`RS_ID` = 386;
UPDATE `rs_property_app_definitions` SET `RS_TYPE` = 'float' WHERE `rs_property_app_definitions`.`RS_ID` = 387;

#Remove POS module
DELETE FROM `rs_actions_groups` WHERE EXISTS (
    SELECT 1 FROM `rs_actions_clients` INNER JOIN `rs_actions` ON `rs_actions_clients`.`RS_ACTION_ID` = `rs_actions`.`RS_ID`
    WHERE `rs_actions`.`RS_NAME` = 'rsm.mainpanel.pos.access'
    AND `rs_actions_groups`.`RS_ACTION_CLIENT_ID` = `rs_actions_clients`.`RS_ID`
    AND `rs_actions_groups`.`RS_CLIENT_ID` = `rs_actions_clients`.`RS_CLIENT_ID`
);
DELETE FROM `rs_actions_clients` WHERE `RS_ACTION_ID` = (SELECT `RS_ID` FROM `rs_actions` WHERE `RS_NAME` = 'rsm.mainpanel.pos.access');
DELETE FROM `rs_actions` WHERE `RS_NAME` = 'rsm.mainpanel.pos.access';


# New pending events properties
REPLACE INTO rs_item_type_app_definitions (RS_ID, RS_NAME) VALUES (67, 'serverApp');
REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (471, 'serverApp.name', 67, 'Name of the server app', 'text', 0);

REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (472, 'scheduledEvents.node', 40, 'ID pertaining to the node that will execute the event', 'identifier', 67);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (473,  'scheduledEvents.priority', 40,  'Integer code indicating the execution priority (1 = highest priority)', 'integer',  0);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (474,  'eventTrigger.priority', 11,  'Integer code indicating the execution priority (1 = highest priority)', 'integer',  0);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (475,  'eventTrigger.avoidDuplication', 11,  'Boolean indicating if creating duplicated jobs allowed', 'text',  0);


# Recargo de equivalencia en clientes y facturas
REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (476,  'financial.documents.equiv', 37,  'Recargo de equivalencia', 'identifier2property',  0);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (477,  'invoice.client.equiv', 38,  'Recargo de equivalencia', 'float',  0);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (478,  'crmAccounts.invoicing.equiv', 59,  'Recargo de equivalencia', 'text',  0);


# New payment gateway configuration properties
REPLACE INTO rs_item_type_app_definitions (RS_ID, RS_NAME) VALUES (68, 'paymentMethod');
REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (479,  'paymentMethod.name', 68,  'Payment Method Identifier', 'text',  0);

REPLACE INTO rs_item_type_app_definitions (RS_ID, RS_NAME) VALUES (69, 'paymentParameter');
REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (480,  'paymentParameter.name', 69,  'Payment Parameter Identifier', 'text',  0);
REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (481,  'paymentParameter.value', 69,  'Payment Parameter Value', 'longtext',  0);
REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (482,  'paymentParameter.method', 69,  'ID of the releated Payment Method', 'identifier',  68);
