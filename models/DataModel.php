<?php
include_once('config/db.php');
class DataModel {
	protected $data = array();
	
	/**
	 * PHP magic getter for retrieving values via $obj->fieldName
	 * @param String $name
	 */
	public function __get($name) {
		if (array_key_exists($name, $this->data)) {
			return $this->data[$name];
		}
	}

	/**
	 * PHP magic setter for setting values with $obj->fieldName = value
	 * @param String $name Name of field to set
	 * $param mixed $value New value for field
	 */
	public function __set($name,$value) {
		if($name == $this->getIdName() && $this->inDB()) {
			$trace = debug_backtrace();
			$msg = '<div class="error">Cannot change id: '.$name.' in '.$trace[0]['file'].' on line '.$trace[0]['line'].'</div>';
			trigger_error($msg,E_USER_NOTICE);
			return null;
		} else {
			$this->data[$name] = $value;
		}
	}

	/**
	 * Retrieves a connection to the database
	 */
	public static function connect($dbname=DB_NAME,$host='localhost',$dbusername=DB_USERNAME,$dbpassword=DB_PASSWORD) {
		try {
			return new PDO('mysql:dbname='.DB_NAME.';host=localhost',DB_USERNAME,DB_PASSWORD);
		} catch (PDOException $e) {
			self::pdoError($e);
			die();
		}
	}
	
	/**
	 * Executes a SELECT query on the provided table name, using the
	 * provided field names & values
	 * @param String $tableName
	 * @param Array $fields
	 * @return Array of objects, whose class is specified by $className
	 */
	public static function find($className, $orderBy=null, $fields=null) {
		$tableName = self::getPluralForm($className);
		$sql = "SELECT * FROM $tableName";
		$values = null;
		// Construct WHERE clause, if fields have been provided
		if($fields != null && count($fields) > 0) {
			$sql .= ' WHERE ';
			$values = array();
			$i = 0;
			foreach($fields as $name => $value) {
				// Add AND if this is not the first field in the WHERE clause
				if($i > 0) {
					$sql .= ' AND ';
				}
				$sql .= "$name LIKE ?";
				$values[] = '%'.$value.'%';
				$i++;
			}
		}
		
		if($orderBy != null) {
			$sql .= "ORDER BY $order";
		}
		
		
		// Connect to DB
		$conn = self::connect();
		
		// Prepare SQL statement
		$stmt = $conn->prepare($sql);
		
		// Execute SQL
		$stmt->execute($values);
				
		// Fetch results
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$objArray = array();
		foreach($results as $result) {
			$rc = new ReflectionClass($className);
			$obj = $rc->newInstance();
			$obj->data = $result;
			$objArray[] = $obj;
		}
		
		// Close connection
		unset($conn);
		
		// Return array
		return $objArray;
	}
	
	/**
	 * Retrieves all records having non-numeric column name LIKE the one provided
	 * @param String $className Name of class
	 * @param String $value Value to search for in DB
	 */
	public static function findAny($className,$value) {
		// Get all non-numeric & non-date fields
		$fields = self::getStringFields($className);
		
		$tableName = self::getPluralForm($className);
		$sql = "SELECT * FROM $tableName";
		$values = null;
		// Construct WHERE clause, if fields have been provided
		if($fields != null && count($fields) > 0) {
			$sql .= ' WHERE ';
			$values = array();
			$i = 0;
			foreach($fields as $name) {
				// Add AND if this is not the first field in the WHERE clause
				if($i > 0) {
					$sql .= ' OR ';
				}
				$sql .= "$name LIKE ?";
				$values[] = '%'.$value.'%';
				$i++;
			}
		}
		
		
		// Connect to DB
		$conn = self::connect();
		
		// Prepare SQL statement
		$stmt = $conn->prepare($sql);
		
		// Execute SQL
		$stmt->execute($values);
				
		// Fetch results
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$objArray = array();
		foreach($results as $result) {
			$rc = new ReflectionClass($className);
			$obj = $rc->newInstance();
			$obj->data = $result;
			$objArray[] = $obj;
		}
		
		// Close connection
		unset($conn);
		
		// Return array
		return $objArray;
	}
	
