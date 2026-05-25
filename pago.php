<?php

session_start();

if(!isset($_SESSION['id_usuario'])){
    echo "Debes iniciar sesión para comprar";
    echo "<br><a href='login.php'>Ir al login</a>";
    exit;
}

$conn = new mysqli("localhost","root","kokoa2324","TyTDB",3306);

if($conn->connect_error){
    die("Error de conexión: " . $conn->connect_error);
}

$id_usuario = $_SESSION['id_usuario'];

/* OBTENER CARRITO */
$sql = "SELECT id_carrito FROM Carrito WHERE id_usuario=$id_usuario";
$res = $conn->query($sql);

if($res->num_rows == 0){
    echo "El carrito está vacío";
    exit;
}

$id_carrito = $res->fetch_assoc()['id_carrito'];

/* OBTENER PRODUCTOS DEL CARRITO */
$res = $conn->query("
SELECT Producto.id_Producto, Producto.nombre, Producto.precio, Item_carro.cantidad
FROM Item_carro
JOIN Producto ON Producto.id_Producto = Item_carro.id_producto
WHERE id_carrito=$id_carrito
");

$total = 0;
$productos = [];

?>

<link rel="stylesheet" href="pago.css">

<h2>Confirmar pedido</h2>

<table border="1">
<tr>
<th>Producto</th>
<th>Precio</th>
<th>Cantidad</th>
<th>Subtotal</th>
</tr>

<?php
while($row = $res->fetch_assoc()){

    $sub = $row['precio'] * $row['cantidad'];
    $total += $sub;
    $productos[] = $row;
    //suma todo(la cantidad de productos que lleva)

    echo "<tr>
    <td>".$row['nombre']."</td>
    <td>".$row['precio']."</td>
    <td>".$row['cantidad']."</td>
    <td>".$sub."</td>
    </tr>";
}
?>

</table>

<h3>Total: $<?php echo $total ?></h3>

<form method="POST">

Direccion exacta<br>
<input name="direccion" required><br><br>

Punto de referencia<br>
<input name="referencia" required><br><br>

Telefono<br>
<input name="telefono" required><br><br>

Municipio<br>
<input name="municipio" required><br><br>

Departamento<br>
<input name="departamento" required><br><br>

Metodo de pago<br>

<select name="metodo" id="metodo" onchange="mostrarTransferencia()">
<option value="contraentrega">Contra entrega</option>
<option value="transferencia">Transferencia</option>
</select>

<div id="transferencia" style="display:none;margin-top:10px;">
Contactar por WhatsApp: <b>6116 3889</b><br><br>
<a href="https://wa.me/50361163889" target="_blank">Ir a WhatsApp</a>
</div>

<br>

<button name="confirmar">Confirmar pedido</button>

</form>

<script>
    //java, si selecciona trans. se le muestra el link de whats
function mostrarTransferencia(){
    let m = document.getElementById("metodo").value;
    document.getElementById("transferencia").style.display = (m=="transferencia")?"block":"none";
}
</script>

<?php

/* CONFIRMAR PEDIDO */

if(isset($_POST['confirmar'])){

    $direccion = $_POST['direccion'];
    $referencia = $_POST['referencia'];
    $telefono = $_POST['telefono'];
    $municipio = $_POST['municipio'];
    $departamento = $_POST['departamento'];

    $direccionCompleta = "Dir:$direccion | Ref:$referencia | Tel:$telefono | Mun:$municipio | Dep:$departamento";

    $metodo = $_POST['metodo'];

    /* 🔥 VOLVER A CONSULTAR EL CARRITO, para asegurar si no hay actualizaciones antes de guardar*/
    $res = $conn->query("
    SELECT Producto.id_Producto, Producto.nombre, Producto.precio, Item_carro.cantidad
    FROM Item_carro
    JOIN Producto ON Producto.id_Producto = Item_carro.id_producto
    WHERE id_carrito=$id_carrito
    ");

    $total = 0;
    $productos = [];

    while($row = $res->fetch_assoc()){
        $sub = $row['precio'] * $row['cantidad'];
        $total += $sub;
        $productos[] = $row;
    }

    /* VALIDAR CARRITO */
    if(count($productos) == 0){
        die("Error: carrito vacío");
    }

    /* INSERT PEDIDO */
    $sqlPedido = "INSERT INTO Pedido(fecha_pedido,total,direccionEnvio,id_usuario)
    VALUES(NOW(),$total,'$direccionCompleta',$id_usuario)";

    if(!$conn->query($sqlPedido)){
        die("Error Pedido: " . $conn->error);
    }

    $id_pedido = $conn->insert_id;

    /* INSERT DETALLE */
    foreach($productos as $p){

        $idp = $p['id_Producto'];
        $cant = $p['cantidad'];
        $precio = $p['precio'];
        $sub = $precio * $cant;

        $sqlDetalle = "INSERT INTO DetallePedido
        (cantidad,precioUnitario,subtotal,id_pedido,id_producto)
        VALUES($cant,$precio,$sub,$id_pedido,$idp)";

        if(!$conn->query($sqlDetalle)){
            die("Error Detalle: " . $conn->error);
        }

        /* ACTUALIZAR STOCK */
        $conn->query("UPDATE Producto SET stock = stock - $cant WHERE id_Producto=$idp");
    }

    /* INSERT PAGO */
    $sqlPago = "INSERT INTO Pago(fecha_pago,monto,metodo_pago,id_pedido,estado_pago)
    VALUES(NOW(),$total,'$metodo',$id_pedido,'Pendiente')";

    if(!$conn->query($sqlPago)){
        die("Error Pago: " . $conn->error);
    }
    //todo se le mostrara al admin

    /* LIMPIAR CARRITO */
    $conn->query("DELETE FROM Item_carro WHERE id_carrito=$id_carrito");

    echo "<h3>Pedido realizado correctamente</h3>";
    echo "<a href='index.php'><button>Volver a comprar</button></a>";
}

?>