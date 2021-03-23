<?php
//***************************************************
// RSM queries file
//***************************************************
// Crear clase queries

class RSQueries {
    public array $queries = [
        'item_property' => [
            'getType'     => [
                'query' => 'SELECT RS_TYPE FROM rs_item_properties WHERE RS_PROPERTY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ],
            'getCategory' => [
                'query' => 'SELECT RS_CATEGORY_ID FROM rs_item_properties WHERE RS_PROPERTY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ],
            'getDefaultValue' => [
                'query' => 'SELECT RS_DEFAULTVALUE FROM rs_item_properties WHERE RS_PROPERTY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ],
            'getName' => [
                'query' => 'SELECT RS_NAME FROM rs_item_properties WHERE RS_PROPERTY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ],
            'getReferredItemtype' => [
                'query' => 'SELECT RS_REFERRED_ITEMTYPE FROM rs_item_properties WHERE RS_PROPERTY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ],
            'deleteClientProperty' => [
                'query' => 'DELETE FROM rs_item_properties WHERE RS_PROPERTY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ]
        ],
        'client_category' => [
            'getItemType' => [
                'query' => 'SELECT RS_ITEMTYPE_ID FROM rs_categories WHERE RS_CATEGORY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ],
            'getName' => [
                'query' => 'SELECT RS_NAME FROM rs_categories WHERE RS_CATEGORY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ],
            'getItemProperties' => [
                'query' => 'SELECT RS_PROPERTY_ID, RS_NAME, RS_TYPE, RS_ORDER FROM rs_item_properties WHERE RS_CATEGORY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ]
        ],
        'property_app_relations' => [
            'getPropertyApp' => [
                'query' => 'SELECT RS_PROPERTY_APP_ID FROM rs_property_app_relations WHERE RS_PROPERTY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ],
            'deletePropertyApp' => [
                'query' => 'DELETE FROM rs_property_app_relations WHERE RS_PROPERTY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ]
        ],
        'property_app_definitions' => [
            'getDefaultValue' => [
                'query' => 'SELECT RS_DEFAULTVALUE FROM rs_property_app_definitions WHERE RS_ID = ?',
                'types' => 'i'
            ],
            'getReferredItemtype' => [
                'query' => 'SELECT RS_REFERRED_ITEMTYPE FROM rs_property_app_definitions WHERE RS_ID = ?',
                'types' => 'i'
            ],
            'getIdByName' => [
                'query' => 'SELECT RS_ID FROM rs_property_app_definitions WHERE RS_NAME = ?',
                'types' => 's'
            ]
        ],
        'properties_tables' => [
            'deleteClientProperty' => [
                'query' => 'DELETE FROM ? WHERE RS_PROPERTY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'sii'
            ]
        ],
        'properties_groups' => [
            'deleteClientPropertyGroup' => [
                'query' => 'DELETE FROM rs_properties_groups WHERE RS_PROPERTY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ],
        ],
        'properties_lists' => [
            'deleteClientPropertyList' => [
                'query' => 'DELETE FROM rs_properties_lists WHERE RS_PROPERTY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ],
        ],
        'token_permissions' => [
            'deleteClientPropertyPermission' => [
                'query' => 'DELETE FROM rs_token_permissions WHERE RS_PROPERTY_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ],
        ],
        'item_types' => [
            'updateMainProperty' => [
                'query' => 'UPDATE rs_item_types SET RS_MAIN_PROPERTY_ID = ? WHERE RS_ITEMTYPE_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'iii'
            ],
        ],
        'item_type_app_relations' => [
            'getClientPropertyItemtype' => [
                'query' => 'SELECT rs_item_type_app_relations.RS_ITEMTYPE_ID AS "itemTypeID" 
                            FROM rs_property_app_relations 
                            INNER JOIN rs_property_app_definitions ON (rs_property_app_relations.RS_PROPERTY_APP_ID = rs_property_app_definitions.RS_ID) 
                            INNER JOIN rs_item_type_app_relations ON (rs_property_app_definitions.RS_REFERRED_ITEMTYPE = rs_item_type_app_relations.RS_ITEMTYPE_APP_ID) 
                            WHERE (rs_property_app_relations.RS_PROPERTY_ID = ? AND rs_property_app_relations.RS_CLIENT_ID = ?) 
                            AND (rs_item_type_app_relations.RS_CLIENT_ID = ?)',
                'types' => 'iii'
            ],
            'getClientAppItemtype' => [
                'query' => 'SELECT RS_ITEMTYPE_ID FROM rs_item_type_app_relations WHERE RS_ITEMTYPE_APP_ID = ? AND RS_CLIENT_ID = ?',
                'types' => 'ii'
            ]
        ],
    ];
}