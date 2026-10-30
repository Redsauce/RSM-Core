<?php
// Exercise the production security branch without a database or credentials.
class TokenSecurityDenied extends RuntimeException {}
function RSCheckUserAccess() {
    $GLOBALS['userChecks']++;
    return $GLOBALS['testUserID'];
}
function RSisTokenEnabled($token) { return $token === 'enabled'; }
function RSClientFromToken($token) { return 7; }
function RSCheckCompatibleDB($mode) { return 1; }
function RSReturnError($message, $code) { throw new TokenSecurityDenied($message, $code); }

$source = file_get_contents(__DIR__ . '/../Server/htdocs/AppController/commands_RSM/utilities/RSsecurityCheck.php');
$start = strpos($source, '$RSuserID =');
if ($start === false) throw new RuntimeException('Security entry point missing');
$source = substr($source, $start);
function checkTokenSecurity($post, $userID, $expectedChecks, $expectedID, $expectedError = null) {
    global $source;
    $cstRS_POST = 'testPost'; $cstRS_GET = 'testGet';
    $cstClientID = 'clientID'; $cstRStoken = 'RStoken';
    $GLOBALS['testPost'] = $post; $GLOBALS['testGet'] = array();
    $GLOBALS['userChecks'] = 0; $GLOBALS['testUserID'] = $userID;
    $error = null;
    try { eval($source); }
    catch (TokenSecurityDenied $exception) { $error = $exception->getCode(); }
    if ($error !== $expectedError || $GLOBALS['userChecks'] !== $expectedChecks || $RSuserID !== $expectedID) {
        throw new RuntimeException('Unexpected authentication result');
    }
}
checkTokenSecurity(array('clientID'=>7, 'RStoken'=>'enabled'), 0, 0, 0);
checkTokenSecurity(array('clientID'=>7, 'RStoken'=>'disabled'), 0, 0, 0, -3);
checkTokenSecurity(array('clientID'=>8, 'RStoken'=>'enabled'), 0, 0, 0, -3);
checkTokenSecurity(array('clientID'=>7, 'RStoken'=>'enabled', 'RSLogin'=>'user'), 12, 1, 12);
checkTokenSecurity(array('clientID'=>7, 'RSLogin'=>'user'), 12, 1, 12);
checkTokenSecurity(array('clientID'=>7), 0, 1, 0, -5);
echo "OK: token-only and user authentication checks passed\n";
