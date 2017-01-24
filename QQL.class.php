<?php
/**
 * unit-db:/QQL.class.php
 *
 * @created   2017-01-24
 * @version   1.0
 * @package   unit-db
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */

/**
 * QQL
 *
 * @created   2017-01-24
 * @version   1.0
 * @package   unit-db
 * @author    Tomoaki Nagahara <tomoaki.nagahara@gmail.com>
 * @copyright Tomoaki Nagahara All right reserved.
 */
class QQL extends OnePiece
{
	/**
	 * Convert to SQL from QQL.
	 *
	 * @param  string $qql
	 * @param  db     $db
	 * @return string $sql
	 */
	static function Select($qql, $opt, $_db)
	{
		$field = '*';
		$db    = '';
		$table = '';
		$where = '';
		$limit = '';
		$order = '';
		$offset = '';

		//	field
		if( $pos = strpos($qql, '<-') ){
			list($field, $qql) = explode('<-', $qql);
			$field = $_db->Quote($field);
		}

		//	...
		if( $pos = strrpos($qql, ' = ') ){
		}else if( $pos = strrpos($qql, '>') ){
		}else if( $pos = strrpos($qql, '<') ){
		}else if( $pos = strrpos($qql, '>=') ){
		}else if( $pos = strrpos($qql, '<=') ){
		}else if( $pos = strrpos($qql, '!=') ){
		}else{    $pos = false; }

		//	QQL --> database.table, value
		if( $pos === false ){
			$db_table = trim($qql);
		}else{
			$where    = 1;
			$db_table = trim(substr($qql, 0, $pos));
			$evalu    = trim(substr($qql, $pos, 2));
			$value    = trim(substr($qql, $pos +2));
		}

		//	database.table --> database, table
		$pos = strpos($db_table, '.');
		if( $pos === false ){
			$table = $db_table;
		}else{
			$temp = explode('.', $db_table);
			if( $where ){
				switch( count($temp) ){
					case 2:
						$table = $temp[0];
						$which = $temp[1];
						break;
					case 3:
						$db    = $temp[0];
						$table = $temp[1];
						$which = $temp[2];
						break;
					default:
						d($temp);
				}
				$which = $_db->Quote($which);
				$value = $_db->GetPDO()->quote($value);
				$where = "WHERE {$which} {$evalu} {$value}";
			}else{
				switch( count($temp) ){
					case 1:
						$table = trim($temp);
						break;
					case 2:
						$db    = $temp[0];
						$table = $temp[1];
						break;
					default:
						d($temp);
				}
			}
		}

		//	...
		if( $db ){
			$table = $_db->Quote($db).'.'.$_db->Quote($table);
		}else{
			$table = $_db->Quote($table);
		}

		//	...
		return "SELECT $field FROM $table $where $limit $order $offset";
	}
}