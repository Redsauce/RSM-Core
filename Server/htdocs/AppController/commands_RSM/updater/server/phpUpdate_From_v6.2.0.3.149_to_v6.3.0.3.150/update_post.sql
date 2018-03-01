# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.2.0.3.149', '6.3.0.3.150', NOW(), 'Support for financial documents booking dates. Allow to work with designated financial documents already booked.');

UPDATE rs_property_app_definitions SET RS_NAME = 'financial.documents.bookDate', RS_DESCRIPTION = 'Indicates the date in which the document was booked' WHERE rs_property_app_definitions.RS_ID = 155;

INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES ('198', 'financial.documents.ignoreBooked', '37', 'Allow to work with the financial document independently of the booked state', NULL, 'text', '0'); 