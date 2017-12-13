# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.3.0.3.141', '5.3.1.3.142', NOW(), 'TBD');

# Delete app list used to clasify invoice adresses in invoiceClients
DELETE FROM `rs_lists_app` WHERE `rs_lists_app`.`RS_ID` = 15;

# Delete values in the old app list
DELETE FROM `rs_lists_values_app` WHERE `RS_LIST_APP_ID` = 15;

# Delete relations between old system properties and values of the client (invoiceClient.adresses.billing, invoiceClient.adresses.shipping)
DELETE FROM  `rs_lists_values_relations` WHERE  `RS_VALUE_APP_ID` = 43 OR  `RS_VALUE_APP_ID` = 44;

# Delete relations between the list and the clients lists (list invoiceClient.adresses)
DELETE FROM  `rs_lists_relations` WHERE  `RS_LIST_APP_ID` = 15;

# Delete old 'identifier' system properties for billingAddress and shippingAddress
DELETE FROM  `rs_property_app_definitions` WHERE  `RS_NAME` =  "invoice.client.billingAdress" OR  `RS_NAME` =  "invoice.client.shippingAdress";

# Delete old App properties abount CRM-Account delivery address and CRM-Account invoice address
DELETE FROM `rs_property_app_definitions` WHERE `rs_property_app_definitions`.`RS_ID` = 91;
DELETE FROM `rs_property_app_definitions` WHERE `rs_property_app_definitions`.`RS_ID` = 92;
DELETE FROM `rs_property_app_definitions` WHERE `rs_property_app_definitions`.`RS_ID` = 93;
DELETE FROM `rs_property_app_definitions` WHERE `rs_property_app_definitions`.`RS_ID` = 463;
DELETE FROM `rs_property_app_definitions` WHERE `rs_property_app_definitions`.`RS_ID` = 464;
DELETE FROM `rs_property_app_definitions` WHERE `rs_property_app_definitions`.`RS_ID` = 465;
DELETE FROM `rs_property_app_definitions` WHERE `rs_property_app_definitions`.`RS_ID` = 466;
DELETE FROM `rs_property_app_definitions` WHERE `rs_property_app_definitions`.`RS_ID` = 467;
DELETE FROM `rs_property_app_definitions` WHERE `rs_property_app_definitions`.`RS_ID` = 468;
DELETE FROM `rs_property_app_definitions` WHERE `rs_property_app_definitions`.`RS_ID` = 469;

# Delete relations with old App properties abount CRM-Account delivery address and CRM-Account invoice address for all RSM clients
DELETE FROM `rs_property_app_relations` WHERE `RS_PROPERTY_APP_ID` = 91;
DELETE FROM `rs_property_app_relations` WHERE `RS_PROPERTY_APP_ID` = 92;
DELETE FROM `rs_property_app_relations` WHERE `RS_PROPERTY_APP_ID` = 93;
DELETE FROM `rs_property_app_relations` WHERE `RS_PROPERTY_APP_ID` = 463;
DELETE FROM `rs_property_app_relations` WHERE `RS_PROPERTY_APP_ID` = 464;
DELETE FROM `rs_property_app_relations` WHERE `RS_PROPERTY_APP_ID` = 465;
DELETE FROM `rs_property_app_relations` WHERE `RS_PROPERTY_APP_ID` = 466;
DELETE FROM `rs_property_app_relations` WHERE `RS_PROPERTY_APP_ID` = 467;
DELETE FROM `rs_property_app_relations` WHERE `RS_PROPERTY_APP_ID` = 468;
DELETE FROM `rs_property_app_relations` WHERE `RS_PROPERTY_APP_ID` = 469;

# Create billingAddress as system properties inside invoiceClients
INSERT INTO  `rs_property_app_definitions` (  `RS_ID` ,  `RS_NAME` ,  `RS_ITEM_TYPE_ID` ,  `RS_DESCRIPTION` ,  `RS_DEFAULTVALUE` ,  `RS_TYPE` ,  `RS_REFERRED_ITEMTYPE` ) 
VALUES (
'458',  'invoice.client.billingAddress',  '38',  '', NULL ,  'text',  '0'
), (
'459',  'invoice.client.billingCity',  '38',  '', NULL ,  'text',  '0'
), (
'460',  'invoice.client.billingCountry',  '38',  '', NULL ,  'text',  '0'
), (
'461',  'invoice.client.billingPostCode',  '38',  '', NULL ,  'text',  '0'
), (
'462',  'invoice.client.billingProvince',  '38',  '', NULL ,  'text',  '0'
);

