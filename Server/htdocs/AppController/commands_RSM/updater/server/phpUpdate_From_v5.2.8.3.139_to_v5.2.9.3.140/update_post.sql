# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.2.8.3.139', '5.2.9.3.140', NOW(), 'Configure opening methods for HTML modules and support for event names');

# Insert the application property with opening method for module
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
'455',  'event.name',  '10',  'Name of the event',  '',  'text',  '0'
);

# Insert the application property with opening method for module
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
'456',  'configuration.module.HTMLModule.opening',  '65',  'Default opening method for the module',  '',  'text',  '0'
);

# Insert the application property with opening method for module
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
'457',  'reports.script',  '14',  'Main script for the report',  '',  'longtext',  '0'
);