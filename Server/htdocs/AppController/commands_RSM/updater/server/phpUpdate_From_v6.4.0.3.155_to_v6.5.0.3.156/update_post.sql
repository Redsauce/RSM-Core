# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.4.0.3.155', '6.5.0.3.156', NOW(), 'No database changes');