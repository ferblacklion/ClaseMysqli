<?php

/* constantes para la conexión */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'users_pl');
define('CHARSET','utf8');




/**
* Capa de abstracción con mysqli
*/
class DB
{
	protected static $conn;  # objecto conector mysqli
	protected static $DB = Null;
	protected static $stmt;  # preparación del query SQL
	protected static $reflection;  # objecto reflexivo de mysqli_stmt
	protected static $sql;  # sentencia sql a ser preparada
	protected static $data;  # array conteniendo los tipos de datos mas los datos a ser enlazados (sera recibido como parametros)
	public static $results;  #  colección de datos retornados por consulta de selección
	protected static $failDB = false;  #  si existe un error en la conexión a la DB



	/**
	 * método para abrir la conexión a la DB
	 */
	protected static function conect()
	{
		self::$conn = new mysqli(DB_HOST,DB_USER,DB_PASS,self::$DB);
		if ( mysqli_connect_error() ) {
			self::$failDB = true;
		} else {
			mysqli_set_charset(self::$conn, CHARSET);
		}
	}


	/*  getters */
	public function getName(){ return self::$DB; }


	/**
	 * metodo para preparar una sentecia sql (con marcadores de parametros)
	 */
	protected static function prepareSql()
	{
		self::$stmt = self::$conn->prepare(self::$sql);
		self::$reflection = new ReflectionClass('mysqli_stmt');
	}

	/**
	 * metodo para establecer parametros
	 */
	protected static function setParams()
	{
		$method = self::$reflection->getMethod( 'bind_param' );
		$method->invokeArgs( self::$stmt, self::$data );
	}


	/**
	 * metedo para traer datos
	 * @param  [array] $fields [el cuál será un array asociativo, cuyas claves, serán asociadas a los campos de la tabla (opcional)]
	 * cuando la consulta sea de tipo SELECT
	 */
	protected static function getData($fields)
	{
		$method = self::$reflection->getMethod('bind_result');
		$method->invokeArgs( self::$stmt, $fields );

		while ( self::$stmt->fetch() ) {
			self::$results[] = unserialize( serialize($fields) );
		}
	}


	/**
	 * metodo para cerrar conexion
	 */
	protected static function closeConn()
	{
		self::$stmt->close();
		self::$conn->close();
	}


	/**
	 * metodo para ejecutar una consulta a la DB
	 * @param  [string]  $sql    sentecia SQL
	 * @param  [array]  $data   datos que seran enviados a la DB, con el tipo de dato
	 * @param  [array] $fields [el cuál será un array asociativo, cuyas claves, serán asociadas a los campos de la tabla]
	 * @return [string] en caso que el query sea INSERT retornara el id AUTO_INCREMENT de lo contrario retornara los un array con las claves que se le pasen con el dato de la tabla.
	 */
	public static function runSql( $sql, $data, $fields=False )
	{
		self::$sql = $sql;  # reiniciar el query $sql
		self::$data = $data;  # reiniciar la propiedad $data
		self::conect();  # conectar a la DB

		if (!self::$failDB) {
			self::prepareSql();  #preparar el query
			self::setParams();  # enlazar los datos
			self::$stmt->execute();  # ejecutar la consulta
			if ( $fields ) {
				self::getData( $fields );
				return self::$results;  # ARRAY resultado de la consulta SELECT
			} else {
				if ( strpos(strtoupper(self::$sql), 'INSERT') === 0 ) {
					return self::$stmt->insert_id;  #  ID resultado de la consulta INSERT
				}

			}
			self::closeConn();  # cerrar conexion
		} else {
			/*  Si ocurrio algun problema al conectar a la DB  */
			$msg = '<strong>Error al conectar a la DB</strong>';
			return $msg;
		}

	}


	/**
	 * constructor para sobre escribir el nombre de la DB de lo contrario que no no exita el parametro de usara la constante DB_NAME
	 * @param [string] $DB nombre de la BD
	 */
	function __construct($DB = Null)
	{
		self::$DB = ($DB != Null) ? $DB : DB_NAME;
	}

}

$test = new DB();
$sql = 'INSERT INTO users (Xtop, Yleft ) VALUES (?,?)';
$data = array('ii',10,220);
$insert_id = $test->runSql($sql, $data);
echo "<pre>".print_r($insert_id,true)."</pre>\n";
echo $test->getName();

$test2 = new DB('galloapp');
$sql = "SELECT firstname, lastname, id
	FROM users
	WHERE id = ?";
$data = array('s', '100000549605569');
$fields = array("nombre" => "", "apellido" => "", "id" => "");
$resultado = $test2->runSql($sql, $data, $fields);
echo "<pre>".print_r($resultado,true)."</pre>\n";
echo $test2->getName();

?>