# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.3.4.2.154', 'x.x.x.x.155', NOW(), 'Property event.logType and related list created in order to improve log scripts');

# Add new property in order to improve log scripts
INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES ('483', 'event.logType', '10', 'Log detail to save', NULL, 'text', '0');

# Add new list in order to improve log scripts
INSERT INTO rs_lists_app (RS_ID, RS_NAME) VALUES ('15', 'log.types');

INSERT INTO rs_lists_values_app (RS_ID, RS_VALUE, RS_LIST_APP_ID) VALUES ('44', 'log.type.none', '15');
INSERT INTO rs_lists_values_app (RS_ID, RS_VALUE, RS_LIST_APP_ID) VALUES ('45', 'log.type.basic', '15');
INSERT INTO rs_lists_values_app (RS_ID, RS_VALUE, RS_LIST_APP_ID) VALUES ('46', 'log.type.advanced', '15');