#Create the app item type for crmAdresses
INSERT INTO  rs_item_type_app_definitions (RS_ID ,RS_NAME) VALUES ('39',  'crmAdresses');

#Create system properties to CRM-Accounts addresses
INSERT INTO  `rs_property_app_definitions` (  `RS_ID` ,  `RS_NAME` ,  `RS_ITEM_TYPE_ID` ,  `RS_DESCRIPTION` ,  `RS_DEFAULTVALUE` ,  `RS_TYPE` ,  `RS_REFERRED_ITEMTYPE` ) 
VALUES (
'463',  'crmAdresses.address',  '39',  '', NULL ,  'text',  '0'
), (
'464',  'crmAdresses.postcode',  '39',  '', NULL ,  'text',  '0'
), (
'465',  'crmAdresses.city',  '39',  '', NULL ,  'text',  '0'
), (
'466',  'crmAdresses.province',  '39',  '', NULL ,  'text',  '0'
), (
'467',  'crmAdresses.country',  '39',  '', NULL ,  'text',  '0'
);

# Delete old internal links in properties "Default invoice address" and "Default delivery address"
UPDATE  `rs_item_properties` SET  `RS_REFERRED_ITEMTYPE` =  '0' WHERE  `rs_item_properties`.`RS_CLIENT_ID` = 1 AND  `rs_item_properties`.`RS_PROPERTY_ID` = 940;
UPDATE  `rs_item_properties` SET  `RS_REFERRED_ITEMTYPE` =  '0' WHERE  `rs_item_properties`.`RS_CLIENT_ID` = 1 AND  `rs_item_properties`.`RS_PROPERTY_ID` = 943;

#Create new system properties to Default Addresses in CRM-Accounts
INSERT INTO  `rs_property_app_definitions` (  `RS_ID` ,  `RS_NAME` ,  `RS_ITEM_TYPE_ID` ,  `RS_DESCRIPTION` ,  `RS_DEFAULTVALUE` ,  `RS_TYPE` ,  `RS_REFERRED_ITEMTYPE` ) 
VALUES (
'468',  'crmAccounts.default.delivery.address',  '59',  '', NULL ,  'identifier',  '39'
), (
'469',  'crmAccounts.default.invoice.address',  '59',  '', NULL ,  'identifier',  '39'
);

# Update internal property from multiidentifier to identifier
UPDATE  `rs_property_app_definitions` SET  `RS_TYPE` =  "identifier" WHERE  `RS_NAME` =  "onlineStoreAttribute.stockItemID";

# Update client properties related with onlineStoreAtribute.stockItemID
UPDATE rs_item_properties, rs_property_app_relations
SET rs_item_properties.RS_TYPE =  "identifier" WHERE
rs_item_properties.RS_CLIENT_ID = rs_property_app_relations.RS_CLIENT_ID AND
rs_item_properties.RS_PROPERTY_ID = rs_property_app_relations.RS_PROPERTY_ID AND
rs_property_app_relations.RS_PROPERTY_APP_ID =404;

# Move values from table rs_property_multiIdentifiers to rs_property_identifiers
INSERT INTO  `rs_property_identifiers` (  `RS_ITEMTYPE_ID` ,  `RS_ITEM_ID` ,  `RS_DATA` ,  `RS_PROPERTY_ID` ,  `RS_CLIENT_ID` ) 
SELECT rs_property_multiIdentifiers.RS_ITEMTYPE_ID, rs_property_multiIdentifiers.RS_ITEM_ID, rs_property_multiIdentifiers.RS_DATA, rs_property_multiIdentifiers.RS_PROPERTY_ID, rs_property_multiIdentifiers.RS_CLIENT_ID
FROM rs_property_multiIdentifiers, rs_property_app_relations
WHERE rs_property_multiIdentifiers.RS_CLIENT_ID = rs_property_app_relations.RS_CLIENT_ID
AND rs_property_multiIdentifiers.RS_PROPERTY_ID = rs_property_app_relations.RS_PROPERTY_ID
AND rs_property_app_relations.RS_PROPERTY_APP_ID =404;

# Delete old values
DELETE FROM  rs_property_multiIdentifiers
USING rs_property_multiIdentifiers INNER JOIN rs_property_app_relations ON
rs_property_multiIdentifiers.RS_CLIENT_ID = rs_property_app_relations.RS_CLIENT_ID
AND rs_property_multiIdentifiers.RS_PROPERTY_ID = rs_property_app_relations.RS_PROPERTY_ID
AND rs_property_app_relations.RS_PROPERTY_APP_ID =404;

# Delete unused table
DROP TABLE rs_action_scripts;