# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.0.0.3.143', '6.0.1.3.144', NOW(), 'Keep the last ID created for a given item type. Support for events scheduling.');

# Remove unused column in the rs_item_types table
ALTER TABLE rs_item_types DROP RS_LOCKED;

# Add a column to conserve the ID of the latest created item of its kind
ALTER TABLE  rs_item_types ADD  RS_LAST_ITEM_ID INT( 11 ) NOT NULL DEFAULT  '0';

# Correct the description in the clients' table
ALTER TABLE rs_clients CHANGE RS_NAME RS_NAME VARCHAR( 255 ) CHARACTER SET utf8 NOT NULL COMMENT  'RSM client name';

# Relationships with scheduled events
REPLACE INTO  rs_item_type_app_definitions (RS_ID, RS_NAME) VALUES (40, 'scheduledEvents');

# And the properties to relate
REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (92,  'scheduledEvents.event', 40,  'ID of the Event to execute', 'identifier',  10);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (93,  'scheduledEvents.creationDate', 40,  'Datetime in which the event was enqueued', 'datetime',  0);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (111,  'scheduledEvents.parameters', 40,  'Parameters for the script', 'longtext',  0);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (112,  'scheduledEvents.executionStart', 40,  'Datetime in which the execution was started', 'datetime',  0);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (113,  'scheduledEvents.executionEnd', 40,  'Datetime in which the execution finished', 'datetime',  0);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (114,  'scheduledEvents.exitCode', 40,  'Integer code indicating the exit result', 'integer',  0);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (115,  'scheduledEvents.executionLog', 40,  'Output of the script', 'longtext',  0);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (116,  'scheduledEvents.script', 40,  'Complete script to execute', 'longtext',  0);

REPLACE INTO  rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_TYPE, RS_REFERRED_ITEMTYPE)
VALUES (91,  'configuration.module.HTMLmodule.JS', 65,  'JavaScript code snippet to execute', 'longtext',  0);
