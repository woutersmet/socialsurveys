<?php
class db {

	private $conn; //db connection
	private $queryresult; //query result
	private $query; //query

	private static $dbInstance; //instance of me (so we connect only once during a run)

        public static function mailExportFile()
        {
            Debug::log("Will create and mail an export file");
            $db = DB::getInstance();
            $backupdata = $db->createbackup();

            $filename = 'backup_' . date('Y-m-d__H-i-s', time());
            $path = 'tabignite_storage/backupfiles/' . $filename . '.txt';
            Util::createTextFile($path, $backupdata);

            Mail::sendBackupMail($path);

            //source: http://php.net/manual/en/function.header.php
            header('Content-type: application/txt');
            header('Content-Disposition: attachment; filename="socialsurvey-backup.txt"');
            readfile($path);
        }
                
        //similar to below
        public static function selectRow($tablename, $whereconditions)
        {
            Debug::log($whereconditions, "Selecting row from table $tablename with these conditions ...");
            
            $db = DB::getInstance();
            
            foreach ($whereconditions as $wherefield)
            {
                $wherearray[] = $wherefield['key'] . '={' . $wherefield['key'] . '}';
            }

            $wherestring = 'WHERE ' . implode (' AND ', $wherearray);
            
            $db->prepare("SELECT * FROM $tablename $wherestring");
            
            foreach ($whereconditions as $array)
            {
                if (isset($array['type']) && $array['type'] == 'var')
                {
                    $db->assignVar($array['key'], $array['value']);
                }
                else
                {
                    $db->assignInt($array['key'], $array['value']);
                }
            }
            
            return $db->getRow();
        }
        
        //if no whereconditions we consider it an insert.
         //keyvaluestypesarray and where array should be something like where type can be 'var' or 'int' or 'raw'
        // array(array('key' => xxx, 'value' => xx, 'type' => 'int');
        // if type is 'NOW' we will use sql function NOW()
        //when type is left out a var is assumed for the fields and an int for the where fields
	public static function updateOrInsert($tablename,$keyvaluestypesArray,$whereconditions = false)
	{
            Debug::log($keyvaluestypesArray, "Processing row in  $tablename with these values insert or update? " . ($whereconditions ? 'UPDATE' : 'INSERT'));
            $isUpdate = $whereconditions !== false;
            
            $db = DB::getInstance();
            
            $values = array();

            foreach($keyvaluestypesArray as $field)
            {
                if ($field['type'] == 'function')
                {
                    $values[] = $field['key'] . '=' . mysql_real_escape_string($field['value']) . '()';
                }
                else
                {
                    $values[] = $field['key'] .'={' . $field['key'] . '}';
                }
            }
            $valuestring = implode(",\n", $values);

            if ($isUpdate)
            {
                foreach ($whereconditions as $wherefield)
                {
                    $wherearray[] = $wherefield['key'] . '={' . $wherefield['key'] . '}';
                }

                $wherestring = 'WHERE ' . implode (' AND ', $wherearray);
                $command = 'UPDATE ';
            }
            else
            {
                $wherestring = '';
                $command = 'INSERT INTO ';
            }


            $db->prepare("$command $tablename SET $valuestring $wherestring");

            //assign fields, assuming 'var' by default
            foreach ($keyvaluestypesArray as $array)
            {
                if (isset($array['type']) && $array['type'] == 'int')
                {
                    $db->assignInt($array['key'], $array['value']);
                }
                elseif (isset($array['type']) && $array['type'] == 'raw')
                {
                    $db->assignRaw($array['key'], $array['value']);
                }
                else
                {
                    $db->assignVar($array['key'], $array['value']);
                }
            }

            //assign where conditions, assuming 'int' by default
            if ($isUpdate)
            {
                foreach ($whereconditions as $array)
                {
                    if (isset($array['type']) && $array['type'] == 'var')
                    {
                        $db->assignVar($array['key'], $array['value']);
                    }
                    elseif (isset($array['type']) && $array['type'] == 'raw')
                    {
                        $db->assignRaw($array['key'], $array['value']);
                    }
                    else
                    {
                        $db->assignInt($array['key'], $array['value']);
                    }
                }
            }

            if ($isUpdate)
            {
                return $db->getAffectedRows();
            }
            else
            {
                $insertid = $db->getInsertId();
                
                //die(var_dump($insertid));
                if ($insertid === 0)
                {
                    Debug::log("Insert id is 0 meaning it was no auto-increment stuff but query was executed!");
                    return true;
                }
                else
                {
                    //will either be a true insert id or FALSE 
                    return $insertid;
                }
            }
	}        
        
	public function __construct()
	{

            $this->connect();
	}
        
        public static function enableLocalDbMode()
        {
            Debug::log("Enabling local db mode!");
            Cookie::set('localdbmode','hello');
        }
        
        public static function inLocalDbMode()
        {
            return Cookie::get('localdbmode');
        }
        
        public static function disableLocalDbMode()
        {
            Debug::log("Disnabling local db mode!");
            Cookie::delete('localdbmode');
        }


