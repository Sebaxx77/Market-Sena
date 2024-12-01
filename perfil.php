<?php
require 'config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '/home/u524804893/domains/marketsena.shop/vendor/autoload.php';
 // Asegúrate de cargar el autoload de Composer

session_start();

// Tiempo de inactividad antes de cerrar la sesión (en segundos)
$inactivity_limit = 1800; // 30 minutos

// Verifica si la sesión está activa
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirigir a login si no hay sesión activa
    exit;
}

// Verifica el tiempo de inactividad solo si no estamos en un proceso de actualización
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit) && !isset($_POST['direccion']) && !isset($_POST['telefono'])) {
    session_unset(); // Limpia la sesión
    session_destroy(); // Destruye la sesión
    header('Location: login.php'); // Redirigir a login
    exit;
}

// Actualiza la última actividad
$_SESSION['last_activity'] = time();

// Obtener los datos del usuario
$id_usuario = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT nombre, documento, rol, fecha_registro, direccion, telefono, correo, fecha_cambio_contrasena,foto_usuario FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id_usuario]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar si se obtuvo el usuario
if (!$usuario) {
    echo "Usuario no encontrado.";
    exit;
}

// Inicializamos los mensajes
$mensaje_guardado = "";
$mensaje_cierre_sesion = "";
$mensaje_contrasena = "";
$mensaje_error_contrasena = "";

// Si se cambian dirección o teléfono
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['direccion']) && isset($_POST['telefono']) && !isset($_POST['contrasena_nueva'])) {
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];

    // Actualiza los datos del usuario en la base de datos
    $stmt = $pdo->prepare("UPDATE usuarios SET direccion = ?, telefono = ? WHERE id_usuario = ?");
    $stmt->execute([$direccion, $telefono, $id_usuario]);

    $mensaje_guardado = "Cambios guardados con éxito.";  // Mostrar mensaje de éxito
}

// Si se sube una nueva foto de perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_usuario'])) {
    // Verificar si se cargó una imagen
    if ($_FILES['foto_usuario']['error'] === UPLOAD_ERR_OK) {
        // Obtener información del archivo subido
        $archivo_tmp = $_FILES['foto_usuario']['tmp_name'];
        $archivo_nombre = $_FILES['foto_usuario']['name'];
        $archivo_extension = pathinfo($archivo_nombre, PATHINFO_EXTENSION);

        // Validar que sea una imagen
        $extensiones_validas = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($archivo_extension), $extensiones_validas)) {
            // Crear una nueva ruta para guardar la foto
            $directorio_destino = 'uploads/profile/';
            $nombre_nuevo = uniqid('foto_', true) . '.' . $archivo_extension;
            $ruta_destino = $directorio_destino . $nombre_nuevo;

            // Mover el archivo al directorio de destino
            if (move_uploaded_file($archivo_tmp, $ruta_destino)) {
                // Actualizar la base de datos con la nueva foto de perfil
                $stmt = $pdo->prepare("UPDATE usuarios SET foto_usuario = ? WHERE id_usuario = ?");
                $stmt->execute([$ruta_destino, $id_usuario]);

                $mensaje_guardado = "Foto de perfil actualizada con éxito.";
            } else {
                $mensaje_error_contrasena = "Error al subir la foto de perfil.";
            }
        } else {
            $mensaje_error_contrasena = "Solo se permiten archivos de imagen (JPG, JPEG, PNG, GIF).";
        }
    } else {
        $mensaje_error_contrasena = "Error al subir el archivo. Intente nuevamente.";
    }
}

// Lógica para saber si puede cambiar la contraseña
$puede_cambiar_contrasena = true;
$mensaje_fecha_limite = "";

if (isset($usuario['fecha_cambio_contrasena'])) {
    $fecha_cambio = new DateTime($usuario['fecha_cambio_contrasena']);
    $fecha_limite = $fecha_cambio->modify('+7 days');

    if (new DateTime() < $fecha_limite) {
        $puede_cambiar_contrasena = false; // No puede cambiar la contraseña
        $mensaje_fecha_limite = "Podrá cambiar su contraseña de nuevo hasta " . $fecha_limite->format('d-m-Y');
    }
}

