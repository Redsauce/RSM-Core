<?php

function legacyItemTypeAssert($condition, $message)
{
    if (!$condition) throw new RuntimeException($message);
}

function legacyItemTypeExtractFunction($source, $name)
{
    $tokens = token_get_all($source);
    for ($i = 0; $i < count($tokens); $i++) {
        if (!is_array($tokens[$i]) || $tokens[$i][0] !== T_FUNCTION) continue;

        $functionSource = 'function';
        $functionName = null;
        $depth = 0;
        $started = false;

        for ($i++; $i < count($tokens); $i++) {
            $token = $tokens[$i];
            $functionSource .= is_array($token) ? $token[1] : $token;
            if ($functionName === null && is_array($token) && $token[0] === T_STRING) {
                $functionName = $token[1];
            }
            if ($token === '{') {
                $depth++;
                $started = true;
            }
            if ($token === '}') $depth--;
            if ($started && $depth === 0) break;
        }

        if ($functionName === $name) return $functionSource;
    }

    throw new RuntimeException('Missing production function ' . $name);
}

class LegacyItemTypeResult
{
    private $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    public function fetch_assoc()
    {
        return array_shift($this->rows);
    }
}

$queryResults = array();
function RSQuery($query)
{
    global $queryResults;
    return array_shift($queryResults);
}

$root = dirname(__DIR__) . '/Server/htdocs/AppController/commands_RSM/';
$endpointSource = file_get_contents($root . 'itemsManager/classLbxItemPropertiesFilter_getProperties.php');
$managementSource = file_get_contents($root . 'utilities/RSMuserPropertiesManagement.php');

eval(legacyItemTypeExtractFunction($endpointSource, 'resolveItemPropertiesFilterItemTypeID'));
eval(legacyItemTypeExtractFunction($managementSource, 'getUserProperties'));

legacyItemTypeAssert(
    resolveItemPropertiesFilterItemTypeID(array('itemTypeID' => '196'), 'itemTypeID') === '196',
    'canonical itemTypeID must be accepted'
);
legacyItemTypeAssert(
    resolveItemPropertiesFilterItemTypeID(array('itemtypeID' => '197'), 'itemTypeID') === '197',
    'legacy itemtypeID must be accepted as a fallback'
);
legacyItemTypeAssert(
    resolveItemPropertiesFilterItemTypeID(array('itemTypeID' => '196', 'itemtypeID' => '197'), 'itemTypeID') === '196',
    'canonical itemTypeID must take precedence'
);
legacyItemTypeAssert(
    resolveItemPropertiesFilterItemTypeID(array(), 'itemTypeID') === 0,
    'missing item type parameters must resolve to zero'
);
legacyItemTypeAssert(
    strpos($endpointSource, 'TODO: Remove the "itemtypeID" fallback after RSM 7.') !== false,
    'legacy fallback must document its removal milestone'
);

$queryResults = array(false);
$failedProperties = getUserProperties(3, 1, 196);
legacyItemTypeAssert($failedProperties === array(array('lists' => '')), 'failed property query must not be dereferenced');

$queryResults = array(
    new LegacyItemTypeResult(array(array(
        'propertyID' => '1832',
        'propertyName' => 'ISO code',
        'propertyType' => 'text',
        'categoryName' => 'General'
    ))),
    false
);
$failedLists = getUserProperties(3, 1, 196);
legacyItemTypeAssert(count($failedLists) === 2, 'failed list query must preserve property results without a fatal');
legacyItemTypeAssert($failedLists[0]['propertyID'] === '1832', 'property result must be preserved');
legacyItemTypeAssert($failedLists[1] === array('lists' => ''), 'list separator must be preserved');

echo "Legacy itemTypeID compatibility tests passed.\n";
