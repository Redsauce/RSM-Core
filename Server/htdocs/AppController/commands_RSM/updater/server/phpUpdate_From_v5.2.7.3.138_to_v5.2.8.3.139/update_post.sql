# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.2.7.3.138', '5.2.8.3.139', NOW(), 'Added new permissions for API Sanity Tests');

# Insert new permissions for API Sanity Tests
INSERT INTO `rs_token_permissions` (`RS_CLIENT_ID`, `RS_TOKEN_ID`, `RS_PROPERTY_ID`, `RS_PERMISSION`)
 VALUES ('19', '4', '220', 'READ'), ('19', '4', '825', 'READ'), ('19', '4', '445', 'READ'), ('19', '4', '631', 'READ');