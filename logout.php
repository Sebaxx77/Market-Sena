<?php
session_start(); // Iniciar la sesión
session_unset(); // Limpiar todas las variables de sesión
session_destroy(); // Destruir la sesión
header("Location: login.php"); // Redirigir a la página de inicio de sesión
exit();
?>
