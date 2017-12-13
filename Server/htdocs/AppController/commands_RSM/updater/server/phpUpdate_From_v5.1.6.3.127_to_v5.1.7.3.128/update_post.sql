# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS) 
VALUES (NULL, '5.1.6.3.127', '5.1.7.3.128', NOW(), 'Remove support for operations module, add support for unique docIDs');

# Clear entry for the operations module from the DB
DELETE FROM rs_actions WHERE RS_NAME = 'rsm.mainpanel.operations.access';

# DB support for unique identifiers in financial documents
REPLACE INTO rs_property_app_definitions (
RS_ID ,
RS_NAME ,
RS_ITEM_TYPE_ID ,
RS_DESCRIPTION ,
RS_DEFAULTVALUE ,
RS_TYPE ,
RS_REFERRED_ITEMTYPE
)
VALUES (
'441',  'financial.documents.uniqueID',  '37',  'Unique ID for the document', NULL ,  'identifier2property',  '0'
);