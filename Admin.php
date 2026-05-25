<link rel="stylesheet" href="Admin.css">

<?php

$conn = new mysqli("localhost","root","kokoa2324","TyTDB",3306);

if($conn->connect_error){
    die("Error de conexion: " . $conn->connect_error);
}

$accion = $_GET['accion'] ?? "listar";

?>

<h2>Panel Administrador</h2>

<a href="admin.php">Ver productos</a> |
<a href="admin.php?accion=crear">Nuevo producto</a> |
<a href="admin.php?accion=categoria">Nueva categoria</a> |
<a href="admin.php?accion=pedidos">Ver pedidos</a>

<hr>

<?php

/* LISTAR PRODUCTOS + BUSCADOR */

if($accion == "listar"){

$buscar = $_GET['buscar'] ?? "";//Lo busca por el nombre
$categoriaFiltro = $_GET['categoria'] ?? "";

$categorias = $conn->query("SELECT * FROM Categoria");

$where = [];
//Si buscamos se añade una condición al array
if($buscar != ""){
    $where[] = "Producto.nombre LIKE '%$buscar%'";//busca por el nombre
}
//Se filtra por nombre y por categoria
if($categoriaFiltro != ""){
    $where[] = "Producto.id_categoria = '$categoriaFiltro'";
}

$whereSQL = "";

//junta todo, el nombre y categoria
if(count($where) > 0){
    $whereSQL = "WHERE " . implode(" AND ", $where);
}

$sql = "
SELECT Producto.*, Categoria.nombre as categoria
FROM Producto
LEFT JOIN Categoria ON Producto.id_categoria = Categoria.id_categoria
$whereSQL
";

$resultado = $conn->query($sql);
?>

<form method="GET">

<input type="hidden" name="accion" value="listar">

<input type="text" name="buscar" placeholder="Buscar producto..."
value="<?php echo $buscar; ?>">

<select name="categoria">
<option value="">Todas</option>

<?php
while($cat = $categorias->fetch_assoc()){
    $selected = ($categoriaFiltro == $cat['id_categoria']) ? "selected" : "";
    echo "<option value='".$cat['id_categoria']."' $selected>".$cat['nombre']."</option>";
}
?>

</select>

<button type="submit">Buscar</button>
<a href="admin.php">Limpiar</a>

</form>

<br>

<?php

echo "<table border='1'>";

echo "<tr>
<th>ID</th>
<th>Nombre</th>
<th>Categoria</th>
<th>Precio</th>
<th>Stock</th>
<th>Imagen</th>
<th>Acciones</th>
</tr>";

while($row = $resultado->fetch_assoc()){

/* 🔥 STOCK EN ROJO */
$stockColor = ($row['stock'] < 20) ? "style='color:red;font-weight:bold'" : "";

echo "<tr>

<td>".$row['id_Producto']."</td>
<td>".$row['nombre']."</td>
<td>".$row['categoria']."</td>
<td>".$row['precio']."</td>
<td $stockColor>".$row['stock']."</td>

<td><img src='imagenes/".$row['imagen']."' width='80'></td> /*Muestra imagen desde carpeta*/

<td>
<a href='admin.php?accion=editar&id=".$row['id_Producto']."'>Editar</a>
<a href='admin.php?accion=eliminar&id=".$row['id_Producto']."'>Eliminar</a>
</td>

</tr>";

}

echo "</table>";

}


/* CREAR CATEGORIA */

if($accion == "categoria"){

if($_SERVER['REQUEST_METHOD'] == 'POST'){

$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];

$sql = "INSERT INTO Categoria(nombre,descripcion)
VALUES('$nombre','$descripcion')";

if(!$conn->query($sql)){
    die("Error: " . $conn->error);
}

echo "<script>alert('Categoria creada');window.location='admin.php';</script>";

}
?>

<h3>Nueva Categoria</h3>

<form method="POST">
Nombre<br>
<input name="nombre"><br><br>

