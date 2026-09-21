<?php
//***************************************************************************************
// Description:
//   Duplica un item raiz y, opcionalmente, sus items dependientes mediante duplicateItem().
//   Los permisos se validan desde este endpoint. Toda la operacion usa una transaccion.
//
// METHOD / URL:
//   POST /AppController/commands_RSM/api/v2/items/duplicate.php
//   OPTIONS: preflight CORS; no crea ni modifica items.
//
// HEADERS:
//   Content-Type: application/json
//   Authorization: <TOKEN>
//   El cliente y el usuario se obtienen de la autenticacion, no del cuerpo JSON.
//
// REQUEST BODY (JSON OBJECT):
//   itemTypeID (obligatorio): tipo del item raiz; ID numerico o nombre de aplicacion
//                            que parseITID pueda resolver para el cliente del token.
//   itemID (obligatorio):     ID entero positivo del item raiz existente.
//   descendants (opcional):  array de relaciones de dependencia que se deben seguir.
//     itemTypeID:            tipo del HIJO, no del padre.
//     dependencyPropertyID:  ID numerico positivo de la propiedad DEL HIJO que apunta
//                            al padre. Debe ser identifier/identifiers y duplicable.
//   Los IDs numericos admiten numeros JSON o cadenas de digitos.
//   No admite arrays de items raiz, numCopies, valores de propiedades ni otros campos.
//
// EXAMPLE 1 - Duplicar solamente el item raiz:
// {
//   "itemTypeID": "41",
//   "itemID": "9158"
// }
//
// EXAMPLE 2 - Duplicar una factura con sus conceptos (IDs ilustrativos):
//   Tipo 41 = facturas; tipo 42 = conceptos.
//   Propiedad 709 = relacion del concepto con su factura.
//   Se buscan los conceptos vinculados a la factura 9158, no todos los del tipo 42.
// {
//   "itemTypeID": "41",
//   "itemID": "9158",
//   "descendants": [
//     {"itemTypeID": "42", "dependencyPropertyID": "709"}
//   ]
// }
//
// EXAMPLE 3 - Incluir otro nivel de dependencia (IDs ilustrativos):
//   La propiedad 810 del tipo 43 apunta al concepto de tipo 42.
//   Cada relacion debe estar conectada al tipo raiz a traves de las seleccionadas.
//   El orden de las entradas del array no determina la jerarquia.
// {
//   "itemTypeID": "41",
//   "itemID": "9158",
//   "descendants": [
//     {"itemTypeID": "42", "dependencyPropertyID": "709"},
//     {"itemTypeID": "43", "dependencyPropertyID": "810"}
//   ]
// }
//
// COMPORTAMIENTO DE LAS COPIAS:
//   - Sin descendants, o con [], solo se duplica el item raiz.
//   - Un hijo exclusivo se duplica y su copia se vincula al padre nuevo.
//   - Si la relacion identifiers del hijo contiene varios padres, se REUTILIZA ese
//     hijo: se conservan sus padres actuales y se agregan los nuevos sin repetirlos.
//     Ejemplo: hijo H con padres [A,B]; al copiar A como A2, H pasa a [A,B,A2].
//     H no se copia ni se recorre su descendencia a traves de esa relacion.
//   - Una propiedad identifiers con un solo padre sigue la copia normal del hijo.
//   - La regla es generica: no depende de que el item sea factura, concepto o script.
//   - El copiador existente tambien sigue la relacion recursiva configurada de un
//     tipo dependiente; esa propiedad debe estar habilitada para duplicacion.
//   - Se copian las propiedades permitidas por RS_AVOID_DUPLICATION = 0. Las excluidas
//     no se rellenan ni se inicializan con valores por defecto desde esta operacion.
//   - No se generan numeros de negocio ni se reinician fechas, series u otros campos.
//   - Repetir una llamada correcta crea otra copia: la operacion no es idempotente.
//
// PERMISOS Y TRANSACCION:
//   READ y CREATE sobre las propiedades duplicables de todos los tipos seleccionados,
//   usando las reglas de permisos del token o visibilidad del usuario. Si un tipo no
//   tiene propiedades duplicables, se comprueba su propiedad principal.
//   Reutilizar un hijo compartido requiere ademas WRITE sobre la relacion modificada.
//   Se comprueba el ambito de cliente del token en origenes, destinos e hijos reutilizados.
//   Ante un fallo se revierten las copias y los cambios en los hijos compartidos.
//
// RESPONSE - HTTP 200 (IDs nuevos ilustrativos; no se garantiza ID original + 1):
//   Sin descendants:
//   {"itemTypeID":41,"sourceItemID":9158,"newItemID":9200}
//
//   Con descendants, se agrega copiedItems con los items realmente CREADOS:
//   {
//     "itemTypeID": 41,
//     "sourceItemID": 9158,
//     "newItemID": 9200,
//     "copiedItems": [
//       {"itemTypeID":41,"sourceItemID":9158,"newItemID":9200},
//       {"itemTypeID":42,"sourceItemID":120,"newItemID":150}
//     ]
//   }
//   copiedItems incluye la raiz y NO incluye los hijos compartidos reutilizados.
//   Con descendants: [], o sin hijos coincidentes, solo contiene la copia de la raiz.
//
// ERRORS:
//   400: cuerpo/IDs/relaciones invalidos, desconectados o excluidos de duplicacion.
//   403: permisos insuficientes o items fuera del ambito permitido por el token.
//   404: item raiz inexistente para el cliente y tipo indicados.
//   405: metodo HTTP no admitido. La autenticacion usa los errores comunes de la API.
//   500: fallo interno al leer, copiar, actualizar relaciones o confirmar la operacion.
//   Formato: {"message":"..."}. El detalle depende de RSallowDebug; los fallos internos
//   indican la fase en modo debug. La excepcion se registra despues del rollback.
//***************************************************************************************
require_once '../../../utilities/RStools.php';
require_once '../../../utilities/RSMverifyBody.php';
handleApiCorsPreflight('POST');
setAuthorizationTokenOnGlobals();
checkCorrectRequestMethod('POST');

