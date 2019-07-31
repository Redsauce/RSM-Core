# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.3.4.2.154', '6.4.0.3.155', NOW(), 'Property event.logType and related list created in order to improve log scripts');

# Add new property in order to improve log scripts
REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES ('483', 'event.logType', '10', 'Log detail to save', NULL, 'text', '0');

# Add new list in order to improve log scripts
REPLACE INTO rs_lists_app (RS_ID, RS_NAME) VALUES ('15', 'log.types');

REPLACE INTO rs_lists_values_app (RS_ID, RS_VALUE, RS_LIST_APP_ID) VALUES ('44', 'log.type.none', '15');
REPLACE INTO rs_lists_values_app (RS_ID, RS_VALUE, RS_LIST_APP_ID) VALUES ('46', 'log.type.complete', '15');

# RS_ORDER field is 0 by default
ALTER TABLE `rs_property_identifiers` CHANGE `RS_ORDER` `RS_ORDER` INT(11) NOT NULL DEFAULT 0;

# Clean rs_error_log table and make RS_ID autoincremental and primary
TRUNCATE rs_error_log;
ALTER TABLE `rs_error_log` ADD PRIMARY KEY(`RS_ID`);
ALTER TABLE `rs_error_log` CHANGE `RS_ID` `RS_ID` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT;