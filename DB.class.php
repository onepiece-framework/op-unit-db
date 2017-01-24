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
	 * Save connection configuration.
	 *
	 * @var string
	 */
	private $_config;

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
	 * ...
	 */
	function __sleep()
	{
		return ['_config','_queries'];
	}

	/**
	 * ...
	 */
	function __wakeup()
	{
		D("Does not implemented yet.");
	}

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
		switch( $this->_config['driver'] ){
			case 'mysql':
				$lf = $rg = '`';
				break;
			default:
				d("undefined driver. ({$this->_config['driver']})");
				break;
		}

		//	...
		return [$lf, $rg];
	}

	/**
	 * Database connection.
	 *
	 * @param  array
	 * @return boolean
	 */
	function Connect($config)
	{
		//	...
		foreach(['driver','host','database','user','password','charset'] as $key){
			if( isset($config[$key]) ){
				$this->_config[$key] = ${$key} = $config[$key];
			}else{
				Notice::Set("Has not been set this key's value. ($key)");
			}
		}

		//	...
		$dsn = "{$driver}:host={$host};dbname={$database}";
		$options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '{$charset}'";
		if( ifset( $config[PDO::MYSQL_ATTR_MULTI_STATEMENTS], true ) ){
			if( defined('PDO::MYSQL_ATTR_MULTI_STATEMENTS') ){
				$options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
			}
		}

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
	 * Quick Query Language.
	 *
	 * <pre>
	 * //	Space is required.
	 *
	 * //	Basic SELECT
	 * $value = 1;
	 * $this->Quick("TABLE.column = $value"); // Equal
	 * $this->Quick("TABLE.column > $value"); // Grater than
	 * $this->Quick("TABLE.column > " . $value - 1); // Grater than equal
	 * $this->Quick("TABLE.column != $value"); // Not equal
	 *
	 * //	Get single column
	 * $this->Quick("score <- TABLE.date < $today");
	 *
	 * //	Limit
	 * $this->Quick("score <- TABLE.date < $today", "limit=1");
	 *
	 * //	Order (default is ASC)
	 * $this->Quick("score <- TABLE.date < $today", "limit=1, order=id timestamp");
	 *
	 * //	Order (DESC)
	 * $this->Quick("score <- TABLE.date < $today", "limit=1, order=^asc desc^");
	 *
	 * //	Function
	 * $this->Quick("sum(score) <- TABLE.date < $today");
	 * </pre>
	 *
	 * @param  string $qql
	 * @return array
	 */
	function Quick($qql, $option=null)
	{
		//	...
		if(!class_exists('QQL')){
			if(!include(__DIR__.'/QQL.class.php')){
				return [];
			}
		}

		//	...
		if( $sql = QQL::Select($qql, $option, $this) ){
			return $this->Query($sql);
		}

		//	...
		return [];
	}

	/**
	 * Get PDO instance.
	 *
	 * @return PDO
	 */
	function GetPDO()
	{
		return $this->_pdo;
	}

	/**
	 * Get last query.
	 *
	 * @return string
	 */
	function GetQuery()
	{
		return $this->_queries[count($this->_queries)];
	}

	/**
	 * Get all queries.
	 *
	 * @return array
	 */
	function GetQueries()
	{
		return $this->_queries;
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
			Notice::Set("Has not been instantiate PDO.", debug_backtrace());
			return false;
		}

		//	...
		$query = trim($query);

		//	...
		$this->_queries[] = $query;
		if(!$statement = $this->_pdo->query($query)){
			$errorinfo = $this->_pdo->errorInfo();
			$state = $errorinfo[0];
			$errno = $errorinfo[1];
			$error = $errorinfo[2];
			Notice::Set("[$state($errno)] $error", debug_backtrace());
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

	/**
	 * Quote key string.
	 *
	 * @param  string $val
	 * @return string
	 */
	function Quote($str)
	{
		list($l, $r) = $this->_get_quoter();
		$str = str_replace([$l, $r], '', $str);
		return $l.trim($str).$r;
	}
}