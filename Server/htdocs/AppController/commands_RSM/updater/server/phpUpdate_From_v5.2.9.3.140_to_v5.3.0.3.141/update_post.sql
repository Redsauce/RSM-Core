# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '5.2.9.3.140', '5.3.0.3.141', NOW(), 'Removed support for expenses module');

# Remove support for the expenses module
DELETE FROM rs_actions WHERE RS_ID = 7;
DELETE FROM rs_actions_clients WHERE RS_ACTION_ID = 7;

# Rename an app list for compatibility with the events handler
UPDATE rs_lists_values_app
SET RS_VALUE =  'trigger.type.update.item'
WHERE  RS_ID = 7;

# Create app list to use invoice adresses in invoiceClients
INSERT INTO  `rs_lists_app` (
`RS_ID` ,
`RS_NAME`
)
VALUES (
'15',  'invoiceClient.adresses'
);

# Create value inside the new app list
INSERT INTO  `rs_lists_values_app` (
`RS_ID` ,
`RS_VALUE` ,
`RS_LIST_APP_ID`
)
VALUES (
'43',  'invoiceClient.adresses.billing',  '15'
), (
'44',  'invoiceClient.adresses.shipping',  '15'
);


# Create new system property to the client.adress
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
'458',  'invoice.client.billingAdress',  '38',  'Reference to the billing adress for this client', NULL ,  'identifier',  '0'
), (
'459',  'invoice.client.shippingAdress',  '38',  'Reference to the sending adress for this client', NULL ,  'identifier',  '0'
);