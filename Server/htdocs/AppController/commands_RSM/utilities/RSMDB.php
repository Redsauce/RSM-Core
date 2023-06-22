<?php

require_once 'vendor/autoload.php'; // include Composer stuff installed with: composer require "mongodb/mongodb=^1.0.0"

/**
 * RSM's Database Interface Class
 *
 * This class presents an interface to interact with the Mongo Database easily
 */
class RSMDB
{
    private $client = null;
    private $database = null;
    private $collection = null;
    private $collectionName = "";

    /**
     * SetClient
     *
     * This function establishes a connection with a MongoDB instance
     *
     * @param $address (String) Contains the location of the MongoDB instance
     */
    public function SetClient($address)
    {
        $this->client = new MongoDB\Client($address);
    }

    /**
     * SetDatabase
     *
     * This function chooses a Database in the MongoDB instance to work with. The Database needn't exist.
     *
     * @param $db (Any) Contains the id of the database.
     */
    public function SetDatabase($db)
    {
        $this->database = $db;
        if (!is_null($this->collection)) {
            //To Avoid PHP Syntax quirks
            $name = $this->collectionName;
            $this->collection = $this->client->$db->$name;
        }
    }

    /**
     * SetCollection
     *
     * This function chooses a Collection (Table in MySQL) in the Database to work with. The Collection needn't exist. A Database must've been initialized previously
     *
     * @param $col (Any) Contains the id of the database.
     */
    public function SetCollection($col)
    {
        $db = $this->database; //Because of PHP Syntax reasons
        $this->collection = $this->client->$db->$col;
        $this->collectionName = $col;
    }

    /**
     * Query
     *
     * Execute a Query on the selected database collection
     *
     * @param $query (Array) Contains the query information in the following format: ['key' => 'value', 'key2' => 'value2']
     * @return MongoDB\Driver\Cursor Which can be iterated in a foreach ($result as $entry) where each entry's information can be accessed as $entry['key']
     */
    public function Query($query)
    {
        return $this->collection->find($query);
    }

    /**
     * Insert
     *
     * Insert any number of Documents (Rows in MySQL)  into the Collection.
     *
     * @param $data (Array) Contains the information in the following format (Note the double array is necessary even for a single element):
     * [['key' => 'value', 'key2' => 'value2'], ['key' => 'value3']]
     * @return MongoDB\InsertManyResult Which can be consulted with ->getInsertedIds to obtain an Array with the IDs of all successful inserts.
     */
    public function Insert($data)
    {
        return $this->collection->insertMany($data);
    }

    /**
     * Remove
     *
     * Remove any number of Documents from the Collection.
     *
     * @param $filter (Array) Contains the query information in the following format: ['key' => 'value', 'key2' => 'value2']
     * @return MongoDB\DeleteManyResult Which can be consulted with ->getDeletedIds to obtain an Array with the IDs of all successful inserts.
     */
    public function Remove($filter)
    {
        return $this->collection->deleteMany($filter);
    }

    /**
     * DropDatabase
     *
     * Drops the entire selected Database.
     *
     */
    public function DropDatabase()
    {
        $db = $this->database;
        $this->client->$db->drop();
    }

    /**
     * DropCollection
     *
     * Drops the entire selected Collection.
     *
     */
    public function DropCollection()
    {
        $this->collection->drop();
    }
}
