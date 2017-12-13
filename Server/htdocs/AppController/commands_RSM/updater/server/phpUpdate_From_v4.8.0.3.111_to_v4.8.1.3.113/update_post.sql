# Insert the application version with changes in the PHP layer
INSERT INTO `rs_dbchanges` (`RS_ID`, `RS_PREVIOUS_VERSION`, `RS_NEW_VERSION`, `RS_EXECUTION_DATE`, `RS_COMMENTS`) 
	VALUES 
	(NULL, '4.8.0.3.111', '4.8.1.3.113', NOW(), 'Removed support for POST method in HTMLModule');

# Remove the obsolete app properties and any existing relationships	
DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'configuration.module.HTMLModule.Method');

DELETE FROM `rsm`.`rs_property_app_definitions`
WHERE `rs_property_app_definitions`.`RS_NAME` = 'configuration.module.HTMLModule.Method';

DELETE FROM `rs_property_app_relations` 
WHERE `RS_PROPERTY_APP_ID`=(SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = 'configuration.module.HTMLModule.RequestVars');

DELETE FROM `rsm`.`rs_property_app_definitions`
WHERE `rs_property_app_definitions`.`RS_NAME` = 'configuration.module.HTMLModule.RequestVars';