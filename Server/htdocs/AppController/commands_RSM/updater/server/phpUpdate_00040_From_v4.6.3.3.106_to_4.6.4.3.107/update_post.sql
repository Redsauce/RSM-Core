


DELETE FROM `rs_property_app_definitions` WHERE `RS_NAME`='stockItem.storeProductID';

DELETE FROM `rs_property_app_definitions` WHERE `RS_NAME`='onlineStoreProduct.price';


INSERT INTO `rs_dbchanges` (`RS_ID`, `RS_PREVIOUS_VERSION`, `RS_NEW_VERSION`, `RS_EXECUTION_DATE`, `RS_COMMENTS`) 
	VALUES 
	(NULL, '4.6.3.3.106', '4.6.4.3.107', NOW(), '');