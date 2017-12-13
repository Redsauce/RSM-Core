# Insert the application version with changes in the PHP layer
INSERT INTO `rs_dbchanges` (`RS_ID`, `RS_PREVIOUS_VERSION`, `RS_NEW_VERSION`, `RS_EXECUTION_DATE`, `RS_COMMENTS`) 
VALUES (NULL, '5.0.0.3.115', '5.0.1.3.116', NOW(), 'Support for news feeds');

# New app item types for news
INSERT INTO rs_item_type_app_definitions (RS_ID, RS_NAME) VALUES (48, 'news');

# News properties
INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (58, 'news.title'      , 48,  'Title for the news'      ,  'New item',  'text'       , 0 );
INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (59, 'news.description', 48,  'Description for the news',  '',          'longtext'   , 0 );
INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (60, 'news.type'       , 48,  'News type'               ,  '',          'identifiers', 61);
INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (61, 'news.author'     , 48,  'Author of the news'      ,  '',          'text'       , 0 );
INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (62, 'news.date'       , 48,  'Date for the news'       ,  '',          'date'       , 0 );
INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (63, 'news.image'      , 48,  'Image for the news'      ,  '',          'image'      , 0 );

# New app item type for news types
INSERT INTO rs_item_type_app_definitions (RS_ID, RS_NAME) VALUES (61, 'newsType');

# News types properties
INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_DESCRIPTION, RS_DEFAULTVALUE, RS_TYPE, RS_REFERRED_ITEMTYPE) VALUES (64, 'newstype.title'  , 61,  'Title for the news type' ,  'New item',  'text'       , 0);
