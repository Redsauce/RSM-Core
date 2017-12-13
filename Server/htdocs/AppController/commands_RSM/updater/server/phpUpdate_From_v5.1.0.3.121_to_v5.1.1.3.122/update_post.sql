# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS) 
VALUES (NULL, '5.1.0.3.121', '5.1.1.3.122', NOW(), 'Fixed a app property name case');

# Resolve a problem with the reports module not listing the script editor contents
UPDATE rs_property_app_definitions
SET    RS_NAME =  'sections.scriptParams'
WHERE  rs_property_app_definitions.RS_NAME ='sections.ScriptParams';