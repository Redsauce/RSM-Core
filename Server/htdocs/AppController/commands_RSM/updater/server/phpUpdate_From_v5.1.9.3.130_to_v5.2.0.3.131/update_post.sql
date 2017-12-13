# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.1.9.3.130', '5.2.0.3.131', NOW(), 'Redesigned RSM core + new events system');

# Support for description, URL and language for RSS news
INSERT INTO  `rs_property_app_definitions` (
`RS_ID` ,
`RS_NAME` ,
`RS_ITEM_TYPE_ID` ,
`RS_DESCRIPTION` ,
`RS_DEFAULTVALUE` ,
`RS_TYPE` ,
`RS_REFERRED_ITEMTYPE`
)
VALUES (
'105',  'newsType.description',  '61',  'Description for the news type',  '',  'longtext',  '0'
);

INSERT INTO  `rs_property_app_definitions` (
`RS_ID` ,
`RS_NAME` ,
`RS_ITEM_TYPE_ID` ,
`RS_DESCRIPTION` ,
`RS_DEFAULTVALUE` ,
`RS_TYPE` ,
`RS_REFERRED_ITEMTYPE`
)
VALUES (
'106',  'newsType.URL',  '61',  'URL where the RSS comes from',  '',  'text',  '0'
);

INSERT INTO  `rs_property_app_definitions` (
`RS_ID` ,
`RS_NAME` ,
`RS_ITEM_TYPE_ID` ,
`RS_DESCRIPTION` ,
`RS_DEFAULTVALUE` ,
`RS_TYPE` ,
`RS_REFERRED_ITEMTYPE`
)
VALUES (
'107',  'newsType.language',  '61',  'Language code for the RSS content',  'es-ES',  'text',  '0'
);

# Repaired typo error in the news type title
UPDATE  `rs_property_app_definitions` SET  `RS_NAME` =  'newsType.title' WHERE  `rs_property_app_definitions`.`RS_ID` =64;

# Support for base amounts in financial documents
INSERT INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_DEFAULTVALUE,RS_TYPE,RS_REFERRED_ITEMTYPE)
VALUES ('95',  'event.token',  '10',  'Authorization token for the script', NULL ,  'text',  '0');

# New RSM Scripts Editor application
INSERT INTO rs_versions (RS_ID, RS_NAME, RS_BUILD, RS_OS, RS_SIGNATURE, RS_PUBLIC, RS_URL) VALUES (NULL, 'RSM Scripts Editor', '1.0.0.3.1', 'linux', NULL, '1', NULL);

# Support for URL in news
INSERT INTO  `rs_property_app_definitions` (
`RS_ID` ,
`RS_NAME` ,
`RS_ITEM_TYPE_ID` ,
`RS_DESCRIPTION` ,
`RS_DEFAULTVALUE` ,
`RS_TYPE` ,
`RS_REFERRED_ITEMTYPE`
)
VALUES (
'108',  'news.URL',  '48',  'URL for the news',  '',  'text',  '0'
);