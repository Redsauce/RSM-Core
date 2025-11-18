# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.9.0.3.164', '7.0.0.3.165', NOW(), 'Module scripts Editor has been removed');

# Remove the reference to the scripts editor application
DELETE FROM rs_actions WHERE RS_ID=8;

# Also remove the action from all clients
DELETE FROM rs_actions_clients WHERE RS_ACTION_ID=8;

# Remove the references for removed actions from the rs_actions_group table
DELETE FROM rs_actions_groups
WHERE NOT EXISTS (
    SELECT 1
    FROM rs_actions_clients
    WHERE rs_actions_groups.RS_CLIENT_ID = rs_actions_clients.RS_CLIENT_ID
    AND rs_actions_groups.RS_ACTION_CLIENT_ID = rs_actions_clients.RS_ID
);

REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (489,'event.language',10,'Script''s programming language','text');

REPLACE INTO rs_property_app_definitions (RS_ID, RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (490,'eventInclude.language',27,'Include''s programming language','text');

REPLACE INTO rs_lists_app (RS_ID,RS_NAME)
	VALUES (16,'event.language');

REPLACE INTO rs_lists_values_app (RS_ID,RS_VALUE,RS_LIST_APP_ID)
	VALUES (47,'event.language.xojoscript',16);

REPLACE INTO rs_lists_values_app (RS_ID,RS_VALUE,RS_LIST_APP_ID)
	VALUES (48,'event.language.python',16);

# Create properties for invoice client wich will be used by Veri*factu
REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (491,'invoice.client.identifier',38,'Text used to locate the invoice in the relationships window.','text');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (492,'invoice.client.series',38,'Indicates the type of invoice series, such as standard invoice, corrective invoice, etc.','text');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE,RS_REFERRED_ITEMTYPE)
	VALUES (493,'invoice.client.relatedInvoice',38,'References the original invoice that is being corrected by the current one.','identifier',38);


# Create the item type and properties for invoice client concepts
REPLACE INTO rs_item_type_app_definitions (RS_ID,RS_NAME)
	VALUES (70,'invoice.client.concept');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (494,'invoice.client.concept.base',70,'Base price of the item on the issued invoice','float');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (495,'invoice.client.concept.vat',70,'VAT percentage applied to the item on the issued invoice','float');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (496,'invoice.client.concept.retention',70,'Withholding percentage applied to the item on the issued invoice','float');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (497,'invoice.client.concept.quantity',70,'Product quantity used to calculate the total price of the item','float');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (498,'invoice.client.concept.showPrice',70,'Indicate whether the item should be included in the invoice calculation.','text');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (499,'invoice.client.concept.invoiceID',70,'Indicate the invoice to which the item belongs.','identifier');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (500,'invoice.client.concept.discount',70,'Specify the discount rate to be applied to the invoice line.','float');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (501,'invoice.client.verifactuSentDate',70,'Stores the date and time when the record was sent to Verifactu.','datetime');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (502,'invoice.client.verifactuSent',70,'Stores the XML sent to Verifactu.','longtext');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (503,'invoice.client.verifactuResponse',70,'Stores the response received from Verifactu.','longtext');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (504,'invoice.client.verifactuOwnFingerprint',70,'Stores the fingerprint of the invoice sent to Verifactu.','text');

REPLACE INTO rs_property_app_definitions (RS_ID,RS_NAME,RS_ITEM_TYPE_ID,RS_DESCRIPTION,RS_TYPE)
	VALUES (505,'invoice.client.verifactuPreviousFingerprint',70,'Stores the fingerprint of the previous invoice.','text');

# Create a table to track client data
CREATE TABLE IF NOT EXISTS rs_client_stats (
    RS_CLIENT_ID INT NOT NULL,
    STAT_DATE DATE NOT NULL,
    DB_DATA_BYTES BIGINT UNSIGNED NOT NULL DEFAULT 0, 
    DB_FILES_BYTES BIGINT UNSIGNED NOT NULL DEFAULT 0,
    DB_IMAGES_BYTES BIGINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (RS_CLIENT_ID, STAT_DATE)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;