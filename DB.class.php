<?php
/**
 * unit-db:/DB.class.php
 *
 * @created   2016-11-28
 * @version   1.0
 * @package   unit-db
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */

/**
 * db
 *
 * @created   2016-11-28
 * @version   1.0
 * @package   unit-db
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */
class db extends OnePiece
{
	/**
	 * Driver. (mysql, pgsql, sqlite)
	 *
	 * @var string
	 */
	private $_driver;

	/**
	 * PDO instance handle.
	 *
	 * @var PDO
	 */
	private $_pdo;

	/**
	 * Stack execute queries.
	 *
	 * @var array
	 */
	private $_queries;

	/**
	 * Get quote character.
	 *
	 * @return array
	 */
	private function _get_quoter()
	{
		static $lf, $rg;
		if( $lf and $rg ){
			return [$lf, $rg];
		}

		//	...
		switch( $this->_driver ){
			case 'mysql':
				$lf = $rg = '`';
				break;
			default:
				d("undefined driver. ({$this->_driver})");
				break;
		}

		//	...
		return [$lf, $rg];
	}

	/**
	 * Quote key string.
	 *
	 * @param  string $val
	 * @return string
	 */
	function Quote($val)
	{
		list($lf, $rg) = $this->_get_quoter();
		return "{$lf}{$val}{$rg}";
	}

	/**
	 * Database connection.
	 *
	 * @param  array
	 * @return boolean
	 */
	function Connect($args)
	{
		//	...
		foreach(['driver','host','database','user','password','charset'] as $key){
			if( null === ${$key} = $args[$key]){
				Notice::Set("Has not been set this key's value. ($key)");
			}
		}

		//	...
		$this->_driver = $driver;
		$dsn	 = "{$driver}:host={$host};dbname={$database}";
		$options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '{$charset}'";

		//	...
		try{
			$this->_queries[] = $dsn;
			$this->_pdo = new PDO($dsn, $user, $password, $options);
		}catch(PDOException $e){
			Notice::Set($e->getMessage());
		}

		//	...
		return $this->_pdo ? true: false;
	}

	/**
	 * Get database names.
	 *
	 * @return array
	 */
	function GetDatabase()
	{
		foreach($this->Query('SHOW DATABASES') as $record){
			$result[] = $record['Database'];
		}
		return $result;
	}

	/**
	 * Get table names.
	 *
	 * @param  string
	 * @return array
	 */
	function GetTable($database)
	{
		$result = [];
		$_database = $this->Quote($database);
		foreach($this->Query("SHOW TABLES FROM {$_database}") as $record){
			$result[] = $record['Tables_in_'.$database];
		}
		return $result;
	}

	function GetPDO()
	{
		return $this->_pdo;
	}

	/**
	 * Execute sql query.
	 *
	 * @param  string $query
	 * @return boolean|integer|array
	 */
	function Query($query, $type=null)
	{
		//	...
		if(!$this->_pdo){
			Notice::Set("Has not been instantiate PDO.");
			return false;
		}

		//	...
		$this->_queries[] = $query;
		if(!$statement = $this->_pdo->query($query)){
			$errorinfo = $this->_pdo->errorInfo();
			$state = $errorinfo[0];
			$errno = $errorinfo[1];
			$error = $errorinfo[2];
			Notice::Set("[$state($errno)] $error");
			return false;
		}

		//	...
		if(!$type){
			$type = substr($query, 0, strpos($query, ' '));
		}

		//	...
		switch( strtolower($type) ){
			case 'show':
			case 'select':
				$result = $statement->fetchAll(PDO::FETCH_ASSOC);
				break;

			case 'count':
				$result = $statement->fetchAll(PDO::FETCH_ASSOC);
				$result = $result[0]['COUNT(*)'];
				break;

			case 'insert':
				$result = $this->_pdo->lastInsertId(/* $name is necessary at PGSQL */);
				break;

			case 'update':
			case 'delete':
				$result = $statement->rowCount();
				break;

			case 'alter':
			case 'grant':
			case 'create':
				$result = true;
				break;

			default:
				d($type);
		}

		return $result;
	}
}