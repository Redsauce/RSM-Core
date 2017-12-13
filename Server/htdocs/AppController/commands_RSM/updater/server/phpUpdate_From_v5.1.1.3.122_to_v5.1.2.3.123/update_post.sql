# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS) 
VALUES (NULL, '5.1.1.3.122', '5.1.2.3.123', NOW(), 'Support for bank accounts');

# Support for bank accounts (Item type)
INSERT INTO  rs_item_type_app_definitions (
RS_ID,
RS_NAME
)
VALUES (
'66',  'bankAccount'
);

# Support for bank accounts (Properties)
INSERT INTO  rs_property_app_definitions (
RS_ID,
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_DEFAULTVALUE,
RS_TYPE,
RS_REFERRED_ITEMTYPE
)
VALUES (
'65',  'bankAccount.IBAN.Country',  '66',  'Country for the IBAN', NULL ,  'text',  '0'
), (
'66',  'bankAccount.IBAN.CheckDigit',  '66',  'Check digit for the IBAN', NULL ,  'text',  '0'
), (
'67',  'bankAccount.IBAN.Bank',  '66',  'Bank for the account', NULL ,  'text',  '0'
), (
'82',  'bankAccount.IBAN.Office',  '66',  'Office for the account', NULL ,  'text',  '0'
), (
'83',  'bankAccount.IBAN.ControlDigit',  '66',  'Control digit for the account', NULL ,  'text',  '0'
), (
'84',  'bankAccount.IBAN.Account',  '66',  'Account number', NULL ,  'text',  '0'
), (
'85',  'bankAccount.IBAN.SWIFT',  '66',  'Bank swift code', NULL ,  'text',  '0'
);

# Support for date reordering in financial documents
INSERT INTO  rs_property_app_definitions (
RS_ID,
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_DEFAULTVALUE,
RS_TYPE,
RS_REFERRED_ITEMTYPE
)
VALUES (
'86',  'financial.documents.date',  '37',  'Date property for document filtering by date', NULL ,  'identifier2property',  '0'
);

# Support for total calculation and description display in financial documents
INSERT INTO  rs_property_app_definitions (
RS_ID,
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_DEFAULTVALUE,
RS_TYPE,
RS_REFERRED_ITEMTYPE
)
VALUES (
'87',  'financial.documents.total',  '37',  'Date property for total calculation', NULL ,  'identifier2property',  '0'
);

INSERT INTO  rs_property_app_definitions (
RS_ID,
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_DEFAULTVALUE,
RS_TYPE,
RS_REFERRED_ITEMTYPE
)
VALUES (
'88',  'financial.documents.description',  '37',  'Date property for total calculation', NULL ,  'identifier2property',  '0'
);

# New status for staff
INSERT INTO  rs_lists_values_app (
RS_ID ,
RS_VALUE ,
RS_LIST_APP_ID
)
VALUES (
'42',  'staff.status.inactive',  '5'
);