# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.8.1.3.163', '6.9.0.3.164', NOW(), 'Added a new column for user badges and two new system properties for generic modules.');

INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES ('485', 'configuration.module.genericModule.timeout', '53', 'Number of seconds before the timeout for displaying data', NULL, 'integer', '0') ON DUPLICATE KEY UPDATE RS_NAME='configuration.module.genericModule.timeout', RS_ITEM_TYPE_ID='53', RS_DESCRIPTION='Number of seconds before the timeout for displaying data', RS_DEFAULTVALUE=NULL, RS_TYPE='integer';

INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES ('486', 'configuration.module.genericModule.ignoredProperties', '53', 'IDs of properties ignored when constructing the tree', NULL, 'text', '0') ON DUPLICATE KEY UPDATE RS_NAME='configuration.module.genericModule.ignoredProperties', RS_ITEM_TYPE_ID='53', RS_DESCRIPTION='IDs of properties ignored when constructing the tree', RS_DEFAULTVALUE=NULL, RS_TYPE='text';

ALTER TABLE rs_users
ADD COLUMN RS_BADGE VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci;