	public static function getInstance()
	{
            if (!self::$dbInstance)
            {
                    self::$dbInstance = new DB();
            }

            return self::$dbInstance;
	}

	/**
	 * Connect to the MySQL database
	 */
	 function connect()
	{
            Debug::log("Connecting to db...");
            
            //$credentialsToGet = Util::isLocalEnvironment() ? 'local' : 'online';
            //possible options: local, online, onlinedev (see configuration.inc.php)
            if (Util::isLocalEnvironment())
            {
                    $credentialsToGet = 'local';
            }
            else{
                $credentialsToGet = 'online';
            }
            
            $creds = Configuration::get('database', $credentialsToGet);
            
            define('CLIENT_LONG_PASSWORD', 1);
            $this->conn = @mysql_connect($creds['host'], $creds['user'], $creds['pass'],false, CLIENT_LONG_PASSWORD);

            if(!$this->conn)
            {
                Debug::error("could not connect to DB: ".mysql_error());
                if (Util::isLocalEnvironment())
                {
                    die("Whoops you killed the site database :-(  <br />Verify that you are online, MySQL is running and your credentials are correct. Credentials used: <pre>".print_r($creds,true)."</pre> MySQL ERROR: " . mysql_error());
                }
            }

            if (!mysql_select_db($creds['database'],$this->conn))
            {
                Debug::error("Something went wrong selecting database named " . $creds['database']);
            }
	}

	//escape a var
	public static function escape($var)
	{
		return mysql_real_escape_string($var);
	}

	/**
	 * prepare query
	 *
	 */
	public function prepare($query)
	{
		$this->query = $query;
	}

	public function sanitize($string)
	{
		return mysql_real_escape_string($string);
	}

	public function assignVar($string_in_query,$replacement)
	{
            $replacement = $this->sanitize($replacement);
            $newquery = str_replace('{'.$string_in_query.'}','\''.$replacement.'\'',$this->query);

            //worst js injection prevention ever:
            //$newquery = str_replace('script', 'LAZY', $newquery);
            $this->query = $newquery;
	}

	public function assignRaw($string_in_query,$replacement)
	{
            $newquery =str_replace('{'.$string_in_query.'}',$replacement,$this->query);
            $this->query = $newquery;
	}

	public function assignInt($string_in_query,$replacement)
	{
            $replacement = (int) $this->sanitize($replacement);
            $newquery =str_replace('{'.$string_in_query.'}',$replacement,$this->query);
            $this->query = $newquery;
	}

	public function assignIntArray($string_in_query,$replacementArray)
	{
            $replacement = $this->sanitize(implode(',',$replacementArray));
            
            $newquery = str_replace('{'.$string_in_query.'}',$replacement,$this->query);
            
            $this->query = $newquery;
	}
        
	public function getQuery()
	{
		return $this->query;
	}

	public function execute()
	{
		debug::logQuery('Executing query '.$this->query);

		$beforetime = microtime(true); //for logging time it took query
		$result = mysql_query($this->query);

		$aftertime = microtime(true); //for logging time it took query
		$querytime = ($aftertime - $beforetime) * 1000;
		if(!$result)
		{

			Debug::error("ERROR" . mysql_error() . ' query:  ' . $this->query);
			debug::error('Failed executing query '.$this->query.' because: '.mysql_error(),'ERROR');
		}
		debug::log('Did DB query and it took took '.round($querytime,5).'ms');
		return $result;
	}

	//return results in an array and close db connection
	public function getResults($useCache = false, $expiryTime = false)
	{
        $useCache = $useCache && Configuration::get('general', 'useCache');
        if ($useCache)
		{
			Debug::alert("Query with cache!");
			Debug::log("Checking if there's a valid result in cache...");
			$this->cacheObject = new Cache();
			$cacheResult = $this->cacheObject->get($this->query, $expiryTime);
			if ($cacheResult)
			{
				Debug::log("Found cached result for this query!");
				Debug::logQueryWithCache();
				return $cacheResult;
			}
			else
			{
				Debug::alert("No cached result for this query (yet). Proceeding with regular db call...");
			}
		}

		Debug::alert("Query without cache!");
		Debug::logQueryWithoutCache();
		$this->queryresult = $this->execute();
		$results = Array();
		if(mysql_num_rows($this->queryresult) > 0)
		{
			while($row = mysql_fetch_assoc($this->queryresult))
			{
				//unescape field contents
				foreach($row as $key=>$value)
				{
					$row[$key] = stripslashes($value);
				}
				$results[] = $row;
			}
			Debug::log($results,'queryresult');

			if ($useCache)
			{
				Debug::log("Storing results in cache...");
				$this->cacheObject->set($this->query, $results);
			}

			return $results;
		}
		else
		{
			debug::log($results,'queryresult');
			return false;
		}
	}

	public function clearCache()
	{
		Debug::log("Clearing cache of current query ...");
		$c = new Cache();
		return $c->clear($this->query);
	}

