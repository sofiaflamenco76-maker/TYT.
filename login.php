<?php

session_start();

$conn = new mysqli("localhost","root","kokoa2324","TyTDB",3306);

if($conn->connect_error){
die("Error de conexion");
}


/* REGISTRO */

if(isset($_POST['registrar'])){

$nombre = $_POST['nombre'];
$correo = $_POST['correo'];
$contraseña = $_POST['contraseña'];
$telefono = $_POST['telefono'];      
$direccion = $_POST['direccion'];    

if(strpos/*Busca si un texto contiene otro texto como en
este caso si esta la @*/($correo,"@") === false){
echo "El correo debe contener @";
exit;
}

$sql = "INSERT INTO Usuario(nombre,correo,contraseña,telefono,direccion)
VALUES('$nombre','$correo','$contraseña','$telefono','$direccion')";

if($conn->query($sql)){

$id_usuario = $conn->insert_id;
//el usuario queda logueado automaticamente
$_SESSION['id_usuario'] = $id_usuario;
$_SESSION['nombre'] = $nombre;

header("Location: index.php");
exit;

}else{

echo "Error al registrar usuario: " . $conn->error;

}

}


/* LOGIN */
//iniciar sesion
if(isset($_POST['login'])){

$correo = $_POST['correo'];
$contraseña = $_POST['contraseña'];

$sql = "SELECT * FROM Usuario WHERE correo='$correo'";
$res = $conn->query($sql);

if($res->num_rows > 0){

$user = $res->fetch_assoc();
//si el usuario exite se le pide contraseña

if($user['contraseña'] == $contraseña){
   //contraseña, usuario correcta se le manda al index

$_SESSION['id_usuario'] = $user['id_usuario'];
$_SESSION['nombre'] = $user['nombre'];

header("Location: index.php");
exit;

}else{

echo "Contraseña incorrecta";

}

}else{

echo "Usuario no encontrado";

}

}

?>

<!DOCTYPE html>
<html>

<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="login.css">
<title>Login</title>
</head>

<body>

<h2>Iniciar sesión</h2>

<form method="POST">

Correo<br>
<input type="text" name="correo" required>
<br><br>

Contraseña<br>
<input type="password" name="contraseña" required>
<br><br>

<button type="submit" name="login">
Iniciar sesión
</button>

</form>

<hr>

<h2>Crear cuenta</h2>

<form method="POST">

Nombre<br>
<input type="text" name="nombre" required>
<br><br>

Correo<br>
<input type="text" name="correo" required>
<br><br>

Contraseña<br>
<input type="password" name="contraseña" required>
<br><br>

Telefono<br>
<input type="text" name="telefono" required>
<br><br>

Direccion<br>
<input type="text" name="direccion" required>
<br><br>

<button type="submit" name="registrar">
Crear cuenta
</button>

</form>

</body>
</html>