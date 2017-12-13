# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS) 
VALUES (NULL, '5.0.4.3.120', '5.1.0.3.121', NOW(), 'No changes in the database');

# Remove invalid properties for receipts (albaranes)
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'receipt.statement.clientID';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'receipt.statement.invoiceID';

DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 353;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 354;