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
	 * PDO instance handle.
	 *
	 * @var PDOStatement
	 */
	private $_dbh;

	/**
	 * Database connection.
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
		$dsn	 = "{$driver}:host={$host};dbname={$database}";
		$options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES '{$charset}'";

		//	...
		try{
			$this->_dbh = new PDO($dsn, $user, $password, $options);
		}catch(PDOException $e){
			Notice::Set($e->getMessage());
		}

		return $this->_dbh ? true: false;
	}
}