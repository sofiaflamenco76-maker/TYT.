<?php

session_start();

$conn = new mysqli("localhost","root","kokoa2324","TyTDB",3306);

if($conn->connect_error){
die("Error conexion");
}

/* USUARIO DE SESION */

if(!isset($_SESSION['id_usuario'])){
echo json_encode(["html"=>"Debes iniciar sesión","cantidad"=>0]);/*debe de iniciar sesion para poder realizar
una compra*/
exit;
}

$id_usuario = $_SESSION['id_usuario'];


/* OBTENER CARRITO */

$sql = "SELECT id_carrito FROM Carrito WHERE id_usuario=$id_usuario";
$res = $conn->query($sql);

if($res->num_rows > 0){

$row = $res->fetch_assoc();//recorre los productos
$id_carrito = $row['id_carrito'];

}else{

$conn->query("
INSERT INTO Carrito(fecha_creacion,total,id_usuario)
VALUES(NOW(),0,$id_usuario)
");

$id_carrito = $conn->insert_id;

}

$accion = $_POST['accion'] ?? "";//recibe lo que manda java(funciones del carrito)


/* AGREGAR PRODUCTO */

if($accion == "agregar"){

$id = $_POST['id'];

$p = $conn->query("
SELECT stock,precio FROM Producto
WHERE id_Producto=$id
")->fetch_assoc();

$stock = $p['stock'];
$precio = $p['precio'];

$sql = "
SELECT cantidad FROM Item_carro
WHERE id_carrito=$id_carrito AND id_producto=$id
";

$r = $conn->query($sql);

if($r->num_rows > 0){

$row = $r->fetch_assoc();
$cantidad = $row['cantidad'];

if($cantidad < $stock){

$conn->query("
UPDATE Item_carro
SET cantidad=cantidad+1
WHERE id_carrito=$id_carrito AND id_producto=$id
");

}

}else{

$conn->query("
INSERT INTO Item_carro(cantidad,subtotal,id_carrito,id_producto)
VALUES(1,$precio,$id_carrito,$id)
");

}

}


/* AUMENTAR CANTIDAD */

if($accion == "mas"){

$id = $_POST['id'];

$p = $conn->query("
SELECT stock FROM Producto
WHERE id_Producto=$id
")->fetch_assoc();

$stock = $p['stock'];

$c = $conn->query("
SELECT cantidad FROM Item_carro
WHERE id_carrito=$id_carrito AND id_producto=$id
")->fetch_assoc();

$cantidad = $c['cantidad'];

if($cantidad < $stock){

$conn->query("
UPDATE Item_carro
SET cantidad=cantidad+1
WHERE id_carrito=$id_carrito AND id_producto=$id
");

}

}


/* DISMINUIR */

if($accion == "menos"){

$id = $_POST['id'];

$conn->query("
UPDATE Item_carro
SET cantidad=cantidad-1
WHERE id_carrito=$id_carrito
AND id_producto=$id
AND cantidad>1
");

}


/* ELIMINAR */

if($accion == "eliminar"){

$id = $_POST['id'];

$conn->query("
DELETE FROM Item_carro
WHERE id_carrito=$id_carrito
AND id_producto=$id
");

}


/* CARGAR CARRITO */

$sql = "

SELECT Producto.nombre,
Producto.precio,
Producto.imagen,
Producto.stock,
Item_carro.cantidad,
Item_carro.id_producto

FROM Item_carro

JOIN Producto ON Producto.id_Producto = Item_carro.id_producto

WHERE id_carrito=$id_carrito

";

$res = $conn->query($sql);

$html = "";
$total = 0;
$cantidadTotal = 0;

while($row = $res->fetch_assoc()){

$precio = $row['precio'];
$cantidad = $row['cantidad'];

$subtotal = $precio * $cantidad;

$total += $subtotal;
$cantidadTotal += $cantidad;

$html .= "<div class='itemCarrito'>";

$html .= "<img src='imagenes/".$row['imagen']."'>";

$html .= "<div>";

$html .= "<b>".$row['nombre']."</b>";

$html .= "<p>$".$precio."</p>";

$html .= "<p>Stock disponible: ".$row['stock']."</p>";

$html .= "<div class='cantidad'>";

$html .= "<button onclick='cambiarCantidad(".$row['id_producto'].",\"menos\")'>-</button>";

$html .= $cantidad;

$html .= "<button onclick='cambiarCantidad(".$row['id_producto'].",\"mas\")'>+</button>";

$html .= "</div>";

$html .= "<button onclick='eliminarProducto(".$row['id_producto'].")'>Eliminar</button>";

$html .= "</div>";

$html .= "</div>";

}

$html .= "<hr>";
$html .= "<h3>Total: $".$total."</h3>";
//Devuelve datos a Java
echo json_encode([
"html"=>$html,
"cantidad"=>$cantidadTotal
]);

?>