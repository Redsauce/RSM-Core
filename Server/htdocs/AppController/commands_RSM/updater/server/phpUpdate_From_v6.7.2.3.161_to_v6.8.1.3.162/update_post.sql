# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.7.2.3.161', '6.8.1.3.162', NOW(), 'New system property in order limitate script executions to specific servers.');

INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES ('484', 'event.serverAppID', '10', 'ID of a node to force the execution of the event with it.', NULL, 'identifier', '67') ON DUPLICATE KEY UPDATE    
RS_NAME='event.serverAppID', RS_ITEM_TYPE_ID='10', RS_DESCRIPTION='ID of a node to force the execution of the event with it.', RS_DEFAULTVALUE=NULL, RS_TYPE='identifier', RS_REFERRED_ITEMTYPE='67';