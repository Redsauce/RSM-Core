# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS) 
VALUES (NULL, '5.0.3.3.119', '5.0.4.3.120', NOW(), 'Support for includes in the events module, payroll creation using the financial documents module, preview URL support for financial documents and support for relations between operations and financial documents');

INSERT INTO rs_property_app_definitions (
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_TYPE
)
VALUES
(
'payroll.date', 42, 'Date in which the payroll was generated', 'date'
);

INSERT INTO rs_property_app_definitions (
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_TYPE,
RS_REFERRED_ITEMTYPE
)
VALUES
(
'staff.payrollSubAccountID', 3, 'ID of the subaccount in which the payrolls must be created', 'identifier', 4
);

INSERT INTO rs_property_app_definitions (
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_TYPE
)
VALUES
(
'staff.taxCode', 3, 'Unique fiscal identification', 'text'
);

INSERT INTO rs_property_app_definitions (
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_TYPE
)
VALUES
(
'staff.name', 3, 'Name of the person', 'text'
);

INSERT INTO rs_property_app_definitions (
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_TYPE
)
VALUES
(
'staff.bankAccount', 3, 'Bank account of the person', 'text'
);

INSERT INTO rs_property_app_definitions (
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_TYPE
)
VALUES
(
'staff.address', 3, 'Address of the person', 'text'
);

INSERT INTO rs_property_app_definitions (
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_TYPE
)
VALUES
(
'staff.postCode', 3, 'Post code for the address', 'text'
);

INSERT INTO rs_property_app_definitions (
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_TYPE
)
VALUES
(
'staff.city', 3, 'City of residence of the person', 'text'
);

# Remove the unused client app item type
DELETE FROM rs_item_type_app_definitions
WHERE RS_ID = 27;

# And its relationships
DELETE FROM rs_item_type_app_relations
WHERE RS_ITEMTYPE_APP_ID = 27;

# And properties
DELETE FROM rs_property_app_definitions
WHERE RS_ITEM_TYPE_ID = 27;

# Remove the unused provider app item type
DELETE FROM rs_item_type_app_definitions
WHERE RS_ID = 28;

# And its relationships
DELETE FROM rs_item_type_app_relations
WHERE RS_ITEMTYPE_APP_ID = 28;

# And properties
DELETE FROM rs_property_app_definitions
WHERE RS_ITEM_TYPE_ID = 28;

# Link the client invoices with the accounts instead of the clients, previously deleted
UPDATE  rs_property_app_definitions
SET  RS_REFERRED_ITEMTYPE = 59
WHERE RS_NAME = 'invoice.client.clientID';

# Link the provider invoices with the accounts instead of the providers, previously deleted
UPDATE  rs_property_app_definitions
SET  RS_REFERRED_ITEMTYPE = 59
WHERE RS_NAME = 'invoice.provider.clientID';

INSERT INTO  rs_property_app_definitions (
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_TYPE
)
VALUES
(
'financial.documents.relatedOperationIDs',  37,  'Used to relate the financial document with one or more bank operations',  'identifier2property'
);

INSERT INTO  rs_property_app_definitions (
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_TYPE
)
VALUES
(
'financial.documents.previewURL',  37,  'URL used for previewing the document type',  'text'
);

/* Change the type of the app property that stores the date in which an invoice has been paid */
UPDATE  rs_property_app_definitions
SET  RS_TYPE = 'date' WHERE RS_ID = 309;

/* And delete the current relationships to avoid relationships to invalid properties */
DELETE FROM rs_property_app_relations
WHERE RS_PROPERTY_APP_ID = 309;

INSERT INTO rs_property_app_definitions (
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_TYPE,
RS_REFERRED_ITEMTYPE
)
VALUES
(
'eventInclude.eventIDs', 27, 'Stores the IDs of the events containing the code required by this include', 'identifiers', 10
);

INSERT INTO  rs_item_type_app_definitions (
RS_ID,
RS_NAME
)
VALUES
(
27,  'eventInclude'
);

INSERT INTO rs_property_app_definitions (
RS_NAME,
RS_ITEM_TYPE_ID,
RS_DESCRIPTION,
RS_TYPE
)
VALUES
(
'eventInclude.actions', 27, 'Stores the code pertaining to this include', 'longtext'
);

# Delete old payroll linkage
DELETE FROM rs_property_app_definitions
WHERE RS_NAME = 'staff.payrollSubAccountID';

# Delete other not needed linkages
DELETE FROM rs_property_app_definitions
WHERE RS_NAME = 'invoice.client.relatedBankOperations';

# Delete other not needed linkages
DELETE FROM rs_property_app_definitions
WHERE RS_NAME = 'invoice.provider.relatedBankOperations';

/* And delete the current relationships to avoid relationshipd to invalid properties */
DELETE FROM rs_property_app_relations
WHERE RS_PROPERTY_APP_ID = 316;

DELETE FROM rs_property_app_relations
WHERE RS_PROPERTY_APP_ID = 327;