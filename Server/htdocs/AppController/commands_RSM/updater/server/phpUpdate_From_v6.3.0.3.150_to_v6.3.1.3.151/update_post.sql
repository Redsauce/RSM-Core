# Insert the application version with changes in the PHP layer
INSERT INTO rs_dbchanges (RS_ID, RS_PREVIOUS_VERSION, RS_NEW_VERSION, RS_EXECUTION_DATE, RS_COMMENTS)
VALUES (NULL, '6.3.0.3.150', '6.3.1.3.151', NOW(), 'Removed financial document concepts order property.');

# Remove order property from financial documents concepts
DELETE FROM rs_property_integers USING rs_property_integers INNER JOIN (rs_property_identifiers_to_properties INNER JOIN (rs_property_app_relations INNER JOIN rs_property_app_definitions ON rs_property_app_relations.RS_PROPERTY_APP_ID = rs_property_app_definitions.RS_ID) ON rs_property_identifiers_to_properties.RS_PROPERTY_ID = rs_property_app_relations.RS_PROPERTY_ID AND rs_property_identifiers_to_properties.RS_CLIENT_ID = rs_property_app_relations.RS_CLIENT_ID) ON rs_property_identifiers_to_properties.RS_DATA = rs_property_integers.RS_PROPERTY_ID AND rs_property_identifiers_to_properties.RS_CLIENT_ID = rs_property_integers.RS_CLIENT_ID WHERE rs_property_app_definitions.RS_NAME = 'financial.documents.concepts.order'

DELETE FROM rs_property_identifiers_to_properties USING rs_property_identifiers_to_properties INNER JOIN (rs_property_app_relations INNER JOIN rs_property_app_definitions ON rs_property_app_relations.RS_PROPERTY_APP_ID = rs_property_app_definitions.RS_ID) ON rs_property_identifiers_to_properties.RS_PROPERTY_ID = rs_property_app_relations.RS_PROPERTY_ID AND rs_property_identifiers_to_properties.RS_CLIENT_ID = rs_property_app_relations.RS_CLIENT_ID WHERE rs_property_app_definitions.RS_NAME = 'financial.documents.concepts.order'

DELETE FROM rs_property_app_relations USING rs_property_app_relations INNER JOIN rs_property_app_definitions ON rs_property_app_relations.RS_PROPERTY_APP_ID = rs_property_app_definitions.RS_ID WHERE rs_property_app_definitions.RS_NAME = 'financial.documents.concepts.order'

DELETE FROM rs_property_app_definitions WHERE rs_property_app_definitions.RS_NAME = 'financial.documents.concepts.order'

# Another concepts order property
DELETE FROM rs_property_integers USING rs_property_integers INNER JOIN (rs_property_identifiers_to_properties INNER JOIN (rs_property_app_relations INNER JOIN rs_property_app_definitions ON rs_property_app_relations.RS_PROPERTY_APP_ID = rs_property_app_definitions.RS_ID) ON rs_property_identifiers_to_properties.RS_PROPERTY_ID = rs_property_app_relations.RS_PROPERTY_ID AND rs_property_identifiers_to_properties.RS_CLIENT_ID = rs_property_app_relations.RS_CLIENT_ID) ON rs_property_identifiers_to_properties.RS_DATA = rs_property_integers.RS_PROPERTY_ID AND rs_property_identifiers_to_properties.RS_CLIENT_ID = rs_property_integers.RS_CLIENT_ID WHERE rs_property_app_definitions.RS_NAME = 'concepts.orden'

DELETE FROM rs_property_identifiers_to_properties USING rs_property_identifiers_to_properties INNER JOIN (rs_property_app_relations INNER JOIN rs_property_app_definitions ON rs_property_app_relations.RS_PROPERTY_APP_ID = rs_property_app_definitions.RS_ID) ON rs_property_identifiers_to_properties.RS_PROPERTY_ID = rs_property_app_relations.RS_PROPERTY_ID AND rs_property_identifiers_to_properties.RS_CLIENT_ID = rs_property_app_relations.RS_CLIENT_ID WHERE rs_property_app_definitions.RS_NAME = 'concepts.orden'

DELETE FROM rs_property_app_relations USING rs_property_app_relations INNER JOIN rs_property_app_definitions ON rs_property_app_relations.RS_PROPERTY_APP_ID = rs_property_app_definitions.RS_ID WHERE rs_property_app_definitions.RS_NAME = 'concepts.orden'

DELETE FROM rs_property_app_definitions WHERE rs_property_app_definitions.RS_NAME = 'concepts.orden'
