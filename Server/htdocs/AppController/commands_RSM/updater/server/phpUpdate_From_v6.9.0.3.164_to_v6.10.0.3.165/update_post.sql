# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.9.0.3.164', '6.10.0.3.165', NOW(), 'No database changes');
