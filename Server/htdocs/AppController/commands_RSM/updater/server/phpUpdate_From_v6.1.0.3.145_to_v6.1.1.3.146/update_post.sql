# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.1.0.3.145', '6.1.1.3.146', NOW(), 'Fix for testing module not displaying steps');

#Fix testing module error
UPDATE `rs_item_type_app_definitions` SET `RS_NAME` = 'testcasescategory' WHERE `rs_item_type_app_definitions`.`RS_ID` = 19;
UPDATE `rs_property_app_definitions` SET `RS_NAME` = 'testcasescategory.name' WHERE `rs_property_app_definitions`.`RS_ID` = 163;
UPDATE `rs_property_app_definitions` SET `RS_NAME` = 'testcasescategory.parentID' WHERE `rs_property_app_definitions`.`RS_ID` = 164;
UPDATE `rs_property_app_definitions` SET `RS_NAME` = 'testcasescategory.groupID' WHERE `rs_property_app_definitions`.`RS_ID` = 165;
UPDATE `rs_property_app_definitions` SET `RS_NAME` = 'testcasescategory.order' WHERE `rs_property_app_definitions`.`RS_ID` = 166;
