# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.2.5.3.136', '5.2.6.3.137', NOW(), 'Removed the projects module');

# Remove the projects module entry
DELETE FROM rs_actions WHERE RS_ID = 4;

# Remove all the references to the projects module
DELETE FROM rs_actions_clients WHERE RS_ACTION_ID = 4;

# Force the empty multiidentifiers to zero.
UPDATE rs_property_multiIdentifiers SET RS_DATA = '0' WHERE RS_DATA = '';

# Add support for BIC in staff
INSERT INTO  rs_property_app_definitions (
RS_ID,
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_DEFAULTVALUE,
RS_TYPE,
RS_REFERRED_ITEMTYPE
)
VALUES (
'109',  'staff.bic',  '3',  'Bank Information Code', NULL ,  'text',  '0'
);