// Si se cambia la contraseña, hacer que la sesión se cierre
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contrasena_nueva']) && $_POST['contrasena_nueva'] === $_POST['confirmar_contrasena']) {
    // Cambiar la contraseña en la base de datos
    $nueva_contrasena = password_hash($_POST['contrasena_nueva'], PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE usuarios SET contrasena = ?, fecha_cambio_contrasena = NOW() WHERE id_usuario = ?");
    $stmt->execute([$nueva_contrasena, $id_usuario]);

    // Mostrar mensaje de sesión cerrada
    $mensaje_cierre_sesion = "Tu sesión será cerrada. Inicia sesión con tu nueva contraseña.";

    // Cerrar sesión y redirigir al login
    session_unset();
    session_destroy();
    echo "<script>alert('$mensaje_cierre_sesion'); window.location.href = 'login.php';</script>";
    exit;
}

// Verifica si el usuario es vendedor
$es_vendedor = (isset($usuario['rol']) && $usuario['rol'] === 'vendedor');

// Manejar la lógica para mostrar el formulario de verificación
$mostrar_verificacion = false;
if (isset($_POST['borrar_cuenta'])) {
    // Si se presionó el botón para borrar la cuenta, se genera el código de verificación
    $_SESSION['codigo_verificacion'] = rand(100000, 999999); // Generar un código de 6 dígitos
    $codigo_verificacion = $_SESSION['codigo_verificacion'];

    // Obtener el correo del usuario
    $correo = $usuario['correo'];

    // Enviar correo de confirmación
    $mail = new PHPMailer(true);
    try {
        // Configura el servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.hostinger.com'; // Usar el servidor SMTP de Hostinguer
        $mail->SMTPAuth = true;
        $mail->Username = 'marketsena@marketsena.shop'; // Tu correo
        $mail->Password = 'Juanborrero1@'; // Contraseña
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        // Destinatarios
        $mail->setFrom('marketsena@marketsena.shop', 'MARKET-SENA');
        $mail->addAddress($correo); // Agregar destinatario

        // Contenido del correo
        $mail->isHTML(true);
        $mail->Subject = 'Hasta pronto';
        $mail->Body = "Estás a punto de eliminar tu cuenta. Tu código de verificación es: <strong>$codigo_verificacion</strong>";

        // Enviar correo
        $mail->send();
        $mostrar_verificacion = true; // Mostrar el formulario de verificación después de enviar el correo
    } catch (Exception $e) {
        echo "El mensaje no pudo ser enviado. Mailer Error: {$mail->ErrorInfo}";
    }
}

