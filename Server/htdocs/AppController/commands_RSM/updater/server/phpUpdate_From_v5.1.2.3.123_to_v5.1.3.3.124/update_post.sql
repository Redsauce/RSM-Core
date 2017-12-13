# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS) 
VALUES (NULL, '5.1.2.3.123', '5.1.3.3.124', NOW(), 'Make the operations child of bank accounts + support for financial documents concepts');

# Make the operations child of bank accounts
UPDATE rs_property_app_definitions
SET    RS_NAME =  'operations.bankAccount', RS_TYPE = 'identifier', RS_REFERRED_ITEMTYPE = 66
WHERE  rs_property_app_definitions.RS_ID = 79;

# Remove the relationships with property ID 79 for all the clients as it changed its property type
DELETE FROM `rs_property_app_relations` WHERE `RS_PROPERTY_APP_ID` = 79;

# Remove unused columns previously used for client version update
ALTER TABLE `rs_versions` DROP `RS_LANGUAGE`;

# Support for VAT and Retention
INSERT INTO rs_property_app_definitions (
RS_NAME ,
RS_ITEM_TYPE_ID ,
RS_DESCRIPTION ,
RS_TYPE ,
RS_DEFAULTVALUE ,
RS_REFERRED_ITEMTYPE
)
VALUES (
'financial.documents.vat',  '37',  'VAT',  'identifier2property', NULL, '0'
);

INSERT INTO rs_property_app_definitions (
RS_NAME ,
RS_ITEM_TYPE_ID ,
RS_DESCRIPTION ,
RS_TYPE ,
RS_DEFAULTVALUE ,
RS_REFERRED_ITEMTYPE
)
VALUES (
'financial.documents.ret',  '37',  'Retention',  'identifier2property', NULL, '0'
);

INSERT INTO rs_property_app_definitions (
RS_NAME ,
RS_ITEM_TYPE_ID ,
RS_DESCRIPTION ,
RS_TYPE ,
RS_DEFAULTVALUE ,
RS_REFERRED_ITEMTYPE
)
VALUES (
'financial.documents.concepts.ret',  '37',  'Concept Retention',  'identifier2property', NULL, '0'
);

INSERT INTO rs_property_app_definitions (
RS_NAME ,
RS_ITEM_TYPE_ID ,
RS_DESCRIPTION ,
RS_TYPE ,
RS_DEFAULTVALUE ,
RS_REFERRED_ITEMTYPE
)
VALUES (
'financial.documents.concepts.vat',  '37',  'Concept VAT',  'identifier2property', NULL, '0'
);

INSERT INTO rs_property_app_definitions (
RS_NAME ,
RS_ITEM_TYPE_ID ,
RS_DESCRIPTION ,
RS_TYPE ,
RS_DEFAULTVALUE ,
RS_REFERRED_ITEMTYPE
)
VALUES (
'financial.documents.concepts.base',  '37',  'Concept Base',  'identifier2property', NULL, '0'
);

INSERT INTO rs_property_app_definitions (
RS_NAME ,
RS_ITEM_TYPE_ID ,
RS_DESCRIPTION ,
RS_TYPE ,
RS_DEFAULTVALUE ,
RS_REFERRED_ITEMTYPE
)
VALUES (
'financial.documents.concepts.description',  '37',  'Concept Description',  'identifier2property', NULL, '0'
);

INSERT INTO rs_property_app_definitions (
RS_NAME ,
RS_ITEM_TYPE_ID ,
RS_DESCRIPTION ,
RS_TYPE ,
RS_DEFAULTVALUE ,
RS_REFERRED_ITEMTYPE
)
VALUES (
'financial.documents.concepts.project',  '37',  'Concept\'s associated project',  'identifier2property', NULL, '0'
);

INSERT INTO rs_property_app_definitions (
RS_NAME ,
RS_ITEM_TYPE_ID ,
RS_DESCRIPTION ,
RS_TYPE ,
RS_DEFAULTVALUE ,
RS_REFERRED_ITEMTYPE
)
VALUES (
'financial.documents.concepts.stock',  '37',  'Concept\'s associated stock',  'identifier2property', NULL, '0'
);

INSERT INTO rs_property_app_definitions (
RS_NAME ,
RS_ITEM_TYPE_ID ,
RS_DESCRIPTION ,
RS_TYPE ,
RS_DEFAULTVALUE ,
RS_REFERRED_ITEMTYPE
)
VALUES (
'financial.documents.concepts.units',  '37',  'Concept units',  'identifier2property', NULL, '0'
);

INSERT INTO rs_property_app_definitions (
RS_NAME ,
RS_ITEM_TYPE_ID ,
RS_DESCRIPTION ,
RS_TYPE ,
RS_DEFAULTVALUE ,
RS_REFERRED_ITEMTYPE
)
VALUES (
'financial.documents.concepts.order',  '37',  'Concept order',  'identifier2property', NULL, '0'
);