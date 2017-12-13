# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.2.9.3.140', '5.2.10.3.141', NOW(), 'Removed support for expenses module');

# Remove support for the expenses module
DELETE FROM rs_actions WHERE RS_ID = 7;
DELETE FROM rs_actions_clients WHERE RS_ACTION_ID = 7;

# Rename an app list for compatibility with the events handler
UPDATE rs_lists_values_app
SET RS_VALUE =  'trigger.type.update.item'
WHERE  RS_ID = 7;