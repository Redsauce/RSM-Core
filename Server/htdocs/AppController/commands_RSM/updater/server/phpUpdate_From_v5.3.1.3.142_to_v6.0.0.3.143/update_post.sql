# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.3.1.3.142', '6.0.0.3.143', NOW(), 'Added support for QR codes');

INSERT INTO  rs_lists_values_app (
`RS_ID` ,
`RS_VALUE` ,
`RS_LIST_APP_ID`
)
VALUES (
'43',  'trigger.type.qr',  '1'
);
