# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.2.6.3.137', '5.2.7.3.138', NOW(), 'Support for tickets in financial documents');

INSERT INTO rs_item_type_app_definitions (`RS_ID`, `RS_NAME`) VALUES ('16',  'ticket');

INSERT INTO  rs_property_app_definitions (
`RS_ID` ,
`RS_NAME` ,
`RS_ITEM_TYPE_ID` ,
`RS_DESCRIPTION` ,
`RS_DEFAULTVALUE` ,
`RS_TYPE` ,
`RS_REFERRED_ITEMTYPE`
)
VALUES (
'102',  'ticket.ID',  '16',  'Unique ID for the ticket', NULL ,  'integer',  '0'
);

INSERT INTO  rs_property_app_definitions (
`RS_ID` ,
`RS_NAME` ,
`RS_ITEM_TYPE_ID` ,
`RS_DESCRIPTION` ,
`RS_DEFAULTVALUE` ,
`RS_TYPE` ,
`RS_REFERRED_ITEMTYPE`
)
VALUES (
'110',  'ticket.date',  '16',  'Creation date for the ticket', NULL ,  'date',  '0'
);