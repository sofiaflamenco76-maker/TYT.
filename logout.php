<?php

session_start();//se necesita para poder acceder a la sesion sin importar que este loaugt
session_destroy();//elimina todo la sesion 

header("Location: index.php");

?>