<?php

namespace Scenes\Models;

use PDO;
use PDOException;
use Exception;

class BaseModel 
{
    /**
     * The PDO database connection
     */
    protected $db;
    
    /**
     * The table associated with the model
     */
    protected $table;
    
    /**
     * The primary key for the model
     */
    protected $primaryKey = 'id';
    
    /**
     * The columns that can be filled via mass assignment
     */
    protected $fillable = [];
    
    /**
     * Query builder parts
     */
    protected $select = '*';
    protected $where = [];
    protected $orderBy = [];
    protected $limit = null;
    protected $offset = null;
    
    /**
     * Constructor initializes the database connection
     */
    public function __construct() 
    {
        $this->connect();
    }
    
    /**
     * Establishes a connection to the SQLite database
     */
    protected function connect() 
    {
        try {
            $dbPath = dirname(dirname(__DIR__)) . '/data/scenes.db';
            $this->db = new PDO('sqlite:' . $dbPath);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Find a record by its primary key
     * 
     * @param int $id The primary key value
     * @return array|null The record if found, null otherwise
     */
    public function find($id) 
    {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return $result !== false ? $result : null;
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Get all records from the table
     * 
     * @return array Array of records
     */
    public function all() 
    {
        try {
            $sql = "SELECT * FROM {$this->table}";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e);
        }
    }
    
    /**
     * Create a new record in the database
     * 
     * @param array $data Associative array of data to insert
     * @return int|bool The ID of the new record or false on failure
     */
    public function create(array $data) 
    {
        try {
            // Filter data to only include fillable columns
            $filteredData = array_intersect_key($data, array_flip($this->fillable));
            
            $columns = implode(', ', array_keys($filteredData));
            $placeholders = ':' . implode(', :', array_keys($filteredData));
            
            $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
            $stmt = $this->db->prepare($sql);
            
            foreach ($filteredData as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->execute();
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Update an existing record in the database
     * 
     * @param int $id The ID of the record to update
     * @param array $data Associative array of data to update
     * @return bool Success status
     */
    public function update($id, array $data) 
    {
        try {
            // Filter data to only include fillable columns
            $filteredData = array_intersect_key($data, array_flip($this->fillable));
            
            $setClause = '';
            foreach (array_keys($filteredData) as $key) {
                $setClause .= "$key = :$key, ";
            }
            $setClause = rtrim($setClause, ', ');
            
            $sql = "UPDATE {$this->table} SET $setClause WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->prepare($sql);
            
            $stmt->bindValue(':id', $id);
            foreach ($filteredData as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Delete a record from the database
     * 
     * @param int $id The ID of the record to delete
     * @return bool Success status
     */
    public function delete($id) 
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', $id);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            $this->handleError($e);
            return false;
        }
    }
    
    /**
     * Begin a database transaction
     */
    public function beginTransaction() 
    {
        $this->db->beginTransaction();
    }
    
    /**
     * Commit a database transaction
     */
    public function commit() 
    {
        $this->db->commit();
    }
    
    /**
     * Rollback a database transaction
     */
    public function rollback() 
    {
        $this->db->rollBack();
    }
    
    /**
     * Add a where clause to the query
     * 
     * @param string $column Column name
     * @param string $operator Comparison operator
     * @param mixed $value Value to compare against
     * @return $this For method chaining
     */
    public function where($column, $operator, $value = null) 
    {
        // If only two parameters are provided, assume equality
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->where[] = [
            'column' => $column,
            'operator' => $operator,
            'value' => $value
        ];
        
        return $this;
    }
    
    /**
     * Add an ORDER BY clause to the query
     * 
     * @param string $column Column to order by
     * @param string $direction Sort direction (ASC or DESC)
     * @return $this For method chaining
     */
    public function orderBy($column, $direction = 'ASC') 
    {
        $this->orderBy[] = [
            'column' => $column,
            'direction' => strtoupper($direction)
        ];
        
        return $this;
    }
    
    /**
     * Add a LIMIT clause to the query
     * 
     * @param int $limit Maximum number of records to return
     * @return $this For method chaining
     */
    public function limit($limit) 
    {
        $this->limit = (int) $limit;
        return $this;
    }
    
    /**
     * Add an OFFSET clause to the query
     * 
     * @param int $offset Number of records to skip
     * @return $this For method chaining
     */
    public function offset($offset) 
    {
        $this->offset = (int) $offset;
        return $this;
    }
    
    /**
     * Execute the built query and return the results
     * 
     * @return array Query results
     */
    public function get() 
    {
        try {
            $sql = $this->buildQuery();
            $stmt = $this->db->prepare($sql);
            
            // Bind values for WHERE clauses
            $this->bindWhereValues($stmt);
            
            $stmt->execute();
            
            // Reset query builder state
            $this->resetQueryBuilder();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->handleError($e);
            return [];
        }
    }
    
    /**
     * Execute the built query and return the first result
     * 
     * @return array|null First result or null if none
     */
    public function first() 
    {
        // Limit to 1 record
        $this->limit(1);
        
        $results = $this->get();
        return count($results) > 0 ? $results[0] : null;
    }
    
    /**
     * Build the SQL query from the query builder parts
     * 
     * @return string The complete SQL query
     */
    protected function buildQuery() 
    {
        $sql = "SELECT {$this->select} FROM {$this->table}";
        
        // Add WHERE clauses
        if (!empty($this->where)) {
            $sql .= " WHERE ";
            $whereClauses = [];
            
            foreach ($this->where as $index => $condition) {
                $whereClauses[] = "{$condition['column']} {$condition['operator']} :where_{$index}";
            }
            
            $sql .= implode(' AND ', $whereClauses);
        }
        
        // Add ORDER BY clauses
        if (!empty($this->orderBy)) {
            $sql .= " ORDER BY ";
            $orderClauses = [];
            
            foreach ($this->orderBy as $order) {
                $orderClauses[] = "{$order['column']} {$order['direction']}";
            }
            
            $sql .= implode(', ', $orderClauses);
        }
        
        // Add LIMIT clause
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        
        // Add OFFSET clause
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }
        
        return $sql;
    }
    
    /**
     * Bind values for WHERE clauses to the prepared statement
     * 
     * @param \PDOStatement $stmt The prepared statement
     */
    protected function bindWhereValues($stmt) 
    {
        foreach ($this->where as $index => $condition) {
            $stmt->bindValue(":where_{$index}", $condition['value']);
        }
    }
    
    /**
     * Reset the query builder state
     */
    protected function resetQueryBuilder() 
    {
        $this->select = '*';
        $this->where = [];
        $this->orderBy = [];
        $this->limit = null;
        $this->offset = null;
    }
    
    /**
     * Handle database exceptions
     * 
     * @param \Exception $e The exception to handle
     * @throws \Exception Re-throws the exception with additional info
     */
    protected function handleError($e) 
    {
        // Log the error
        error_log("Database error: " . $e->getMessage());
        
        // In a production environment, you might want to throw a custom exception
        // or handle the error differently
        throw new Exception("Database operation failed: " . $e->getMessage(), 0, $e);
    }
}