	//return first row of results
	public function getRow($useCache = false, $expiryTime = false)
	{
		$useCache = $useCache && Configuration::get('general', 'useCache');

		if ($useCache)
		{
			Debug::alert("Query with cache!");
			Debug::log("Checking if there's a valid result in cache...");
			$this->cacheObject = new Cache();
			$cacheResult = $this->cacheObject->get($this->query, $expiryTime);
			if ($cacheResult)
			{
				Debug::log("Found cached result for this query!");
				Debug::logQueryWithCache();
				return $cacheResult;
			}
			else
			{
				Debug::alert("No cached result for this query (yet). Proceeding with regular db call...");
			}
		}

		Debug::alert("Query without cache!");
		Debug::logQueryWithoutCache();

		$this->queryresult = $this->execute();

		if(mysql_num_rows($this->queryresult) > 0)
		{
			$row = mysql_fetch_assoc($this->queryresult);
			debug::log($row,'queryresult');
			foreach($row as $key=>$value)
			{
				$row[$key] = stripslashes($value);
			}

			if ($useCache)
			{
				Debug::log("Storing result in cache...");
				$this->cacheObject->set($this->query, $row);
			}

			return $row;
		}
		else
		{
			return false;
		}
	}

	public function getNumRows()
	{
            $this->queryresult = $this->execute();
            $numrows = mysql_num_rows($this->queryresult);
            debug::log($numrows,'queryresult');
            return $numrows;
	}

	public function getInsertId()
	{
            $this->queryresult = $this->execute();
            $insertId = mysql_insert_id();
            debug::log($insertId,'queryresult');
            return $insertId;
	}
        
        public function getAffectedRows()
        {
		$this->queryresult = $this->execute();
		$affectedRows = mysql_affected_rows();
		debug::log('Affected rows: ' . $affectedRows);
		return $affectedRows;    
        }

	//insert values from $keyvaluesArray (field=>value) in table $tablename
	public function insert($tablename,$keyvaluesArray)
	{

		$values = Array();
		foreach($keyvaluesArray as $key=>$value)
		{
			if(is_numeric($value))
			{
				$valueS = $value;
			}
			else
			{
				$valueS = "'".mysql_real_escape_string($value)."'";
			}
			$values[] = $key.'='.$valueS;
		}

		$this->prepare("INSERT INTO $tablename SET ".implode(',',$values));
		return $this->execute();
		//return $this->query;
	}

	public function update($tablename,$keyvaluesArray,$whereString)
	{
		$values = array();
		foreach($keyvaluesArray as $key=>$value)
		{
			$valueS = is_numeric($value)? $value : "'".$value."'";
			$values[] = $key.'='.$valueS;
		}
		$this->prepare("UPDATE {table} SET {values} WHERE {whereString}");
		$this->assignInt('whereString',$whereString);
		$this->assignVar('values',implode(',',$values));
		$this->assignInt('table',$tablename);

		return $this->execute();
	}

	public function select($tablename,$valuesarray,$whereString)
	{
		$this->prepare("SELECT {values} FROM {table} WHERE {whereString}");
		$this->assignInt('whereString',$whereString);
		$this->assignVar('values',implode(',',$valuesarray));
		$this->assignInt('table',$tablename);

		return $this->getResults();
	}

	public function delete($tablename,$whereString)
	{
		$this->prepare("DELETE FROM {table} WHERE {whereString}");
		$this->assignInt('whereString',$whereString);
		$this->assignInt('table',$tablename);

		return $this->execute();
	}
        
                /* backup the db OR just a table */
        //source: http://davidwalsh.name/backup-mysql-database-php
        public function createbackup($tables = '*')
        {
            Debug::log("Creating db backup (export) ...");
          
            $return = '';
              //get all of the tables
              if($tables == '*')
              {
                  Debug::log("Getting tables...<Br />");
                $tables = array();
                $result = mysql_query('SHOW TABLES');
                while($row = mysql_fetch_row($result))
                {
                  $tables[] = $row[0];
                }
              }
              else
              {
                $tables = is_array($tables) ? $tables : explode(',',$tables);
              }

          //cycle through
          foreach($tables as $table)
          {
              Debug::log("Exporting table $table...");
              
            $result = mysql_query('SELECT * FROM '.$table);
            $num_fields = mysql_num_fields($result);

            $return.= 'DROP TABLE '.$table.';';
            $row2 = mysql_fetch_row(mysql_query('SHOW CREATE TABLE '.$table));
            $return.= "\n\n".$row2[1].";\n\n";
            for ($i = 0; $i < $num_fields; $i++) 
            {
              while($row = mysql_fetch_row($result))
              {
                $return.= 'INSERT INTO '.$table.' VALUES(';
                for($j=0; $j<$num_fields; $j++) 
                {
                  $row[$j] = addslashes($row[$j]);
                  $row[$j] = ereg_replace("\n","\\n",$row[$j]);
                  if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                  if ($j<($num_fields-1)) { $return.= ','; }
                }
                
                $return.= ");\n";
              }
            }
            $return.="\n\n\n";
          }
          
          Debug::log($return, "result");
          return $return;
        }
}
	?>