Descripcion<br>
<input name="descripcion"><br><br>

<button>Guardar</button>
</form>

<?php
}



/* CREAR PRODUCTO (ARREGLADO)*/

if($accion == "crear"){

if($_SERVER['REQUEST_METHOD'] == 'POST'){

$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$precio = $_POST['precio'];
$stock = $_POST['stock'];
$categoria = $_POST['categoria'];

$imagen = "";

if(isset($_FILES['imagen']) && $_FILES['imagen']['name'] != ""){
    $imagen = $_FILES['imagen']['name'];
    move_uploaded_file($_FILES['imagen']['tmp_name'], "imagenes/".$imagen); //Guarda la imagen
}

$sql = "INSERT INTO Producto
(nombre,descripcion,precio,stock,imagen,id_categoria)
VALUES
('$nombre','$descripcion','$precio','$stock','$imagen','$categoria')";

if(!$conn->query($sql)){
    die("Error al insertar: " . $conn->error);
}

header("Location: admin.php");
exit;
}

$categorias = $conn->query("SELECT * FROM Categoria");
?>

<h3>Nuevo Producto</h3>

<form method="POST" enctype="multipart/form-data">

Nombre<br>
<input name="nombre"><br><br>

Descripcion<br>
<input name="descripcion"><br><br>

Precio<br>
<input name="precio"><br><br>

Stock<br>
<input name="stock"><br><br>

Imagen<br>
<input type="file" name="imagen"><br><br>

Categoria<br>

<select name="categoria">
<?php
while($cat = $categorias->fetch_assoc()){
echo "<option value='".$cat['id_categoria']."'>".$cat['nombre']."</option>";
}
?>
</select>

<br><br>

<button>Guardar</button>

</form>

<?php
}



/*EDITAR PRODUCTO*/

if($accion == "editar"){

$id = $_GET['id'];

$sql = "SELECT * FROM Producto WHERE id_Producto='$id'";
$resultado = $conn->query($sql);
$producto = $resultado->fetch_assoc();

$categorias = $conn->query("SELECT * FROM Categoria");

if($_SERVER['REQUEST_METHOD'] == 'POST'){

$nombre = $_POST['nombre'];
$precio = $_POST['precio'];
$stock = $_POST['stock'];
$categoria = $_POST['categoria'];

$imagenActual = $producto['imagen'];

if(isset($_FILES['imagen']) && $_FILES['imagen']['name'] != ""){
    $imagenNueva = $_FILES['imagen']['name'];
    move_uploaded_file($_FILES['imagen']['tmp_name'], "imagenes/".$imagenNueva);
    $imagenActual = $imagenNueva;
}

$sql = "UPDATE Producto SET
nombre='$nombre',
precio='$precio',
stock='$stock',
imagen='$imagenActual',
id_categoria='$categoria'
WHERE id_Producto='$id'";

$conn->query($sql);

header("Location: admin.php");
exit;

}
?>

<h3>Editar Producto</h3>

<form method="POST" enctype="multipart/form-data">

Nombre<br>
<input name="nombre" value="<?php echo $producto['nombre']; ?>"><br><br>

Precio<br>
<input name="precio" value="<?php echo $producto['precio']; ?>"><br><br>

Stock<br>
<input name="stock" value="<?php echo $producto['stock']; ?>"><br><br>

Categoria<br>

<select name="categoria">

<?php
while($cat = $categorias->fetch_assoc()){
$selected = ($producto['id_categoria'] == $cat['id_categoria']) ? "selected" : "";
echo "<option value='".$cat['id_categoria']."' $selected>".$cat['nombre']."</option>";
}
?>

</select>

<br><br>

Imagen actual<br>
<img src="imagenes/<?php echo $producto['imagen']; ?>" width="100"><br><br>

Cambiar imagen<br>
<input type="file" name="imagen"><br><br>

<button>Actualizar</button>

</form>

<?php
}



/* ELIMINAR PRODUCTO*/

