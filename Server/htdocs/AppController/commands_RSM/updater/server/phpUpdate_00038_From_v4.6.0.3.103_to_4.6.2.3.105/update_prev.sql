INSERT INTO  `rs_item_type_app_definitions` 
(`RS_ID`, `RS_NAME`)
VALUES 
(NULL, 'onlineStore'),
(NULL, 'onlineStoreCategory'),
(NULL, 'onlineStoreProduct');


INSERT INTO `rs_property_app_definitions` 
(`RS_ID` ,`RS_NAME` ,`RS_ITEM_TYPE_ID` ,`RS_DESCRIPTION` ,`RS_DEFAULTVALUE` ,`RS_TYPE` ,`RS_REFERRED_ITEMTYPE`)
VALUES 
(NULL, 'onlineStore.URL', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'onlineStore'), '', NULL , 'text', '0'), 
(NULL, 'onlineStoreCategory.storeID', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'onlineStoreCategory'), '', NULL , 'identifier', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'onlineStore')),
(NULL, 'onlineStoreProduct.categoryID', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'onlineStoreProduct'), '', NULL , 'identifier', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'onlineStoreCategory')),  
(NULL, 'onlineStoreProduct.price', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'onlineStoreProduct'), '', NULL , 'float', '0');


INSERT INTO `rs_property_app_definitions` 
(`RS_ID` ,`RS_NAME` ,`RS_ITEM_TYPE_ID` ,`RS_DESCRIPTION` ,`RS_DEFAULTVALUE` ,`RS_TYPE` ,`RS_REFERRED_ITEMTYPE`)
VALUES 
(NULL, 'stockItem.storeProductID', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'stockItem'), '', NULL , 'identifier', (SELECT RS_ID FROM rs_item_type_app_definitions WHERE RS_NAME = 'onlineStoreProduct'));