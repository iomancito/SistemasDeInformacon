<?php
require_once("user.php");
require_once("product.php");
require_once("feature.php");
require_once("rating.php");
class dbhandler{
    
    private $dburl = "localhost";
    private $dbname = "SisInf";
    private $dbuser = "joseluis";
    private $dbpass = "joseluis";
    private $mysqli = null;
    
    
	/**
	 *	Construcor del manejador
	 * 	Crea un objeto mysqli para los datos de la db asignados a los atributos de clase
	 * 
	 *  @return		TRUE si tiene exito al realizar la conexión, FALSE en caso contrario.
	 */
	public function __construct(){
		$this->mysqli = new mysqli($this->dburl, $this->dbuser, $this->dbpass, $this->dbname);

		if ($this->mysqli->connect_errno) {
			printf("Falló la conexión: %s\n", $mysqli->connect_error);
			return FALSE;
		}
		$this->mysqli->set_charset("utf8");
		return TRUE;
	}
    
    
    /**
     * Realiza la funcion de login y devuelve un objeto user si tiene exito 
     * 
     * @param	$email	email del usuario que buscamos
     * @param	$pass	password del usuario que buscamos	
     * @return 			si hay conincidencias, un objeto user o FALSE en caso contrario
	 */
	public function login($email, $pass){
		//esacapamos el email para evitar SQL inyection
		$email = $this->mysqli->real_escape_string($email);
		//generamos hash para el password (tb evita SQL inyection)
		$pass = $this->mysqli->real_escape_string($pass);
		
		
		$sql = "SELECT *
		FROM registered_user
		WHERE email = '$email'";
		//realizamos la consulta
		$result = $this->mysqli->query($sql);

		//solo debería haber un único acierto, dado que los emails son únicos
		
		if ($result->num_rows == 1){
			$fila = $result->fetch_assoc();
			if(password_verify($pass, $fila['password'])){
				$user = new user($fila['name'], $fila['login'], $fila['email'], $fila['surname'], $fila['dob'], $fila['u_id']);
				return $user;
			}
		}
		return FALSE;
	}
    
    
	/**
	 * Gestiona la inserción de un nuevo usuario en la base de datos
	 * 
	 * @param	$login		nick del usuario con el que quier ser conocido
	 * @param	$pass		contraseña para el acceso al sistem
	 * @param	$email		email para la identificación en el sistema y contacto
	 * @param	$nombre		nombre real o no
	 * @param	$apellidos	apellidos reales o no
	 * @param	$dob		fecha de nacimiento
	 * @return				TRUE si consigue ingresar al usuario en el sistema, FALSE en caso contrario
	 */
	public function newUser($login, $pass, $email, $nombre, $apellidos, $dob){
		//escapamos todo el formulario para evitar SQL inyection
		$login = $this->mysqli->real_escape_string($login);
		$email = $this->mysqli->real_escape_string($email);
		$nombre = $this->mysqli->real_escape_string($nombre);
		$apellidos = $this->mysqli->real_escape_string($apellidos);
		$dob = $this->mysqli->real_escape_string($dob);
		//generamos hash para el password (tb evita SQL inyection)
		$password = password_hash($pass, PASSWORD_DEFAULT);
		
		$sql = "SELECT email
				FROM registered_user 
				WHERE email = '$email'";
		
		//realizamos la consulta
		$result = $this->mysqli->query($sql);
		
		//no debe haber coincidencias
		if($result->num_rows ==1){
			return FALSE;
		}else{
			//incrementamos la tabla usuarios
			$sql = "INSERT INTO user () VALUES ()";
			$this->mysqli->query($sql);
		
			$sql = "INSERT INTO registered_user (u_id, login, email, password, dob, name, surname)
					VALUES ((SELECT MAX(u_id) FROM user), '$login', '$email', '$password', '$dob', '$nombre', '$apellidos')";
			$this->mysqli->query($sql);
			return TRUE;
		}
	}
	
	
	/**
	 * Modificar usuario existent
	 * 
	 * @param	$apellidos	apellidos a modificar
	 * @param	$nombre		nombre a modificar
	 * @param	$fecha		fecha de naciomiento a modificar
	 * @param	$email		email a modificar
	 * @param	$pass		password a modificar
	 * @return				TRUE si efectua la modificación con éxito, FALSE en caso contrario
	 */
	 
