<?php

/**
 * localhost;
 */

const DB_HOST      = 'localhost';
const DB_USER      = 'root';
const DB_PASS      = '';
const DB_NAME      = 'moiseswebdev';
const CHARSET      = 'utf8';



/**
* Capa de abstracción con mysqli
*/
class DB
{
	private static $conn;  # objecto conector mysqli
	protected static $stmt;  # preparación del query SQL
	protected static $reflection;  # objecto reflexivo de mysqli_stmt
	protected static $sql;  # sentencia sql a ser preparada
	protected static $data;  # array conteniendo los tipos de datos mas los datos a ser enlazados (sera recibido como parametros)
	private static $results = array();  #  colección de datos retornados por consulta de selección
	protected static $errorDB = false;  #  si existe un error en la conexión a la DB


	/**
	 * método para abrir la conexión a la DB
	 */
	private static function conect()
	{
		self::$conn = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
		if ( mysqli_connect_error() ) {
			self::$errorDB = true;
		} else {
			mysqli_set_charset(self::$conn, CHARSET);
		}
	}


	/*  getters */
	public function getNameDB(){ return DB_NAME; }
	public function getChar(){ return self::$conn->character_set_name();}


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
	private static function closeConn()
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
		self::$results = array();  # setear array de resultado
		self::$sql = $sql;  # reiniciar el query $sql
		self::$data = $data;  # reiniciar la propiedad $data
		self::conect();  # conectar a la DB

		if (!self::$errorDB) {
			self::prepareSql();  #preparar el query
			self::setParams();  # enlazar los datos
			self::$stmt->execute();  # ejecutar la consulta
			if ( $fields ) {
				self::getData( $fields );
				return self::$results;  # ARRAY resultado de la consulta SELECT
			} else {
				if ( strpos(strtoupper(self::$sql), 'INSERT') === 0 ) {
					return self::$stmt->insert_id;  #  ID resultado de la consulta INSERT
				} elseif ( strpos(strtoupper(self::$sql), 'UPDATE') === 0 ) {
					return self::$stmt->affected_rows;  # columnas afectadas
				}

			}
			self::closeConn();  # cerrar conexion
		} else {
			/*  Si ocurrio algun problema al conectar a la DB  */
			return 'Error al conectar a la DB!';
		}

	}


	public static function getResultFromQuery($sql) {
		self::$results = array();  # setear array de resultado
		self::$sql = $sql;  # reiniciar el query $sql
		self::conect();  # conectar a la DB

		$resultado = self::$conn->query(self::$sql);
		while (self::$results[] = $resultado->fetch_assoc());
		array_pop(self::$results);
		$resultado->close();
		self::$conn->close();  # cerrar conexion
		return self::$results;
	}


}


// $sql = "SELECT firstname, lastname, id FROM users WHERE id = ?";
// $data = array('s', '100000361696056');
// $fields = array("nombre" => "", "apellido" => "", "id" => "");
// $resultado = $test2->runSql($sql, $data, $fields);
// echo "<pre>".print_r($resultado,true)."</pre>\n";
//


$query = "UPDATE trabajos SET titulo = ? WHERE id = ?;";
$data = array('si','Nombre change UNO 3',5);
$fields = array('datos' => '');
$results = DB::runSql($query, $data);

echo "<pre>".print_r($results,true)."</pre>\n";

?>