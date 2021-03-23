<?php
require_once "Server/htdocs/AppController/commands_RSM/utilities_2/RSqueries.php";
require_once "Server/htdocs/AppController/commands_RSM/database/RSclient_property.php";
require_once "Server/htdocs/AppController/commands_RSM/database/RSQuery.php";
require_once "Server/htdocs/AppController/commands_RSM/database/RSproperty_app_relations.php";
require_once "Server/htdocs/AppController/commands_RSM/database/RSproperty_app_definitions.php";
require_once "Server/htdocs/AppController/commands_RSM/database/RSitemtype_app_relations.php";
require_once "Server/htdocs/AppController/commands_RSM/database/RSclient_category.php";

class Database {
    use TraitClientProperty, TraitPropertyAppRelations, TraitPropertyAppDefinitions, TraitItemtypeAppRelations,
        TraitClientCategory;
    
    public array $queries;
    public RSQuery $RSQuery;  
    public function __construct(){
        $classQueries = new RSQueries();
        $this->queries = $classQueries->queries;
        $this->RSQuery = new RSQuery();
    }

    public function attach($RSQuery) { // Nota: Esto es momentáneo
        $this->RSQuery = $RSQuery;
    }

    public function connect() {        
        // require_once "RSqueries.php";
        require_once "Server/htdocs/AppController/commands_RSM/utilities/RSconfiguration.php";
        global $mysqli;
        global $queryCount;
        // Save the start time for debugging
        $php_start = microtime(TRUE);
        
        // This variable counts the number of queries performed
        $queryCount = 0; 
        
        // Connect to the database using the above settings
        $mysqli = new mysqli('localhost', 'ibra', 'ibra', 'testrsm');
        if ($mysqli->connect_errno) {
            RSReturnError("CANNOT CONNECT TO DATABASE SERVER", -1);
        }

        // Check database compatibility and user permisions
        if (!isset($RSUpdatingProcess)) {
            require_once ("Server/htdocs/AppController/commands_RSM/utilities/RSsecurityCheck.php");
        }

        return $mysqli;
    }

    public function close() {
        global $mysqli;
        $mysqli->close();
    }

    public function RSSelect($group, $element, $params, $queryConcatenation = '') { //Mirar mejor el tema de los nombres
        $result = $this->RSExecute($group, $element, $params, 'get', $queryConcatenation);
        if (!$result) return $result;
        return $result->fetch_assoc();
    }

    public function RSInsert($group, $element, $params, $queryConcatenation = '') { //Mirar mejor el tema de los nombres
        return $this->RSExecute($group, $element, $params, 'insert', $queryConcatenation);
    }

    public function RSUpdate($group, $element, $params, $queryConcatenation = '') { //Mirar mejor el tema de los nombres
        return $this->RSExecute($group, $element, $params, 'update', $queryConcatenation);
    }

    public function RSDelete($group, $element, $params, $queryConcatenation = '') { //Mirar mejor el tema de los nombres
        return $this->RSExecute($group, $element, $params, 'delete', $queryConcatenation);
    }

    public function RSExecute($group, $element, $params, $action, $queryConcatenation) {
        $query = $this->queries[$group][$action.$element]['query'];
        if ($queryConcatenation !== '') $query = $query . $queryConcatenation;
        $types = $this->queries[$group][$action.$element]['types'];
        $result = $this->RSQuery->makeQuery($query, $types, $params);
        return $result;
    }
}

$db = new Database();