	public function modifyUser($nombre, $apellidos, $email, $dob, $pass, $email2){
		//escapamos todo el formulario para evitar SQL inyection
		$email = $this->mysqli->real_escape_string($email);
		$nombre = $this->mysqli->real_escape_string($nombre);
		$apellidos = $this->mysqli->real_escape_string($apellidos);
		$dob = $this->mysqli->real_escape_string($dob);
		//generamos hash para el password (tb evita SQL inyection)
		$password = password_hash($pass, PASSWORD_DEFAULT);
		
		$sql = "UPDATE registered_user
				SET name = '$nombre', surname = '$apellidos', email = '$email', dob = '$dob', password = '$password'
				WHERE email = '$email2'";
		echo $sql;
		if ($this->mysqli->query($sql)) {
			return TRUE;
		}
		else {
			return FALSE;
		}
			
	}
	
	/**
	 * Calcula el número de páginas
	 * 
	 * @param	$tabla		tabla de la que se desea mostrar información
	 * @param	$muestraN	número de elementos que se desean mostrar por página
	 * @return				número de páginas con dichos parámetros
	 */
	public function numPaginas($tabla, $muestraN){
		$sql = "SELECT *
				FROM $tabla";
		
		$result = $this->mysqli->query($sql);
		$rows = $result->num_rows;
		$paginas = intval($rows / $muestraN);
		if ($rows%$muestraN>0) $paginas++;
		return $paginas;
	}
	
	
	/**
	 * Listado de artículos
	 * 
	 * @param	$pagina		Número de página a mostrar
	 * @param	$numXpag	Número de artículos por página a mostar
	 * @return				un SplObjectStorage con todos los objetos 'product' de la consulta
	 */
	public function listadoArticulos($pagina, $numXpag){
		$pagina = ($pagina-1)*$numXpag;
		$sql = "SELECT *
				FROM product
				ORDER BY p_id
				LIMIT $pagina, $numXpag";

		$result = $this->mysqli->query($sql);
		
		//creamos un almacen de objetos
		$spl = new SplObjectStorage();
		
		//para cada producto resultado de la consulta
		while($fila = $result->fetch_assoc()){
			//creamos un objeto tipo producto con sus datos
			$product = new product($fila['p_id'], $fila['name'], $fila['description'], $fila['price'], $fila['picture']);
			//y lo almacenamos en el SplObjectStorage
			$spl->attach($product);
		}
		//devolvemos el spl con todos los objetos de productos
		return $spl;
		
	 }
	 
	 /**
	  * Detalle de un artículo
	  * 
	  * @param	$p_id	identificador único del producto
	  * @return			objeto product
	  * 
	  */
	public function productDetail($id){
		$sql = "SELECT *
				FROM product
				WHERE p_id = '$id'";
			
		$result = $this->mysqli->query($sql);
		if ($result->num_rows == 1){
			$fila = $result->fetch_assoc();
			$product = new product($fila['p_id'], $fila['name'], $fila['description'], $fila['price'], $fila['picture']);
			return $product;
		}else return FALSE;
	}
	
	/**
	 * Añade sesión a la base de datos
	 * 
	 * @param	$u_id	identificador numérico del usuario
	 * @return			el token creado en caso de exito, FALSE en caso contrario
	 * 
	 */
	public function createSession($uid){
		
		$expire = time()+7*24*60*60;
		$token = sha1($email.time());
		
		$sql = "SELECT *
				FROM user_session
				WHERE u_id = '$uid'";
		$result = $this->mysqli->query($sql);
		
		if($result->num_rows == 1){
			echo "update";
			$expire = time()+7*24*60*60;
			$sql = "UPDATE user_session
					SET token = '$token', expires = $expire
					WHERE u_id = '$uid'";
			if($this->mysqli->query($sql)) return $token;
			else return FALSE;
		}else{
			echo "create";
			$sql = "INSERT INTO user_session (token, u_id, expires)
					VALUES ('$token', '$uid', '$expire')";
					echo $sql;
			if($this->mysqli->query($sql)) return $token;
			else return FALSE;
		}
	}
	