require_once '../../../utilities/RSdatabase.php';
require_once '../../../utilities/RSMitemsManagement.php';

// 1. Validar el cuerpo y resolver los tipos dentro del cliente autenticado.
$body = getRequestBody();
if (!is_object($body) || !isset($body->itemTypeID, $body->itemID)
    || count(array_diff(array_keys(get_object_vars($body)), array('itemTypeID', 'itemID', 'descendants'))) > 0
    || (property_exists($body, 'descendants') && !is_array($body->descendants))
    || (!is_string($body->itemTypeID) && !is_int($body->itemTypeID))
    || trim((string)$body->itemTypeID) === ''
    || (!is_string($body->itemID) && !is_int($body->itemID))
    || !ctype_digit((string)$body->itemID)
    || filter_var(ltrim((string)$body->itemID, '0'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) === false) {
    returnJsonMessage(400, $RSallowDebug ? 'Expected itemTypeID and one positive integer itemID' : '');
}

$RStoken = getRStoken();
$clientID = RSclientFromToken($RStoken);
$RSuserID = getRSuserID();
$resolvedItemTypeID = parseITID($body->itemTypeID, $clientID);
if (!ctype_digit((string)$resolvedItemTypeID)
    || filter_var(ltrim((string)$resolvedItemTypeID, '0'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) === false) {
    returnJsonMessage(400, $RSallowDebug ? 'Invalid itemTypeID' : '');
}
$itemTypeID = intval($resolvedItemTypeID);
$itemID = intval($body->itemID);
$edges = array();
$typeIDs = array($itemTypeID => true);
foreach ($body->descendants ?? array() as $descendant) {
    if (!is_object($descendant) || !isset($descendant->itemTypeID, $descendant->dependencyPropertyID)
        || count(array_diff(array_keys(get_object_vars($descendant)), array('itemTypeID', 'dependencyPropertyID'))) > 0
        || (!is_string($descendant->itemTypeID) && !is_int($descendant->itemTypeID))
        || trim((string)$descendant->itemTypeID) === ''
        || (!is_string($descendant->dependencyPropertyID) && !is_int($descendant->dependencyPropertyID))
        || !ctype_digit((string)$descendant->dependencyPropertyID)
        || filter_var(ltrim((string)$descendant->dependencyPropertyID, '0'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) === false) {
        returnJsonMessage(400, $RSallowDebug ? 'Expected descendant itemTypeID and positive dependencyPropertyID' : '');
    }
    $childTypeID = parseITID($descendant->itemTypeID, $clientID);
    if (!ctype_digit((string)$childTypeID)
        || filter_var(ltrim((string)$childTypeID, '0'), FILTER_VALIDATE_INT, array('options' => array('min_range' => 1))) === false) {
        returnJsonMessage(400, $RSallowDebug ? 'Invalid descendant itemTypeID' : '');
    }
    $childTypeID = intval($childTypeID);
    $propertyID = intval($descendant->dependencyPropertyID);
    $edges[$childTypeID . ':' . $propertyID] = array('itemTypeID' => $childTypeID, 'dependencyPropertyID' => $propertyID);
    $typeIDs[$childTypeID] = true;
}
if ($itemTypeID <= 0) returnJsonMessage(400, $RSallowDebug ? 'Invalid itemTypeID' : '');
if (!verifyItemExists($itemID, $itemTypeID, $clientID)) {
    returnJsonMessage(404, $RSallowDebug ? 'Source item does not exist' : '');
}

$failureCode = 500;
$failureMessage = 'Unable to duplicate item';
$newItemID = null;
$transactionStarted = false;
$failureLog = null;
try {
    $failureStage = 'starting transaction';
    if (!$mysqli->begin_transaction()) throw new RuntimeException('Unable to start duplication transaction');
    $transactionStarted = true;
    // 2. Bloquear los contadores de IDs en orden estable y cargar los metadatos
    //    que se usaran tanto para comprobar permisos como para copiar propiedades.
    ksort($typeIDs, SORT_NUMERIC);
    $itemTypeProperties = array();
    foreach ($typeIDs as $typeID => $_) {
        $failureStage = 'locking item type ' . $typeID;
        if (!RSlockItemTypeForDuplication($typeID, $clientID)) throw new RuntimeException('Unable to lock item type');
        $failureStage = 'reading properties of item type ' . $typeID;
        $itemTypeProperties[$typeID] = getClientItemTypeProperties($typeID, $clientID, 1, true);
    }
    if (!verifyItemExists($itemID, $itemTypeID, $clientID)) {
        $failureCode = 404;
        throw new RuntimeException('Source item no longer exists');
    }

    // 3. Comprobar que cada propiedad pertenece al hijo y obtener su tipo padre.
    foreach ($edges as &$edge) {
        $failureStage = 'validating dependency property ' . $edge['dependencyPropertyID'];
        $propertiesByID = array_column($itemTypeProperties[$edge['itemTypeID']], null, 'id');
        $property = $propertiesByID[$edge['dependencyPropertyID']] ?? null;
        if (!$property || !in_array($property['type'], array('identifier', 'identifiers'), true)) {
            $failureCode = 400;
            $failureMessage = 'Dependency must be an eligible relation belonging to the descendant type';
            throw new RuntimeException($failureMessage);
        }
        $edge['parentTypeID'] = intval(getClientPropertyReferredItemType($edge['dependencyPropertyID'], $clientID));
    }
    unset($edge);
    // The shared copier also follows a dependent type's configured recursive
    // relation. Validate that relation against the same authorized metadata.
    foreach ($edges as $edge) {
        $recursiveID = intval(getRecursivePropertyID($edge['itemTypeID'], $clientID));
        if ($recursiveID && !in_array($recursiveID, array_column($itemTypeProperties[$edge['itemTypeID']], 'id'))) {
            $failureCode = 400;
            $failureMessage = 'Recursive dependency must be eligible for duplication';
            throw new RuntimeException($failureMessage);
        }
    }
    $reachable = array($itemTypeID => true);
    do {
        $previousCount = count($reachable);
        foreach ($edges as $edge) {
            if (isset($reachable[$edge['parentTypeID']])) $reachable[$edge['itemTypeID']] = true;
        }
    } while (count($reachable) !== $previousCount);
    foreach ($edges as $edge) {
        if (!isset($reachable[$edge['parentTypeID']])) {
            $failureCode = 400;
            $failureMessage = 'Every dependency must be reachable from the root type';
            throw new RuntimeException($failureMessage);
        }
    }

    // Authorize the exact metadata used by every copy, including empty lists.
    foreach ($itemTypeProperties as $typeID => $properties) {
        $failureStage = 'authorizing item type ' . $typeID;
        $permissionIDs = array_column($properties, 'id');
        if (count($permissionIDs) === 0) $permissionIDs = array(getMainPropertyID($typeID, $clientID));
        foreach ($permissionIDs as $propertyID) {
            if (intval($propertyID) <= 0
                || !(RShasTokenPermission($RStoken, $propertyID, 'READ') || isPropertyVisible($RSuserID, $propertyID, $clientID))
                || !(RShasTokenPermission($RStoken, $propertyID, 'CREATE') || isPropertyVisible($RSuserID, $propertyID, $clientID))) {
                $failureCode = 403;
                $failureMessage = 'No permission to duplicate all eligible properties';
                throw new RuntimeException($failureMessage);
            }
        }
        if (RSisCustomerScopedToken($RStoken)) {
            $dependencyID = RSgetTokenCustomerDependencyPropertyID($RStoken, $typeID, $clientID);
            if (!$dependencyID || !in_array($dependencyID, array_column($properties, 'id'))) {
                $failureCode = 403;
                $failureMessage = 'Customer dependency cannot be excluded from the copy';
                throw new RuntimeException($failureMessage);
            }
        }
    }

    // 4. Adaptar el JSON al formato del copiador: padre => [[tipoHijo, propiedad], ...].
    //    La busqueda de hijos y su copia/reutilizacion se delegan a duplicateItem().
    $descendants = array();
    foreach ($edges as $edge) {
        $descendants[$edge['parentTypeID']][] = array($edge['itemTypeID'], $edge['dependencyPropertyID']);
    }
    $failureStage = 'checking source scope';
    if (!RSitemMatchesTokenCustomerScope($RStoken, $clientID, $itemTypeID, $itemID)) {
        $failureCode = 403;
        $failureMessage = 'Source item is outside token customer scope';
        throw new RuntimeException($failureMessage);
    }
    $failureStage = 'copying item and descendants';
    $copiedItems = array();
    // El copiador invoca esta validacion antes y despues de modificar un hijo
    // compartido. La politica de permisos permanece definida en este endpoint.
    $validateSharedItem = function ($typeID, $sharedItemID, $propertyID) use ($RStoken, $RSuserID, $clientID, &$failureCode, &$failureMessage) {
        if (!(RShasTokenPermission($RStoken, $propertyID, 'WRITE') || isPropertyVisible($RSuserID, $propertyID, $clientID))
            || !RSitemMatchesTokenCustomerScope($RStoken, $clientID, $typeID, $sharedItemID)) {
            $failureCode = 403;
            $failureMessage = 'No permission to update shared child relation';
            throw new RuntimeException($failureMessage);
        }
    };
    $newItemID = duplicateItem($itemTypeID, $itemID, $clientID, 1, $descendants, $copiedItems, $itemTypeProperties, $validateSharedItem);
    if (!is_int($newItemID) || $newItemID <= 0) throw new RuntimeException('Copy failed');
    // 5. Verificar el ambito de todos los origenes y destinos antes de confirmar.
    //    Un rechazo aqui tambien revierte las escrituras realizadas por el copiador.
    $copies = array();
    foreach ($copiedItems as $typeID => $items) {
        foreach ($items as $sourceID => $newIDs) {
            // Validate every source reached by the shared traversal before commit.
            if (!RSitemMatchesTokenCustomerScope($RStoken, $clientID, $typeID, $sourceID)) {
                $failureCode = 403;
                $failureMessage = 'Source item is outside token customer scope';
                throw new RuntimeException($failureMessage);
            }
            $copies[] = array('itemTypeID' => intval($typeID), 'sourceItemID' => intval($sourceID), 'newItemID' => $newIDs[0]);
        }
    }
    foreach ($copies as $copy) {
        $failureStage = 'checking destination scope for item ' . $copy['itemTypeID'] . ':' . $copy['newItemID'];
        if (!RSitemMatchesTokenCustomerScope($RStoken, $clientID, $copy['itemTypeID'], $copy['newItemID'])) {
            $failureCode = 403;
            $failureMessage = 'Copied item is outside token customer scope';
            throw new RuntimeException($failureMessage);
        }
    }
    $newItemID = $copiedItems[$itemTypeID][$itemID][0];
    $failureStage = 'committing copies';
    if (!$mysqli->commit()) throw new RuntimeException('Unable to commit copy');
    $transactionStarted = false;
} catch (Throwable $exception) {
    $failureLog = 'duplicate item: ' . $failureStage . ': ' . get_class($exception) . ': ' . $exception->getMessage();
    if ($failureCode === 500) $failureMessage .= ' (' . $failureStage . ')';
    $newItemID = null;
} finally {
    if ($transactionStarted) $mysqli->rollback();
}

// RSError writes through the same DB connection; log only after rollback so the
// diagnostic is not discarded along with the failed copies.
if ($failureLog !== null) RSError($failureLog);
if ($newItemID === null) returnJsonMessage($failureCode, $RSallowDebug ? $failureMessage : '');
$response = array('itemTypeID' => $itemTypeID, 'sourceItemID' => $itemID, 'newItemID' => $newItemID);
if (property_exists($body, 'descendants')) $response['copiedItems'] = $copies;
returnJsonResponse(json_encode($response));