	private static function getStringFields($className) {
		// TODO:Get all non-numeric & non-date fields
		$tableName = self::getPluralForm($className);
		$sql = "SHOW COLUMNS FROM $tableName";
		
		// Connect to DB
		$conn = self::connect();
		
		// Prepare SQL statement
		$stmt = $conn->prepare($sql);
		
		// Execute SQL
		$stmt->execute(null);
				
		// Fetch results
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		
		$cols = array();
		foreach($results as $col) {
			if(!stristr($col['Type'],'int') && !stristr($col['Type'],'date')) {
				$cols[] = $col['Field'];
			}
		}
		return $cols;
	}
	
	/**
	 * Retrieves first matched record with the fields provided
	 * @param String $className
	 * @param Mixed $fields Associative array of column=>value
	 * @return Object of type matching class of $className, or null if no match
	 */
	public static function match($className,$fields=null) {
		$objectArr = self::find($className,null,$fields);
		if(count($objectArr) > 0) {
			return $objectArr[0];
		} else {
			return null;
		}
	}
	
	/**
	 * Adds the current object to the DB
	 * @return Success or failure of INSERT
	 */
	private function insert() {
		$tableName = self::getTableName($this);
		$sql = "INSERT INTO $tableName (";
		
		$values = array();
		$i = 0;
		foreach($this->data as $name => $value) {
			// Add a comma if this isn't the first column name in the list
			if($i > 0) {
				$fieldStr .= ',';
				$valueStr .= ',';
			}
			$fieldStr .= $name;
			$valueStr .= ' ? ';
			
			$values[] = $value;
			$i++;
		}
		
		$sql .= "$fieldStr) VALUES($valueStr)";
	
		return self::exec($sql,$values);
	}
	
	/**
	 * Updates the current object in the database
	 * @return Success or failure of UPDATE
	 */
	private function update() {
		$tableName = self::getTableName($this);
		$sql = "UPDATE $tableName SET";
		
		// Get all protected properties from object
		$properties = $this->getPropertiesFromObject();
		$i = 0;
		foreach($this->data as $name => $value) {
			// Add column=value for all columns except primary key
			if(substr($name,-3) != '_id') {
				if($i > 0) {
					$sql .= ',';
				}
				$sql .= " $name=?";
				
				$values[] = $this->data[$name];
				$i++;
			}
			
		}
		
		// Concatenate WHERE clause
		$idName = $this->getIdName();
		$sql .= " WHERE $idName=?";
		
		// Add ID to values
		$values[] = $this->data[$idName];
		
		// Execute query
		return self::exec($sql, $values);
	}
	
	/**
	 * Saves the current object to the database, using all fields
	 * of the current object. If the object's classname_id property
	 * has been set, an UPDATE query will be executed, otherwise
	 * an INSERT query
	 * @return Success or failure of UPDATE or INSERT
	 */
	public function save() {	
		// If this object is already in the DB, UPDDATE it
		if($this->inDB()) {
			return $this->update();
		} else { // Not in DB, INSERT it
			return $this->insert();
		}	
	}
	
	/**
	 * Removes the current object from the database
	 * @return Success or failure of DELETE
	 */
	public function delete() {
		// Construct SQL
		$tableName = self::getTableName($this);
		$idName = $this->getIdName();
		$sql = "DELETE FROM $tableName WHERE $idName=?";
		$values = array($this->data[$idName]);
		// Execute SQL
		return self::exec($sql,$values);
	}
	
	/**
	 * Helper method to execute all queries
	 * @param String $sql Parameterized SQL
	 * @param array $values Array of values to use for parameters
	 * @return Result set upon success, null upon failure
	 */
	public static function exec($sql,$values=null) {
		// Connect to DB
		$conn = self::connect();
		try {
			// Execute SQL
			$stmt = $conn->prepare($sql);
			$result = $stmt->execute($values);
			if($result == null) {
				trigger_error(self::sqlError($stmt),E_USER_WARNING);
			}
			
			// Fetch results
			$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			$objArray = array();
			foreach($rows as $row) {
				$results[] = $row;
			}
			$result = $results;
			
		} catch(PDOException $e) {
			trigger_error(self::pdoError($e,$sql),E_USER_WARNING);
			$result = null;
		}
		
		// Close DB
		unset($conn);
		
		return $result;
	}
	