	/**
	 * Comprueba si existe una sesión de un usuario dado,
	 * devolviendo el objeto user correspondiente en caso afirmativo.
	 * 
	 * @param	$token	el token de la sesión del navegaro
	 * @return			devuelve FALSE si no existe la sesión o ha expirado
	 * 					o el objeto user correspondiente a dicha session en caso afirmativo
	 * 
	 */
	public function getSession($token){
		$time = time();
		$sql = "SELECT u_id
				FROM user_session
				WHERE token = '$token' and expires > '$time'";
		$result = $this->mysqli->query($sql);
		if($result->num_rows != 1) return FALSE;
		else{
			$fila = $result->fetch_assoc();
			$u_id = $fila['u_id'];
			$sql = "SELECT *
					FROM registered_user
					WHERE u_id = '$u_id'";
					
			$result = $this->mysqli->query($sql);
			$fila = $result->fetch_assoc();
			$user = new user($fila['name'], $fila['login'], $fila['email'], $fila['surname'], $fila['dob'], $fila['u_id']);
			return $user;
		}
	}
	
	/*
	 * Listado de caracteristicas de un producto
	 * 
	 * 
	 * 
	 */
	public function getFeatures($p_id){
		$sql = "SELECT *
				FROM feature
				WHERE p_id = '$p_id'";
		$result = $this->mysqli->query($sql);
		
		$spl = new SplObjectStorage();
		
		//para cada producto resultado de la consulta
		while($fila = $result->fetch_assoc()){
			//creamos un objeto tipo producto con sus datos
			$feature = new feature($fila['f_id'], $fila['p_id'], $fila['name'], $fila['description'], $fila['picture']);
			//y lo almacenamos en el SplObjectStorage
			$spl->attach($feature);
		}
		//devolvemos el spl con todos los objetos de productos
		return $spl;
	}
	
	/**
	 * Listado de artículos
	 * 
	 * @param	$pagina		Número de página a mostrar
	 * @param	$numXpag	Número de comentarios por página a mostar
	 * @param	$pid		Identificardor del producto
	 * @return				un SplObjectStorage con todos los objetos 'product' de la consulta
	 */
	public function listadoComentarios($pid){
		$pagina = ($pagina-1)*$numXpag;
		$sql = "SELECT r.*, pa.*, ru.login
				FROM rating r, product_activity pa, registered_user ru
				WHERE pa.p_id = '$pid' and pa.pa_id=r.pa_id and ru.u_id = pa.u_id";

		$result = $this->mysqli->query($sql);
		
		//creamos un almacen de objetos
		$spl = new SplObjectStorage();
		
		//para cada producto resultado de la consulta
		while($fila = $result->fetch_assoc()){
			//creamos un objeto tipo producto con sus datos
			$rating = new rating($fila['login'], $fila['rating'], $fila['comment'],$fila['date']);
			//y lo almacenamos en el SplObjectStorage
			$spl->attach($rating);
		}
		//devolvemos el spl con todos los objetos de productos
		return $spl;
		
	 }
	 
	 /*
	  * Crear una actividad de un usuario
	  * 
	  * @param	$u_id	id del usuario
	  * @descp	$descp	descripción de la actividad
	  * @return			TRUE si añade con exito, FALSE en caso contrario
	  * 
	  */
	public function setActivity($u_id, $descp){
		$sql = "INSERT INTO user_activity (u_id, description)
				VALUES ('$u_id', '$descp')";
		if($this->mysqli->query($sql)) return true;
		else return false;
	 }
	 
	 /*
	  * Comentar un producto
	  * 
	  * @param	$coment	Texto del comentario
	  * @param	$rate	Puntuadión del producto
	  * @param	$uid	id del usuario
	  * @param	$pid	id del producto
	  * @return			TRUE si realiza la consulta con exito FALSE en caso contrario
	  * 
	  */
	public function newRate($comment, $rating, $uid, $pid){
		$sql = "INSERT INTO product_activity (p_id, u_id)
				VALUES ('$pid', '$uid')";
		$sql2= "INSERT INTO rating (pa_id, rating, comment)
				VALUES (LAST_INSERT_ID(), '$rating', '$comment')";
		if($this->mysqli->query($sql) && $this->mysqli->query($sql2)) return TRUE;
		else return FALSE;
	}
}?>
