# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.9.0.3.164', '7.0.0.3.165', NOW(), 'Module scripts Editor has been removed');

# Remove the reference to the scripts editor application
DELETE FROM rsm1.rs_actions WHERE RS_ID=8;

# Also remove the action from all clients
DELETE FROM rsm1.rs_actions_clients WHERE RS_ACTION_ID=8;

# Remove the references for removed actions from the rs_actions_group table
DELETE FROM rs_actions_groups
WHERE NOT EXISTS (
    SELECT 1
    FROM rs_actions_clients
    WHERE rs_actions_groups.RS_CLIENT_ID = rs_actions_clients.RS_CLIENT_ID
    AND rs_actions_groups.RS_ACTION_CLIENT_ID = rs_actions_clients.RS_ID
);