if($accion == "eliminar"){

$id = $_GET['id'];

$conn->query("DELETE FROM Producto WHERE id_Producto='$id'");

header("Location: admin.php");
exit;

}



/*VER PEDIDOS*/

if($accion == "pedidos"){

$sql = "
SELECT 
Pedido.id_pedido,
Pedido.fecha_pedido,
Pedido.total,
Pedido.direccionEnvio,
Usuario.nombre as usuario,
Pago.metodo_pago,
Pago.estado_pago
FROM Pedido
JOIN Usuario ON Pedido.id_usuario = Usuario.id_usuario
JOIN Pago ON Pedido.id_pedido = Pago.id_pedido
";

$resultado = $conn->query($sql);

echo "<h3>Pedidos realizados</h3>";

echo "<table border='1'>";

echo "<tr>
<th>ID</th>
<th>Usuario</th>
<th>Direccion - Telefono</th>
<th>Total</th>
<th>Metodo</th>
<th>Estado Pago</th>
<th>Productos</th>
<th>Acciones</th>
</tr>";

while($row = $resultado->fetch_assoc()){

/* 🔥 COLOR ESTADO */
$color = "black";
if($row['estado_pago'] == "Pendiente") $color = "orange";
if($row['estado_pago'] == "Aprobado") $color = "green";
if($row['estado_pago'] == "Rechazado") $color = "red";

/* 🔥 DIRECCIÓN VERTICAL */
$direccionVertical = str_replace(",", ",<br>", $row['direccionEnvio']);//Hace saltos de línea

echo "<tr>

<td>".$row['id_pedido']."</td>
<td>".$row['usuario']."</td>

<td style='max-width: 200px; word-wrap: break-word; word-break: break-word;'>".$direccionVertical."</td>

<td>$".$row['total']."</td>
<td>".$row['metodo_pago']."</td>
<td style='color:$color;font-weight:bold'>".$row['estado_pago']."</td>

<td>
<a href='admin.php?accion=detalle&id=".$row['id_pedido']."'>Ver productos</a>
</td>

<td style='display: flex; flex-direction: column; gap: 5px;'>
<a href='admin.php?accion=estado&id=".$row['id_pedido']."'>Actualizar pago</a>
<a href='admin.php?accion=borrarPedido&id=".$row['id_pedido']."'>Eliminar</a>
</td>

</tr>";

}

echo "</table>";

}



/* DETALLE PEDIDO */

if($accion == "detalle"){

$id = $_GET['id'];

echo "<h3>Productos del pedido #$id</h3>";

$res = $conn->query("
SELECT Producto.nombre, DetallePedido.cantidad, DetallePedido.subtotal
FROM DetallePedido
JOIN Producto ON Producto.id_Producto = DetallePedido.id_producto
WHERE id_pedido=$id
");

while($row = $res->fetch_assoc()){
echo $row['nombre']." | Cant: ".$row['cantidad']." | $".$row['subtotal']."<br>";
}

echo "<br><a href='admin.php?accion=pedidos'>Volver</a>";
}


/* CAMBIAR ESTADO*/

if($accion == "estado"){

$id = $_GET['id'];

if(isset($_POST['guardar'])){
$estado = $_POST['estado'];
$conn->query("UPDATE Pago SET estado_pago='$estado' WHERE id_pedido=$id");
header("Location: admin.php?accion=pedidos");
}
?>

<form method="POST">
<select name="estado">
<option>Pendiente</option>
<option>Aprobado</option>
<option>Rechazado</option>
</select>
<button name="guardar">Guardar</button>
</form>

<?php
}


/*ELIMINAR PEDIDO*/

if($accion == "borrarPedido"){

$id = $_GET['id'];

$conn->query("DELETE FROM DetallePedido WHERE id_pedido=$id");
$conn->query("DELETE FROM Pago WHERE id_pedido=$id");
$conn->query("DELETE FROM Pedido WHERE id_pedido=$id");

header("Location: admin.php?accion=pedidos");
}

?>