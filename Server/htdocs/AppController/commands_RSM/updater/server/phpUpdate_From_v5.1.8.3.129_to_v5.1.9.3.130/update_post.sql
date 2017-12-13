# Delete extranet module
DROP TABLE `rs_extranet_modules`;
DROP TABLE `rs_extranet_modules_clients`;
DROP TABLE `rs_extranet_modules_users`;

# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.1.8.3.129', '5.1.9.3.130', NOW(), 'Deleted the extranet module');