	/**
	 * Gets all protected properties from current object
	 * @return Array[ReflectionProperty]
	 */
	private function getPropertiesFromObject() {
		$rc = new ReflectionClass($this);
		return $rc->getProperties(ReflectionProperty::IS_PROTECTED);
	}
	
	/**
	 * Generates an expression to be used in a SQL statement in the form
	 * $name=$value. This function adds quotes for non-numeric values.
	 * @param String $name
	 * @param mixed $value
	 * @return Expression in the form column=value or column='value'
	 */
	public static function getProperty($column,$value) {
		$sql = "$column=";
		
		// Quote non-numeric values
		if(!is_numeric($value)) {
			$sql .= "'$value'";
		} else {
			$sql .= "$value";
		}
		
		return $sql;
	}
	
	/**
	 * Displays the error causing a thrown PDOException, optionally displaying any
	 * SQL attempted
	 * @param PDOException $e
	 * @param String $sql
	 * @return Error message
	 */
	public static function pdoError(PDOException $e, $sql=null, $html=true) {
		if(!$html) {
			$msg = $e->getMessage();
		} else {
			$msg = '<div class=\"error sql-error\">';
			$msg .='<h3>Error Message</h3>';
			$msg .="<p>{$e->getMessage()}</p>";
			if($sql != null) {
				$msg .='<h4>SQL Attempted</h4>';
				$msg .="<p class=\"code\">{$sql}</p>";
			}
			$msg .='</div>';
		}
		
		return $msg;
	}
	
	/**
	 * Finds the table name of the specified object
	 * @param Object $object Object for which to find corresponding table name
	 * @return String table name
	 */
	private static function getTableName($object) {
		return self::getPluralForm(self::getClassName($object));
	}
	
	/**
	 * Gets the plural form defined in each class, to be used
	 * with an associated DB table. If no field is provided,
	 * the assumed value is the class name, in all lowercase, with an 's' added
	 * (e.g. Contact => contacts
	 * @param String $className
	 * @return Plural form of class, in all lowercase
	 */
	private static function getPluralForm($className=null) {
		$className = $className == null ? $this : $className;
		$rc = new ReflectionClass($className);
		try {
			$plural = $rc->getProperty('plural')->getValue();
		} catch(ReflectionException $e) {
			$plural = strtolower($className).'s';
			$msg = "<div class=\"error\">You have not defined a 'plural' field in the '$className' class. Assumed value is '$plural'</div>";
			trigger_error($msg,E_USER_NOTICE);
		}
		return $plural;
	}
	
	/**
	 * Helper function to find class name of specified object
	 * @param Object $object Object for which to find class name
	 * @return String class name
	 */
	private static function getClassName($object) {
		$rc = new ReflectionClass($object);
		return $rc->getShortName();
	}
	
	/**
	 * Checks to see whether or not the current object's id property has been set.
	 * If so, the id must have come from the DB
	 * @return True if current object is in DB, false otherwise
	 */
	private function inDB() {
		$rc = new ReflectionClass($this);
		try {
			return isset($this->data[$this->getIdName()]);
		} catch (ReflectionException $e) {
			return false;
		}
	}
	
	/**
	 * Displays the error causing a thrown PDOException, optionally displaying any
	 * SQL attempted
	 * @param PDOException $e
	 * @param String $sql
	 * @return Error message
	 */
	public static function sqlError(PDOStatement $stmt) {
		$error = $stmt->errorInfo();
		$msg = '<div class=\"error sql-error\">';
		$msg .="<p><strong>MySQL Error #{$error[1]}:</strong>{$error[2]}</p>";
		$msg .="<pre>{$stmt->queryString}</pre>";
		if($sql != null) {
			$msg .='<h4>SQL Attempted</h4>';
			$msg .="<p class=\"code\">{$sql}</p>";
		}
		$msg .='</div>';
		return $msg;
	}
	
	/**
	 * Gets the name of the current object's id column name
	 * (this will be the primary key)
	 * @return String ID column name
	 */
	private function getIdName() {
		try {
			$rc = new ReflectionClass($this);
			return strtolower($rc->getShortName()).'_id';
		} catch(ReflectionException $e) {
			$message = '<div class="error code"><pre>'.print_r($e->getTrace(),true).'</pre></div>';
			trigger_error($message,E_USER_NOTICE);
			return null;
		}
	}
}