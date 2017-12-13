# Insert the application version with changes in the PHP layer
INSERT INTO `rs_dbchanges` (`RS_ID`, `RS_PREVIOUS_VERSION`, `RS_NEW_VERSION`, `RS_EXECUTION_DATE`, `RS_COMMENTS`) 
VALUES (NULL, '5.0.2.3.118', '5.0.3.3.119', NOW(), 'No changes in the database');