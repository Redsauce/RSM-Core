<?php

// Shared wire text and query fragments. Values preserve the legacy SQL and responses.
final class RSMsql
{
    public const QUOTED_AND_CLIENT_ID = '\' AND RS_CLIENT_ID = \'';
    public const AND_FILTER_ID = ' AND `RS_FILTER_ID`=';
    public const AND_CLIENT_ID = ' AND RS_CLIENT_ID = ';
    public const AND_CLIENT_ID_COMPACT = ' AND RS_CLIENT_ID=';
    public const AND_ITEM_TYPE_ID = ' AND RS_ITEMTYPE_ID = ';
    public const AND_PROPERTY_ID = ' AND RS_PROPERTY_ID = ';
    public const AND_GROUP = ' AND (';
    public const CATEGORIES_CLIENT = ' AND rs_categories.RS_CLIENT_ID = ';
    public const ITEM_PROPERTIES_CLIENT_GROUP = ') AND (rs_item_properties.RS_CLIENT_ID = ';
    public const PROPERTY_GROUPS_CLIENT_GROUP = ') AND (rs_properties_groups.RS_CLIENT_ID = ';
    public const USER_GROUPS_USER_GROUP = ') AND (rs_users_groups.RS_USER_ID = ';
    public const USER_GROUPS_CLIENT = ' AND rs_users_groups.RS_CLIENT_ID = ';
    public const REPLACE_INTO = 'REPLACE INTO  ';
    public const CAST_ITEM_DATA = ', RS_ITEM_ID, CAST(RS_DATA AS ';
    public const WHERE_CLIENT_ID = ' WHERE RS_CLIENT_ID = ';
    public const VALUES = 'VALUES ';
    public const INSERT_INTO = 'INSERT INTO ';
    public const QUOTED_AND_CLIENT_ID_GROUP = ') AND RS_CLIENT_ID = \'';
    public const AND = ' AND ';
    public const SELECT = 'SELECT ';
    public const FROM = ' FROM ';
    public const AND_ITEM_ID = ' AND RS_ITEM_ID = ';
    public const RESET_IDENTIFIERS_FOR_CLIENT = 'UPDATE rs_property_identifiers SET RS_DATA = 0 WHERE RS_CLIENT_ID = ';
    public const RESET_USER_ITEMS_FOR_CLIENT = 'UPDATE rs_users SET RS_ITEM_ID = 0 WHERE RS_CLIENT_ID = ';
    public const WHERE_CLIENT_ID_GROUP = ' WHERE (RS_CLIENT_ID = ';
    public const INNER_JOIN = ' INNER JOIN ';
    public const QUOTED_FILTER = '",filter';
    public const DATA_SET_CONTAINS = '.RS_DATA) > 0';
    public const FILTER_DATE_DIFF = 'DATEDIFF(filter';
    public const DATA_WITH_QUOTE = '.RS_DATA,"';
    public const TIMESTAMP_DIFF_SECONDS = 'TIMESTAMPDIFF(SECOND,"';
    public const DATE_DIFF = 'DATEDIFF(';
    public const DATA_COLUMN = '.RS_DATA';
    public const LEFT_JOIN = ' LEFT JOIN ';
    public const FILTER_ALIAS = ' filter';
    public const JOIN_ITEMS_TYPE_FILTER = ' ON (rs_items.RS_ITEMTYPE_ID = filter';
    public const JOIN_ITEM_TYPE_AND_ITEM = '.RS_ITEMTYPE_ID AND rs_items.RS_ITEM_ID = filter';
    public const JOIN_ITEM_CLIENT_FILTER = '.RS_ITEM_ID AND rs_items.RS_CLIENT_ID = filter';
    public const PROPERTY_COLUMN_EQUALS = '.RS_PROPERTY_ID = ';
    public const FIND_IN_SET_QUOTED = 'FIND_IN_SET(\'';
    public const ORDER_BY = 'ORDER BY ';
    public const LIMIT = 'LIMIT ';
    public const SEARCH_SUFFIX_CLIENT = '%\' AND RS_CLIENT_ID=';
    public const UNION = ' UNION ';
    public const AND_ITEM_TYPE_ID_COMPACT = ' AND RS_ITEMTYPE_ID=';
    public const QUOTED_WHERE_CLIENT_ID = '\' WHERE `RS_CLIENT_ID` = \'';
    public const QUOTED_AND_ITEM_TYPE_ID = '\' AND `RS_ITEMTYPE_ID` = \'';
    public const QUOTED_AND_ITEM_ID = '\' AND `RS_ITEM_ID` = \'';
    public const QUOTED_LIMIT_ONE = '\' LIMIT 1';
    public const QUOTED_AND_PROPERTY_ID = '\' AND `RS_PROPERTY_ID` = \'';
    public const AND_USER_ID = ' AND RS_USER_ID=';
    public const AND_PROPERTY_ID_COMPACT = ' AND RS_PROPERTY_ID=';
    public const AND_ACTION_CLIENT_ID = ' AND RS_ACTION_CLIENT_ID=';
}

final class RSMmediaText
{
    public const PROPERTY_LOG = ', propertyID=';
    public const CLIENT_QUERY = 'clientID=';
    public const ITEM_QUERY = '&itemID=';
    public const PROPERTY_QUERY = '&propertyID=';
}

final class RSMupdateText
{
    public const ITEM_TYPE_UPDATE_ERROR = 'ERROR WHILE UPDATING ITEMTYPE';
    public const NAME_PROPERTY = '.name';
    public const PRODUCT_NAME = 'Redsauce Manager';
    public const ALL_MODULES = 'All Modules';
    public const BUILD_NOT_FOUND = 'PRODUCT BUILD NOT FOUND';
    public const PRODUCT_NOT_FOUND = 'PRODUCT NOT FOUND';
    public const ERROR_PREFIX = '[ERROR] on ';
}

final class RSMcalendarText
{
    public const MINUTES_INTERVAL = ' minutes';
    public const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
}

final class RSMresponseText
{
    public const MEMORY_UNIT = '(MiB): ';
    public const XML_CONTENT_TYPE = 'Content-type: text/xml';
    public const CONTENT_LENGTH = 'Content-Length: ';
    public const XML_DECLARATION = '<?xml version="1.0" encoding="UTF-8" ?>';
    public const COLUMN_CDATA = '"><![CDATA[';
    public const COLUMN_END = ']]></column>';
    public const GZIP_CONTENT_TYPE = 'Content-type: application/x-gzip';
    public const JSON_CONTENT_TYPE = 'Content-Type: application/json';
    public const ACCESS_DENIED = 'ACCESS DENIED';
}

final class RSMpropertyText
{
    public const INTEGER_PATTERN = '/\\d+/';
}

