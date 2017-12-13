# New column to select avoid dupplication of properties
ALTER TABLE  `rs_item_properties` ADD  `RS_AVOID_DUPLICATION` TINYINT( 1 ) NOT NULL DEFAULT  '0';
UPDATE `rs_item_properties` SET `RS_AVOID_DUPLICATION`=1 WHERE `RS_TYPE`='file' OR `RS_TYPE`='image';

# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.1.7.3.128', '5.1.8.3.129', NOW(), 'No changes in the database');