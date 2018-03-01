# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.2.0.3.149', '6.3.0.3.150', NOW(), 'Renamed finantial document ready property definition to booked date');

UPDATE rs_property_app_definitions SET RS_NAME = 'financial.documents.bookedDate', RS_DESCRIPTION = 'Indicates the date in which the document was booked' WHERE rs_property_app_definitions.RS_ID = 155; 