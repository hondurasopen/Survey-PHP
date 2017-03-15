<?php

	/*
		Copyright (c) 2010, J. Palencia (ciachn@gmail.com)
		All rights reserved.

		THIS SOFTWARE IS PROVIDED BY <COPYRIGHT HOLDER> ''AS IS'' AND ANY
		EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
		WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
		DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
		DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
		(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
		LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
		ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
		(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
		SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	*/

	/**
	**	@prot:	class DataConn
	**
	**	@desc:	Provides a clean interface between the client and a database server,
	**			this class can be inherited with its methods overridden to support
	**			a different server other than MySQL.
	*/

	class DataConn
	{
		/**
		**	@prot:	public static $Connection;
		**
		**	@desc:	The global DataConn connection, this will be set when a connection
		**			is made. Other entities can have access to an already open connection
		**			by reading this attribute.
		*/

		public static $Connection = null;

		/**
		**	@prot:	public $conn;
		**
		**	@desc:	The connection resource used by this interface. This attribute
		**			will be set after a call to connect().
		*/

		public $conn = null;

		/**
		**	@prot:	private $server;
		**
		**	@desc:	Contains the host name or IP address of the server.
		*/

		private $server = null;

		/**
		**	@prot:	private $currentTable;
		**
		**	@desc:	Contains the name of the current table. This is used when specific
		**			table methods are invoked, such as insert(), select(), etc.
		*/

		private $currentTable = null;

		/**
		**	@prot:	private $currentDatabase;
		**
		**	@desc:	Contains the name of the current database. This is used when specific
		**			database methods are invoked, such as tables().
		*/

		private $currentDatabase = null;

		/**
		**	@prot:	public function __construct ($dbserver, $dbuser, $dbpass, $dbname)
		**
		**	@desc:	Constructor of an instance of this class, connects to the database 
		**			server using the given connection configuration, and throws an
		**			exception if an error occurrs. If any of the parameters is null,
		**			they will be taken from the configuration options.
		*/

		public function __construct ($dbserver=null, $dbuser=null, $dbpass=null, $dbname=null)
		{
			/* Load options if not specified. */
			if (!$dbserver) $dbserver = Config::get ("DataConn/Server");
			if (!$dbuser) $dbuser = Config::get ("DataConn/User");
			if (!$dbpass) $dbpass = Config::get ("DataConn/Password");
			if (!$dbname) $dbname = Config::get ("DataConn/Database");

			/* Try to connect to the server. */
			$this->conn = @mysql_connect ($this->server = $dbserver, $dbuser, $dbpass);

			/* If failed, return an exception. */
			if (!$this->conn) throw new Exception ("Unable to connect to $dbuser@$dbserver.");

			/* Now select the database (if specified). */
			if ($dbname != null) $this->database ($dbname);

			/* Set the global connection. */
			DataConn::$Connection = $this;
		}

		/**
		**	@prot:	public function __destruct ()
		**
		**	@desc:	Invoked upon destruction of the instance, if the connection
		**			is active it will be shutdown.
		*/

		public function __destruct ()
		{
			/* Close the connection if it is active. */
			if ($this->conn != null) mysql_close ($this->conn);
		}

		/**
		**	@prot:	public function server ()
		**
		**	@desc:	Returns the host name or ip address of the server in use.
		*/

		public function server ()
		{
			return $this->server;
		}

		/**
		**	@prot:	public function execQuery ($queryString, $getFields=false)
		**
		**	@desc:	Executes a query on the database server, returns a table, which is,
		**			an array of arrays, and the sub-arrays are the table-rows. Throws
		**			exceptions on errors.
		*/

		public function execQuery ($queryString, $getFields=false)
		{
			/* First check if the connection if valid. */
			if ($this->conn == null) throw new Exception ("Database server connection not established.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Execute the query, throw exception if error. */
			$result = mysql_query ($queryString);

			/* Throw exception if the result has an error. */
			if (!$result) throw new Exception (mysql_error ());

			/* Get the number of rows, and allocate the new table. */
			$num = mysql_num_rows ($result); $num_fields = mysql_num_fields ($result);
			$table = array ();

			if ($getFields)
			{	
				/* The first row contains the names of all the retrieved fields. */
				$table[] = array ();

				/* Fetch the field names. */
				for ($i = 0; $i < $num_fields; $i++) $table[0][$i] = mysql_field_name ($result, $i);
			}

			/* Fetch the contents from the result resource. */
			while ($num--) 
			{
				$row = mysql_fetch_row ($result);
				$table[] = $row;
				//[stored_w_slashes] foreach ($row as $key => &$value) $value = stripslashes ($value);
			}

			/* Release the result resource. */
			mysql_free_result ($result);

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($queryString);

			/* Return the table. */
			return $table;
		}

		/**
		**	@prot:	public function generatedId ()
		**
		**	@desc:	Returns the id that was generated on the list insertion query.
		*/

		public function generatedId ()
		{
			/* First check if the connection if valid. */
			if ($this->conn == null) throw new Exception ("Database server connection not established.");

			return mysql_insert_id ($this->conn);
		}

		/**
		**	@prot:	public function execScalar ($queryString)
		**
		**	@desc:	Executes a query on the database server, returns an scalar value.
		*/

		public function execScalar ($queryString)
		{
			/* First check if the connection if valid. */
			if ($this->conn == null) throw new Exception ("Database server connection not established.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Execute the query, throw exception if error. */
			$result = mysql_query ($queryString);

			/* Throw exception if the result has an error. */
			if (!$result) throw new Exception (mysql_error ());

			/* Get the result row. */
			$row = mysql_fetch_row ($result);
			//[stored_w_slashes] foreach ($row as $key => &$value) $value = stripslashes ($value);

			/* Release the result resource. */
			mysql_free_result ($result);

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($queryString);

			/* Return the scalar value. */
			return empty ($row) ? false : $row[0];
		}

		/**
		**	@prot:	public function execArray ($queryString, $justFields=false)
		**
		**	@desc:	Executes a query on the database server, returns one row as an array.
		*/

		public function execArray ($queryString, $justFields=false)
		{
			/* First check if the connection if valid. */
			if ($this->conn == null) throw new Exception ("Database server connection not established.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Execute the query, throw exception if error. */
			$result = mysql_query ($queryString);

			/* Throw exception if the result has an error. */
			if (!$result) throw new Exception (mysql_error ());

			/* Get the result row. */
			$row = mysql_fetch_row ($result);
			//[stored_w_slashes] foreach ($row as $key => &$value) $value = stripslashes ($value);

			/* Release the result resource. */
			mysql_free_result ($result);

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($queryString);

			/* Return the scalar value. */
			return $row;
		}

		/**
		**	@prot:	public function execObject ($queryString, $justFields=false)
		**
		**	@desc:	Executes a query on the database server, returns one row as an object.
		*/

		public function execObject ($queryString, $justFields=false)
		{
			/* First check if the connection if valid. */
			if ($this->conn == null) throw new Exception ("Database server connection not established.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Execute the query, throw exception if error. */
			$result = mysql_query ($queryString);

			/* Throw exception if the result has an error. */
			if (!$result) throw new Exception (mysql_error ());

			/* Get the result row. */
			$row = mysql_fetch_assoc ($result);
			//[stored_w_slashes] foreach ($row as $key => &$value) $value = stripslashes ($value);

			/* Release the result resource. */
			mysql_free_result ($result);

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($queryString);

			/* Return the scalar value. */
			return $row;
		}

		/**
		**	@prot:	public function execNonQuery ($queryString)
		**
		**	@desc:	Executes a non-query (statement) on the database server. Does not
		**			return anything, but will throw exceptions if an error occurrs.
		*/

		public function execNonQuery ($queryString)
		{
			/* First check if the connection if valid. */
			if ($this->conn == null) throw new Exception ("Database server connection not established.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Execute the query, throw exception if error. */
			if (!mysql_query ($queryString)) throw new Exception (mysql_error ());

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($queryString);
		}

		/**
		**	@prot:	public function execSql ($sqlFile)
		**
		**	@desc:	Loads the given SQL file and executes the queries stored there, each
		**			query must be separated by a semi-colon.
		*/

		public function execSql ($sqlFile)
		{
			foreach (explode (";", file_get_contents ($sqlFile)) as $query)
			{
				$query = trim ($query);
				if (!$query) continue;

				$this->execNonQuery ($query);
			}
		}

		/**
		**	@prot:	public function databases ()
		**
		**	@desc:	Returns a list of the databases defined in the server. If an 
		**			error occurrs, an exception will be throw.
		*/

		public function databases ()
		{
			/* First check if the connection if valid. */
			if ($this->conn == null) throw new Exception ("Database server connection not established.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Execute the query, throw exception if error. */
			$result = mysql_query ($queryString = "SHOW DATABASES");

			/* Throw exception if the result has an error. */
			if (!$result) throw new Exception (mysql_error ());

			/* Get the number of rows, and allocate the new table. */
			$num = mysql_num_rows ($result);
			$table = array ();

			/* Fetch the contents from the result resource. */
			while ($num--) { $row = mysql_fetch_row ($result); $table[] = $row[0]; }

			/* Release the result resource. */
			mysql_free_result ($result);

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($queryString);

			/* Return the table. */
			return $table;
		}

		/**
		**	@prot:	public function database ($databaseName, $verbose=false)
		**
		**	@desc:	Tries to select the database with the given name, on errors an exception
		**			will be thrown. If verbose is set and an exception is thrown it will
		**			contain error information sent by the server.
		*/

		public function database ($databaseName, $verbose=false)
		{
			/* First check if the connection if valid. */
			if ($this->conn == null) throw new Exception ("Database server connection not established.");

			if (!mysql_select_db ($databaseName, $this->conn))
				throw new Exception ($verbose ? mysql_error () : "Unable to select database '$databaseName'.");

			$this->currentDatabase = $databaseName;

			return $this;
		}

		/**
		**	@prot:	public function table ($tableName)
		**
		**	@desc:	Selects a table for subsequent calls to the table-related methods.
		*/

		public function table ($tableName)
		{
			/* First check if the connection if valid. */
			if ($this->conn == null) throw new Exception ("Database server connection not established.");

			/* Check if the user selected a table. */
			if ($this->currentDatabase == null) throw new Exception ("Database not selected yet.");

			/* Select the wanted table. */
			$this->currentTable = $tableName;

			/* Returns the object itself. */
			return $this;
		}

		/**
		**	@prot:	public function tables ()
		**
		**	@desc:	Returns a list of the tables defined in the current database. If an 
		**			error occurrs, an exception will be throw.
		*/

		public function tables ()
		{
			/* Check if the user selected a table. */
			if ($this->currentDatabase == null) throw new Exception ("Database not selected yet.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Execute the query, throw exception if error. */
			$result = mysql_query ($queryString = "SHOW TABLES");

			/* Throw exception if the result has an error. */
			if (!$result) throw new Exception (mysql_error ());

			/* Get the number of rows, and allocate the new table. */
			$num = mysql_num_rows ($result);
			$table = array ();

			/* Fetch the contents from the result resource. */
			while ($num--) { $row = mysql_fetch_row ($result); $table[] = $row[0]; }

			/* Release the result resource. */
			mysql_free_result ($result);

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($queryString);

			/* Return the table. */
			return $table;
		}

		/**
		**	@prot:	public function __get ($tableName)
		**
		**	@desc:	Using an undefined attribute with this class is similar to calling
		**			table() with the given parameter. This property reader will do so.
		*/

		public function __get ($tableName)
		{
			return $this->table ($tableName);
		}

		/* ==== [Table Specific Methods] ==== */

		/**
		**	@prot:	public function insertColumn ($columnName, $columnType)
		**
		**	@desc:	Inserts a column on the current table. In an error occurrs
		**			an exception will be thrown.
		*/

		public function insertColumn ($columnName, $columnType)
		{
			/* Check if the user selected a table. */
			if ($this->currentTable == null) throw new Exception ("Table not selected yet.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Execute the query, throw exception if error. */
			if (!mysql_query ($queryString = "ALTER TABLE {$this->currentTable} ADD $columnName $columnType"))
				throw new Exception (mysql_error ());

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($queryString);

			/* Returns the object itself. */
			return $this;
		}

		/**
		**	@prot:	public function removeColumn ($columnName)
		**
		**	@desc:	Removes a column from the current table. If an error occurrs
		**			an exception will be thrown.
		*/

		public function removeColumn ($columnName)
		{
			/* Check if the user selected a table. */
			if ($this->currentTable == null) throw new Exception ("Table not selected yet.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Execute the query, throw exception if error. */
			if (!mysql_query ($queryString = "ALTER TABLE {$this->currentTable} DROP $columnName"))
				throw new Exception (mysql_error ());

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($queryString);

			/* Returns the object itself. */
			return $this;
		}

		/**
		**	@prot:	public function changeColumn ($columnName, $columnType)
		**
		**	@desc:	Changes a column from the current table. If an error occurrs
		**			an exception will be thrown.
		*/

		public function changeColumn ($columnName, $columnType)
		{
			/* Check if the user selected a table. */
			if ($this->currentTable == null) throw new Exception ("Table not selected yet.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Execute the query, throw exception if error. */
			if (!mysql_query ($queryString = "ALTER TABLE {$this->currentTable} MODIFY $columnName $columnType"))
				throw new Exception (mysql_error ());

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($queryString);

			/* Returns the object itself. */
			return $this;
		}

		/**
		**	@prot:	public function insert ($values, $fields)
		**
		**	@desc:	Inserts a row in the current table, exact null values in the values
		**			will be converted to SQL NULL, strings will be single-quoted and
		**			escaped, and integers will be written normally.
		*/

		public function insert ($values, $fields=null)
		{
			/* Check if the user selected a table. */
			if ($this->currentTable == null) throw new Exception ("Table not selected yet.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Build the query for the insertion. */
			$query = "INSERT INTO {$this->currentTable}";
			$query .= ($fields ? "(" . implode ($fields, ",") . ")" : "") . " VALUES(";

			/* Add the values, convert each one to its corresponding element. */
			foreach ($values as $value)
			{
				if (is_string ($value))
				{
					$query .= "'" . addslashes ($value) . "',";
				}
				else if ($value === null)
				{
					$query .= "NULL,";
				}
				else 
				{
					$query .= $value . ',';
				}
			}

			/* Execute the query, throw exception if error. */
			if (!mysql_query (substr ($query, 0, -1) . ")")) throw new Exception (mysql_error ());

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($query);

			/* Returns the object itself. */
			return $this;
		}

		/**
		**	@prot:	public function delete ($condition)
		**
		**	@desc:	Deletes rows from the current table that match the given condition, the
		**			condition is in SQL format (without the WHERE). If any error occurrs
		**			an exception will be thrown.
		*/

		public function delete ($condition)
		{
			/* Check if the user selected a table. */
			if ($this->currentTable == null) throw new Exception ("Table not selected yet.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Append "WHERE" if condition is set. */
			if ($condition) $condition = "WHERE $condition";

			/* Execute the query, throw exception if error. */
			if (!mysql_query ($queryString = "DELETE FROM {$this->currentTable} {$condition}")) 
				throw new Exception (mysql_error ());

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($queryString);

			/* Returns the object itself. */
			return $this;
		}

		/**
		**	@prot:	public function update ($condition, $values)
		**
		**	@desc:	Updates with new values all the rows in the current table that match
		**			the given condition. If any error occurrs an exception is thrown.
		**			The condition is in SQL format (without the WHERE), and the values 
		**			parameter is an associative array, the key is the field name.
		*/

		public function update ($condition, $values)
		{
			/* Check if the user selected a table. */
			if ($this->currentTable == null) throw new Exception ("Table not selected yet.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Build the query for the update. */
			$query = "UPDATE {$this->currentTable} SET";

			/* Add the new values. */
			foreach ($values as $field => $value)
			{
				$query .= " $field=";

				if (is_string ($value))
				{
					$query .= "'" . addslashes ($value) . "',";
				}
				else if ($value === null)
				{
					$query .= "NULL,";
				}
				else 
				{
					$query .= $value . ',';
				}
			}

			/* Append "WHERE" if condition is set. */
			if ($condition) $condition = "WHERE $condition";

			/* Execute the query, throw exception if error. */
			if (!mysql_query (substr ($query, 0, -1) . " $condition")) throw new Exception (mysql_error ());

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($query);

			/* Returns the object itself. */
			return $this;
		}

		/**
		**	@prot:	public function select ($fields, $condition, $rules)
		**
		**	@desc:	Selects the group of rows from the current table that match the given
		**			condition. The rules string specifies the ordering and grouping information.
		**			The fields array indicates which fields to fetch. The condition is in 
		**			SQL format (without the WHERE). If any error occurrs an exception will 
		**			be throw. Return an array of arrays, and each sub array is a row.
		*/

		public function select ($fields="*", $condition=null, $rules=null, $wfields=false)
		{
			/* Check if the user selected a table. */
			if ($this->currentTable == null) throw new Exception ("Table not selected yet.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* If fields is an array, convert to string. */
			if (is_array ($fields)) $fields = implode ($fields, ",");

			/* Append "WHERE" if condition is set. */
			if ($condition) $condition = "WHERE $condition";

			/* Build the query for the update. */
			$query = "SELECT {$fields} FROM {$this->currentTable} {$condition} {$rules}";

			/* Execute the query, throw exception if error. */
			$result = mysql_query ($query);

			/* Throw exception if the result has an error. */
			if (!$result) throw new Exception (mysql_error ());

			/* Get the number of rows, and allocate the new table. */
			$num = mysql_num_rows ($result); $num_fields = mysql_num_fields ($result);
			$table = array ();

			/* Retrieve fields if specified. */
			if ($wfields)
			{
				/* The first row contains the names of all the retrieved fields. */
				$table[] = array ();

				/* Fetch the field names. */
				for ($i = 0; $i < $num_fields; $i++) $table[0][$i] = mysql_field_name ($result, $i);
			}

			/* Fetch the contents from the result resource. */
			while ($num--) 
			{
				$row = mysql_fetch_row ($result);
				$table[] = $row;

				//[stored_w_slashes] foreach ($row as $key => &$value) $value = stripslashes ($value);
			}

			/* Release the result resource. */
			mysql_free_result ($result);

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($query);

			/* Return the table. */
			return $table;
		}

		/**
		**	@prot:	public function columns ()
		**
		**	@desc:	Describes the table and return an array containing the columns.
		**			If an error occurrs an exception will be throw.
		*/

		public function columns ()
		{
			/* Check if the user selected a table. */
			if ($this->currentTable == null) throw new Exception ("Table not selected yet.");

			/* Initializes logging information. */
			global $_DEBUGGING; if ($_DEBUGGING) $this->begin_logging ();

			/* Execute the query, throw exception if error. */
			$result = mysql_query ($queryString = "DESCRIBE {$this->currentTable}");

			/* Throw exception if the result has an error. */
			if (!$result) throw new Exception (mysql_error ());

			/* Get the number of rows, and allocate the new table. */
			$num = mysql_num_rows ($result);
			$table = array ();

			/* Fetch the contents from the result resource. */
			while ($num--) $table[] = mysql_fetch_row ($result);

			/* Release the result resource. */
			mysql_free_result ($result);

			/* Finishes logging. */
			if ($_DEBUGGING) $this->end_logging ($queryString);

			/* Return the table. */
			return $table;
		}
		
		/*
		**  @prot:  public static function buildSqlFilter ($data, $ops=null)
		**
		**  @desc:  Builds an SQL filter using the given associative array, the key
		**			of the array will become the field name.
		*/

		public static function buildSqlFilter ($data, $ops=null, $fields=null)
		{
			$cond = "";

			if ($fields == null) $fields = array_keys ($data);

			foreach ($fields as $field_k => $field_v)
			{
				if (is_int ($field_k))
					$field = $field_n = $field_v;
				else
				{
					$field = $field_k; $field_n = $field_v;
				}

				if (!isset ($data[$field])) continue;
				$value = $data[$field];

				if (Validate::is_empty ($value)) continue;

				if ($ops && $ops[$field]) $op = $ops[$field]; else $op = "$(A)=$(B)";

				if (is_array ($value))
				{
					$scond = "";

					foreach ($value as $val)
					{
						if (is_string ($val))
						{
							if ($scond) $scond .= " OR ";
							$val = "'" . addslashes ($val) . "'";
							$scond .= str_replace ("$(A)", $field_n, str_replace ("$(B)", $val, $op));
						}
						else if ($val === null)
						{
							if ($scond) $scond .= " OR ";
							$scond .= $field_n . " IS NULL";
						}
						else 
						{
							if ($scond) $scond .= " OR ";
							$scond .= str_replace ("$(A)", $field_n, str_replace ("$(B)", $val, $op));
						}
					}

					if ($scond)
					{
						if ($cond) $cond .= " AND ";
						$cond .= "(" . $scond . ")";
					}
				}
				else
				{
					if (is_string ($value))
					{
						if ($cond) $cond .= " AND ";
						$value = "'" . addslashes ($value) . "'";
						$cond .= str_replace ("$(A)", $field_n, str_replace ("$(B)", $value, $op));
					}
					else if ($value === null)
					{
						if ($cond) $cond .= " AND ";
						$cond .= $field_n . " IS NULL";
					}
					else 
					{
						if ($cond) $cond .= " AND ";
						$cond .= str_replace ("$(A)", $field_n, str_replace ("$(B)", $value, $op));
					}
				}
			}

			return $cond;
		}

		/*
		**  @prot:  public function begin_logging ()
		**
		**  @desc:  Simply initializes logging information. This can be changed to
		**			store the initial time of the query. This function requires
		**			to be implemented according to your needs.
		*/

		public function begin_logging ()
		{
		}

		/*
		**  @prot:  public function end_logging ($query)
		**
		**  @desc:  Finishes logging, stores execution time information and the
		**			given query. This functions requires to be implemented according
		**			to your needs.
		*/

		public function end_logging ($query)
		{
		}
	};

?>
