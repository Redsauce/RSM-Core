# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS) 
VALUES (NULL, '5.1.4.3.125', '5.1.5.3.126', NOW(), 'Removed unused finantial documents app properties');

# Budget app item type and relationships
DELETE FROM rs_item_type_app_definitions WHERE RS_NAME = 'budget';
DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID = 46;

# Budget app properties
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'budget.clientID';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'budget.invoiceID';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'budget.budgetDate';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'budget.sentDate';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'budget.description';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'budget.amount';

# Budget app properties relationships
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 366;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 363;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 361;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 365;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 362;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 364;

# Invoice coming from providers item type and relationships
DELETE FROM rs_item_type_app_definitions WHERE RS_NAME = 'invoice.provider';
DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID = 39;

# Invoice coming from provider app properties
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'invoice.provider.providerID';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'invoice.provider.invoiceID';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'invoice.provider.invoiceDate';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'invoice.provider.paymentDate';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'invoice.provider.debitDate';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'invoice.provider.description';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'invoice.provider.base';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'invoice.provider.vat';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'invoice.provider.retention';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'invoice.provider.total';

# Invoice coming from provider relationships
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 323;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 322;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 319;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 318;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 320;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 317;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 326;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 325;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 324;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 321;

# Albaran item type and relationships
DELETE FROM rs_item_type_app_definitions WHERE RS_NAME = 'receiptStatement';
DELETE FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID = 44;

# Albaran app properties
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'receipt.statement.sentDate';
DELETE FROM rs_property_app_definitions WHERE RS_NAME = 'receipt.statement.description';

# Albaran app properties relationships
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 355;
DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_APP_ID = 356;

# Support for N19 files generation. RSM must know where is the client's invoice data
INSERT INTO rs_property_app_definitions (RS_ID, RS_NAME, RS_ITEM_TYPE_ID, RS_TYPE)
VALUES (
'89',  'crmAccounts.invoicing.ccc',  '59', 'text'
), (
'90',  'crmAccounts.invoicing.name',  '59', 'text'
), (
'91',  'crmAccounts.invoicing.address',  '59', 'text'
), (
'92',  'crmAccounts.invoicing.postcode',  '59', 'text'
), (
'93',  'crmAccounts.invoicing.city',  '59', 'text'
);

# Support for base amounts in financial documents
INSERT INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_DEFAULTVALUE,RS_TYPE,RS_REFERRED_ITEMTYPE)
VALUES ('94',  'financial.documents.base',  '37',  'Document Base', NULL ,  'identifier2property',  '0');