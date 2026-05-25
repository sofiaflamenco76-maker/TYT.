<?php

session_start();

$conn = new mysqli("localhost","root","kokoa2324","TyTDB",3306);

//filtro 
$buscar = $_GET['buscar'] ?? "";
$categoria = $_GET['categoria'] ?? "";

/* QUERY */
$sql = "SELECT * FROM Producto WHERE 1=1"/*siempre va hacer verdadero */;

if($buscar != ""){
$sql .= " AND nombre LIKE '%$buscar%'";
}

if($categoria != ""){
$sql .= " AND id_categoria='$categoria'";
}

$resultado = $conn->query($sql);

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Tyt Products</title>
<link rel="stylesheet" href="carrito.css">

<style>
body{
font-family:Arial;
margin:20px;
}

.productos{
display:flex;
flex-wrap:wrap;
gap:20px;
justify-content: center; 
}

.producto{
border:1px solid #ccc;
padding:10px;
width:220px;
border-radius:8px;
text-align: center; 
display: flex;
flex-direction: column;
justify-content: space-between;
}

.producto img{
width: 100%;
height: 200px;        
object-fit: contain;  
background: #fdfdfd;  
margin-bottom: 10px;
}

/* 🔥 LOGIN A LA IZQUIERDA CORREGIDO */
#usuario{
position:fixed;
top:20px;
left:20px; /* Cambiado de right a left */
cursor:pointer;
font-size:22px;
z-index: 1000;
}

/* USAMOS EL ID PARA QUE TENGA MÁS FUERZA QUE LA CLASE */
#carritoIcono {
    position: fixed !important;
    top: 20px !important;
    right: 60px !important; /* Lo puse en 400 para que veas que SÍ se mueve */
    left: auto !important;
    z-index: 1000;
    background: #f2f2f2;
    padding: 8px 15px;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    border: 1px solid #ccc;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* El contador también necesita estar bien posicionado respecto al icono */
#contadorCarrito {
    background: #e63946;
    color: white;
    border-radius: 50%;
    padding: 2px 7px;
    font-size: 11px;
    position: absolute;
    top: -10px;
    right: -10px;
    border: 2px solid white;
}

#carritoPanel{
position:fixed;
top:0;
right:-350px;
width:350px;
height:100%;
background:white;
box-shadow:-3px 0 10px rgba(0,0,0,0.3);
padding:20px;
overflow:auto;
transition:0.3s;
z-index: 1001;
}

#carritoPanel.activo{
right:0;
}
</style>

</head>

<body>

<h1>Tyt Products</h1>

<?php if(isset($_SESSION['id_usuario'])){ ?>
    <a href="logout.php" class="btn-logout">Cerrar sesión</a>
<?php }else{ ?>
    <div id="usuario" onclick="window.location.href='login.php'">
        👤
    </div>
<?php } ?>

<div id="carritoIcono" class = "btn-carrito"onclick="abrirCarrito()">
    🛒
    <span id="contadorCarrito">0</span>
</div>

<form method="GET" style="text-align:center;">
    <input type="text" name="buscar" placeholder="Buscar producto" value="<?php echo $buscar ?>">
    <select name="categoria">
        <option value="">Todas las categorias</option>
        <?php
        $cats = $conn->query("SELECT * FROM Categoria");
        while($c = $cats->fetch_assoc()){
            $selected = ($categoria == $c['id_categoria']) ? "selected" : "";
            echo "<option value='".$c['id_categoria']."' $selected>".$c['nombre']."</option>";
        }
        ?>
    </select>
    <button>Buscar</button>
</form>

<br>

<div class="productos">
    <?php while($fila = $resultado->fetch_assoc()/*Recorre todos los productos */){ ?>
    <div class="producto">
        <div>
            <h3><?php echo $fila['nombre']; ?></h3>
            <p>$<?php echo $fila['precio']; ?></p>
            <p><?php echo $fila['descripcion']; ?></p>
            <img src="imagenes/<?php echo $fila['imagen']; ?>">
        </div>
        <button onclick="agregarCarrito(<?php echo $fila['id_Producto']; ?>)">
            Agregar al carrito
        </button>
    </div>
    <?php } ?>
</div>

<div id="carritoPanel">
    <h2>Tu carrito</h2>
    <div id="contenidoCarrito"></div>
    <hr>
    <button onclick="irAPago()" style="width:100%; padding:10px; cursor:pointer;">
        Ir a pagar
    </button>
    <p style="background:#f2f2f2;padding:10px;border-radius:6px; font-size: 13px;">
        Cualquier consulta por productos al mayoreo comuníquese por vía WhatsApp al 
        <b>6116 3889</b>
    </p>
</div>

<script>
//es java, hace todas las funciones del carrito
function abrirCarrito(){
    document.getElementById("carritoPanel").classList.add("activo");
    cargarCarrito();
}

function agregarCarrito(id){
    fetch("carrito.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"accion=agregar&id="+id
    })
    .then(res=>res.json())
    .then(data=>{
        document.getElementById("contenidoCarrito").innerHTML=data.html;//Actualiza carrito en tiempo real
        document.getElementById("contadorCarrito").innerText=data.cantidad;
    });
}

function cargarCarrito(){
    fetch("carrito.php")//envia los datos sin cargar la pag
    .then(res=>res.json())
    .then(data=>{
        document.getElementById("contenidoCarrito").innerHTML=data.html;
        document.getElementById("contadorCarrito").innerText=data.cantidad;
    });
}

function cambiarCantidad(id,accion){
    fetch("carrito.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},//Los datos vienen como formulario
        body:"accion="+accion+"&id="+id//le llegan los datos que se enviaron
    })
    .then(res=>res.json())
    .then(data=>{
        document.getElementById("contenidoCarrito").innerHTML=data.html;
        document.getElementById("contadorCarrito").innerText=data.cantidad;
    });
}

function eliminarProducto(id){
    fetch("carrito.php",{
        method:"POST",
        headers:{"Content-Type":"application/x-www-form-urlencoded"},
        body:"accion=eliminar&id="+id
    })
    .then(res=>res.json())
    .then(data=>{
        document.getElementById("contenidoCarrito").innerHTML=data.html;
        document.getElementById("contadorCarrito").innerText=data.cantidad;
    });
}

function irAPago(){
    fetch("carrito.php")
    .then(res=>res.json())
    .then(data=>{
        if(data.cantidad == 0){
            alert("No puedes ir a pagar sin productos en el carrito 🛒");
            return;
        }
        window.location.href = "pago.php";
    });
}

document.addEventListener("click", function(e){
    let carrito = document.getElementById("carritoPanel");
    let icono = document.getElementById("carritoIcono");
    if(!carrito.contains(e.target) && !icono.contains(e.target)){
        carrito.classList.remove("activo");
    }
});

cargarCarrito();
</script>

</body>
</html>