# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS) 
VALUES (NULL, '5.1.3.3.124', '5.1.4.3.125', NOW(), 'Removed support for the extranet module');

# Fix relation of property with itemtype
UPDATE `rs_property_app_definitions` SET  `RS_REFERRED_ITEMTYPE` =  '59' WHERE  `RS_ID` =361;

# Remove the extranet action
DELETE FROM rs_actions WHERE RS_ID = 15;
DELETE FROM rs_actions_clients WHERE RS_ACTION_ID = 15;

# Remove module salaries
DELETE rs_actions_groups FROM rs_actions_groups
JOIN rs_actions_clients
ON (rs_actions_groups.rs_client_id = rs_actions_clients.rs_client_id AND rs_actions_groups.rs_action_client_id = rs_actions_clients.rs_id)
WHERE rs_actions_clients.rs_action_id = 13;

DELETE FROM rs_actions_clients WHERE rs_action_id = 13;

DELETE FROM rs_actions WHERE rs_name = 'rsm.mainpanel.salaries.access';