// Mostrar el mensaje adecuado
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contrasena_nueva']) && $_POST['contrasena_nueva'] !== $_POST['confirmar_contrasena']) {
    $mensaje_error_contrasena = "Las contraseñas no coinciden.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contrasena_nueva']) && $_POST['contrasena_nueva'] === $_POST['confirmar_contrasena']) {
    // Aquí agregamos la lógica para la actualización de contraseñas, si es necesario
    // Asegurar que la contraseña cumpla con los requisitos de seguridad
    if (
        strlen($_POST['contrasena_nueva']) >= 8 &&
        preg_match('/[A-Z]/', $_POST['contrasena_nueva']) &&
        preg_match('/[a-z]/', $_POST['contrasena_nueva']) &&
        preg_match('/\d/', $_POST['contrasena_nueva']) &&
        preg_match('/[!@#$%^&*(),.?":{}|<>]/', $_POST['contrasena_nueva'])
    ) {
        // Aquí va la lógica de actualización de contraseña si se cumplen los requisitos
    } else {
        $mensaje_error_contrasena = "La contraseña no cumple con los requisitos de seguridad.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MarketSena - Página Inicial</title>
    <link rel="icon" href="logo.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #001f3f;
            color: white;
            margin: 0;
            padding-top: 70px;
            /* Ajustar para que el contenido no quede detrás del navbar */
        }

        .navbar {
            background-color: #00264d;
            /* Azul oscuro */
        }

        .navbar-brand {
            color: #ffffff;
            font-weight: bold;
        }

        .navbar-nav .nav-link {
            color: #ffffff;
        }

        .navbar-nav .nav-link:hover {
            color: #007bff;
            /* Cambia el color del texto al hacer hover */
        }

        .btn-outline-success {
            border-color: #007bff;
            color: #ffffff;
            /* Letra blanca en el botón de búsqueda */
        }

        .btn-outline-success:hover {
            background-color: #007bff;
            color: white;
        }

        .btn-outline-primary {
            border-color: #0056b3;
            color: #ffffff;
            /* Letra blanca en el botón "Crear Publicación" */
        }
        
                .dropdown-item:hover {
            background-color: #007bff;
        }

        .cerrar:hover {
            background-color: red;
        }

        .btn-outline-primary:hover {
            background-color: #007bff;
            color: white;
        }

        .readonly-input {
            background-color: #d0d0d0;
            /* Color de fondo gris más oscuro */
            border: none;
            /* Sin borde */
            pointer-events: none;
            /* Sin interacciones */
            color: #6c757d;
            /* Color gris para el texto */
        }

        .titulo-editar-perfil {
            margin: 3%;
        }


        /* Requisitos de la contraseña */
        .requirements {
            margin-top: 20px;
            font-size: 14px;
        }

        .requirements li {
            margin-bottom: 5px;
        }

        .requirements li.text-danger {
            color: #dc3545;
            /* Rojo para los requisitos no cumplidos */
            font-weight: bold;
        }

        .requirements li.text-success {
            color: #28a745;
            /* Verde para los requisitos cumplidos */
            font-weight: bold;
        }

        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="paginainicial.php"><strong>Market - Sena</strong></a>
            <form class="d-flex flex-grow-1 ms-3 me-3" action="paginainicial.php" method="GET">
                <input class="form-control me-2" type="search" placeholder="Buscar productos..." aria-label="Search" name="query" style="flex-grow: 1;">
                <button class="btn btn-outline-success" type="submit">Buscar</button>
            </form>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse pb-2" id="navbarNav">
                <?php if ($es_vendedor): ?>
                    <a class="btn btn-outline-primary mt-2" href="crear.php" title="Crear Publicación">
                        <i class="fas fa-plus"></i> Crear Publicación
                    </a>
                <?php endif; ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="paginainicial.php">Inicio</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriasDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Categorías
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="categoriasDropdown">
                            <li><a class="dropdown-item" href="paginainicial.php?categoria=comida">Comida</a></li>
                            <li><a class="dropdown-item" href="paginainicial.php?categoria=servicio">Servicio</a></li>
                            <li><a class="dropdown-item" href="paginainicial.php?categoria=ropa">Ropa</a></li>
                            <li><a class="dropdown-item" href="paginainicial.php?categoria=tecnologia">Tecnología</a></li>
                            <li><a class="dropdown-item" href="paginainicial.php?categoria=hogar">Hogar</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="soporte.php">Soporte</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php
                            // Mostrar solo los dos primeros nombres
                            $nombres = explode(" ", $_SESSION['nombre_usuario']);
                            echo htmlspecialchars(implode(" ", array_slice($nombres, 0, 2)));
                            ?>
                        </a> 
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <?php if ($es_vendedor): ?>
                                <li><a class="dropdown-item" href="publicaciones.php">Mis publicaciones</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item cerrar" href="logout.php">Cerrar sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <br>
            <a href="paginainicial.php" class="btn btn-m btn-primary mt-3">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        <br>
        <h1 class="titulo-editar-perfil text-center">Perfil de Usuario</h1>
        <form action="perfil.php" method="POST" enctype="multipart/form-data">
            <div class="container">
                <?php if ($mensaje_guardado): ?>
                    <div class="alert alert-success" role="alert" style="text-align: center;">
                        <?php echo $mensaje_guardado; ?>
                    </div>
                <?php endif; ?>

                <!-- Mostrar la foto de perfil actual -->
                <div class="mb-3 text-center">
                    <?php if (!empty($usuario['foto_usuario'])): ?>
                        <!-- Si existe una foto de perfil, mostrarla -->
                        <div>
                            <img src="<?php echo htmlspecialchars($usuario['foto_usuario']); ?>" alt="Foto de perfil" class="rounded-circle" width="180" height="180" style="border: 2px solid white;">
                        </div>
                        <br>
                        <p>Si deseas cambiar la foto de perfil, selecciona una nueva imagen:</p>
                    <?php else: ?>
                        <!-- Si no hay foto de perfil, mostrar una imagen predeterminada y el mensaje -->
                        <div>
                            <img src="uploads/default/default_avatar.jpg" alt="Foto de perfil" class="rounded-circle" width="180" height="180" style="border: 2px solid black;">
                        </div>
                        <p>No tienes una foto de perfil, añade una nueva:</p>
                    <?php endif; ?>
                    <!-- Campo para subir una nueva foto de perfil -->
                    <input type="file" class="form-control" id="foto_usuario" name="foto_usuario" accept="image/*">
                </div>

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre:</label>
                    <input type="text" id="nombre" class="form-control readonly-input" value="<?php echo htmlspecialchars($usuario['nombre']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="documento" class="form-label">Documento:</label>
                    <input type="text" id="documento" class="form-control readonly-input" value="<?php echo htmlspecialchars($usuario['documento']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="rol" class="form-label">Rol:</label>
                    <input type="text" id="rol" class="form-control readonly-input" value="<?php echo htmlspecialchars($usuario['rol']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <label for="direccion" class="form-label">Dirección:</label>
                    <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo htmlspecialchars($usuario['direccion']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="telefono" class="form-label">Teléfono:</label>
                    <input type="tel" id="telefono" class="form-control" name="telefono" value="<?php echo htmlspecialchars($usuario['telefono']); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="correo" class="form-label">Correo Electrónico:</label>
                    <input type="email" class="form-control readonly-input" id="correo" name="correo" value="<?php echo htmlspecialchars($usuario['correo']); ?>" readonly>
                </div>

                <div class="mb-3">
                    <button type="submit" class="btn btn-outline-success">Actualizar Datos</button>
                </div>
            </div>
        </form>

        <hr>
        <form action="perfil.php" method="POST" id="changePasswordForm">
            <div class="mb-3">
                <label for="contrasena_actual" class="form-label">Contraseña Actual:</label>
                <input type="password" id="contrasena_actual"
                    class="form-control <?php echo !$puede_cambiar_contrasena ? 'readonly-input' : ''; ?>"
                    name="contrasena_actual"
                    <?php echo !$puede_cambiar_contrasena ? 'readonly' : ''; ?>
                    required>
            </div>
            <div class="mb-3">
                <label for="contrasena_nueva" class="form-label">Nueva Contraseña:</label>
                <input type="password" id="contrasena_nueva"
                    class="form-control <?php echo !$puede_cambiar_contrasena ? 'readonly-input' : ''; ?>"
                    name="contrasena_nueva"
                    <?php echo !$puede_cambiar_contrasena ? 'readonly' : ''; ?>
                    required>
            </div>
            <div class="mb-3">
                <label for="confirmar_contrasena" class="form-label">Confirmar Nueva Contraseña:</label>
                <input type="password" id="confirmar_contrasena"
                    class="form-control <?php echo !$puede_cambiar_contrasena ? 'readonly-input' : ''; ?>"
                    name="confirmar_contrasena"
                    <?php echo !$puede_cambiar_contrasena ? 'readonly' : ''; ?>
                    required>
            </div>

            <!-- Requisitos de la contraseña -->
            <?php if ($puede_cambiar_contrasena): ?>
                <div class="requirements">
                    <ul>
                        <li id="length">Debe tener al menos 8 caracteres.</li>
                        <li id="uppercase">Debe contener al menos una letra mayúscula.</li>
                        <li id="lowercase">Debe contener al menos una letra minúscula.</li>
                        <li id="number">Debe contener al menos un número.</li>
                        <li id="special">Debe contener al menos un carácter especial (!@#$%^&*).</li>
                    </ul>
                </div>
            <?php else: ?>
                <p class="text-danger"><?php echo $mensaje_fecha_limite; ?></p>
            <?php endif; ?>

            <div class="mb-3">
                <button type="submit" class="btn btn-outline-success" <?php echo !$puede_cambiar_contrasena ? 'disabled' : ''; ?>>Cambiar Contraseña</button>
            </div>
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <?php if ($mensaje_cierre_sesion): ?>
                <div class="alert alert-warning" role="alert">
                    <?php echo $mensaje_cierre_sesion; ?>
                </div>
            <?php endif; ?>
        </form>
        <hr>
        <h3 class="text-center">Eliminar Cuenta</h3>
        <form method="POST" action="">
            <button type="submit" name="borrar_cuenta" class="btn btn-danger">Eliminar mi cuenta</button>
        </form>

        <?php if ($mostrar_verificacion): ?>
            <hr>
            <div class="mt-4 mb-4" id="form-borrarcuenta">
                <h4 class="text-center">Verificación de Cuenta</h4>
                <p class="text-center">Por favor, ingrese el código de verificación enviado a su correo.</p>
                <form method="POST" action="verificarborrado.php">
                    <input type="number" name="codigo_verificacion" required class="form-control" placeholder="Código de verificación">
                    <button type="submit" class="btn btn-danger mt-2">Eliminar mi cuenta definitivamente</button>
                </form>
            </div>
        <?php endif; ?>

    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener el formulario
            const form = document.getElementById('changePasswordForm');

            // Verificar que el formulario existe
            if (form) {
                // Obtener los elementos de la contraseña y la lista de requisitos
                const passwordInput = form.querySelector('#contrasena_nueva');
                const confirmPasswordInput = form.querySelector('#confirmar_contrasena');
                const lengthRequirement = document.getElementById('length');
                const uppercaseRequirement = document.getElementById('uppercase');
                const lowercaseRequirement = document.getElementById('lowercase');
                const numberRequirement = document.getElementById('number');
                const specialRequirement = document.getElementById('special');

                // Función para verificar los requisitos de la contraseña
                function checkPasswordRequirements() {
                    const password = passwordInput.value;

                    // Verificar longitud mínima
                    if (password.length >= 8) {
                        lengthRequirement.classList.add('text-success');
                        lengthRequirement.classList.remove('text-danger');
                    } else {
                        lengthRequirement.classList.add('text-danger');
                        lengthRequirement.classList.remove('text-success');
                    }

                    // Verificar al menos una letra mayúscula
                    if (/[A-Z]/.test(password)) {
                        uppercaseRequirement.classList.add('text-success');
                        uppercaseRequirement.classList.remove('text-danger');
                    } else {
                        uppercaseRequirement.classList.add('text-danger');
                        uppercaseRequirement.classList.remove('text-success');
                    }

                    // Verificar al menos una letra minúscula
                    if (/[a-z]/.test(password)) {
                        lowercaseRequirement.classList.add('text-success');
                        lowercaseRequirement.classList.remove('text-danger');
                    } else {
                        lowercaseRequirement.classList.add('text-danger');
                        lowercaseRequirement.classList.remove('text-success');
                    }

                    // Verificar al menos un número
                    if (/\d/.test(password)) {
                        numberRequirement.classList.add('text-success');
                        numberRequirement.classList.remove('text-danger');
                    } else {
                        numberRequirement.classList.add('text-danger');
                        numberRequirement.classList.remove('text-success');
                    }

                    // Verificar al menos un carácter especial
                    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                        specialRequirement.classList.add('text-success');
                        specialRequirement.classList.remove('text-danger');
                    } else {
                        specialRequirement.classList.add('text-danger');
                        specialRequirement.classList.remove('text-success');
                    }
                }

                // Ejecutar la verificación cuando el usuario escribe en el campo
                passwordInput.addEventListener('input', checkPasswordRequirements);

                // Validar el formulario cuando se envía
                form.addEventListener('submit', function(event) {
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;

                    let valid = true;

                    // Verificar que las contraseñas coincidan
                    if (password !== confirmPassword) {
                        alert("Las contraseñas no coinciden.");
                        valid = false;
                    }

                    // Verificar que todos los requisitos estén marcados como correctos
                    if (password.length < 8 || !/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/\d/.test(password) || !/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                        alert("La contraseña no cumple con todos los requisitos de seguridad.");
                        valid = false;
                    }

                    // Si no es válido, prevenir el envío del formulario
                    if (!valid) {
                        event.preventDefault();
                    }
                });
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>