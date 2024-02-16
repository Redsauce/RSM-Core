<?php
//*** /gql/get.php ***
//*** Receives a graphQL request and returns the results in graphQL/JSON format ***
//**/ authorization: token in auth header ***
//*** input: graphQL query structure ***
//*** output: graphQL/JSON response ***

/*
query {
   Region {
      Name
      Country {
         Name
         Product {
            Name
            Product Status
         }
      }
   }
}
*/

// Database connection startup
require_once "../utilities/RSdatabase.php";
require_once "../utilities/RSMitemsManagement.php";
require_once "../utilities/RStools.php";

// Definitions
$search  = array("'", "\"");
$replace = array("&rsquo;" , "&quot;");
// Get Token from header without injection risk
isset($_SERVER["768507d7d183e039fe6f0af98d0accfa"]) ? $RStoken = str_replace($search, $replace, $_SERVER["768507d7d183e039fe6f0af98d0accfa"]) : dieWithError(401);

// Get clientID from token
$clientID = RSClientFromToken($RStoken);
$GLOBALS['RS_POST']['clientID'] = $clientID;

// Get graphQL query
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE); //convert JSON into array

// Define that output will be compressed if possible
ob_start('ob_gzhandler');

// Include JSON format header
header("content-type: application/json");

// Start processing received graphQL query
$result = parseItem(trim($input["query"]), 0, 0, true);



// Return data converted to JSON (and compressed if possible)
echo json_encode(array("data"=>$result));




// Function to parse all elements inside an {} element of the query structure
function parseLevel ($input, $linkingProperty = 0, $parentValue = 0) {
   $properties = array();
   $results = array();

   // Sequentially process each line and remove it from input once parsed
   while ($input != "") {
      // Get position of next line delimiter
      // TODO elements can be splitted by spaces and commas also
      $newLinePos = strpos ($input, "\n");

      // Get the line and remove from input
      if ($newLinePos === false) {
         // No more lines, get whole input
         $cleanLine = trim($input);
         $input = "";
      } else {
         // Extract line and remove from input
         $cleanLine = trim(substr($input, 0, $newLinePos));
         $input = trim(substr($input, $newLinePos));
      }

      if ($cleanLine != "") {
         //look for nested item
         $openingPos = strpos ($input, "{");
         if ($openingPos !== false) {
            // Found an item inside line, so process it
            // Revert line to input in order to extract the whole item structure
            $input = $cleanLine . "\n" . $input;
            // Look for item closing
            $nextClosing = findMatchingClosing ($input);
            if ($nextClosing !== false) {
               // extract element from input and process item separatedly
               $results[] = parseItem (substr($input, 0, $nextClosing+1), $linkingProperty, $parentValue);

               // Remove the item from input
               $input = trim(substr($input, $nextClosing+1));
            } else {
               // Opening without closing mean bad formatted query, return error
               returnError ("Malformed query, missing element closing"); //TODO return error location according to graphQL spec
            }

         } else {
            // Valid property with no item inside, store it
            $properties[] = $cleanLine;
         }
      }
   }

   // TODO check permisions and extract properties



   return $results;
}


// Function to parse one itemtype node
function parseItem ($input, $linkingProperty = 0, $parentValue = 0, $rootElement = false) {

   // Remove root query command if passed
   if($rootElement && (stripos($input,"query") === 0 || stripos($input,"{") === 0)) {
      // Get string from first { to last char-1 (skip "query:{" from beginning and last "}" from end )
      // TODO manage optional operation name (for error handling only)
      $input = trim(substr($input, strpos($input,"{")+1, -1));
   }
   // Get field (itemType/related_property) name
   $field = trim(substr($input, 0, strpos($input,"{")));

   //Second get the arguments (filters) for the itemtype with ()
   $filterProperties = array();
   $arguments = array();
   if (strpos($field,"(") !== false) {
      // TODO consider other delimiters than "," and beware of " " and """ """
      $arguments = explode(",",substr($field,strpos($field, "(")+1, -1));
      // Remove arguments from field value
      $field = trim(substr($field, 0, strpos($input,"(")));
   }

   // TODO Get itemtype from field (directly or through related property)****************************************************************



   // Generate filter from arguments
   foreach ($arguments as $argument) {
      $argParts = explode(":", $argument);

      // TODO complete filter after itemtype DONE
      $filterProperties[] = array('ID' => trim($argParts[0]), 'value' => trim($argParts[1]));
      //$filterProperties[] = array('ID' => $linkPropertyID, 'value' => $linkValue, 'mode' => '<-IN');
   }


   // TODO Get items
   //getFilteredItemsIDs($itemTypeID, $clientID, $filterProperties, $returnProperties, $orderBy);

   // TODO Parse properties for each item
   // return array(parseLevel ($input, $linkingProperty, $parentValue));
}


// Writes error message to output and terminates processing query
function returnError ($errorMsg) {
   // Return data converted to JSON (and compressed if possible)
   echo json_encode(array("errors" => array(array("message" => $errorMsg))));
   // Terminate execution
   exit;
}


// Gets the position of the closing element matching the first opening element defined
function findMatchingClosing ($input, $openingChar = "{", $closingChar = "}") {
   // TODO beware of " " and """ """ elements
   //Get the position of first opening and return false if not fount
   $pos = strpos ($input, $openingChar);
   if ($pos === false) return false;

   $levelsCount = 1;

   do {
      // get next occurence of opening and closing
      $nextOpen = strpos ($input, $openingChar, $pos + 1);
      $nextClose = strpos ($input, $closingChar, $pos + 1);
      // If next closing is missing the structure is wrong/incomplete so returning false
      if ($nextClose === false) return false;

      if ($nextOpen !== false || $nextOpen < $nextClose) {
         //If next opening exists and found before next closing store its position and increment levels count
         $pos = $nextOpen;
         $levelsCount++;
      } else {
         //Next closing found before next opening, store its position and decrement levels count
         $pos = $nextClose;
         $levelsCount--;
      }
   } while ($levelsCount > 0);

   return $pos;
}

?>