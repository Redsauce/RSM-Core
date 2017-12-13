# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.2.2.3.133', '5.2.3.3.134', NOW(), 'Removed support for the extranet module');

DELETE FROM rs_item_type_app_definitions WHERE RS_NAME = 'users';
DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID = 16;

DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'users.subAccountID';
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 155;

DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'users.login';
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 156;

DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'users.password';
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 157;