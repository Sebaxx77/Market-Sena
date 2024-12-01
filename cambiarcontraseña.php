<?php
session_start();

// Conexión a la base de datos
$servername = "localhost";
$username = ""; //tu usuario
$password = ""; //tu contraseña
$dbname = "MarketSenadb"; //nombre de la base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar la conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Procesar el formulario al enviarlo
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Verificar que las contraseñas coincidan
    if ($password !== $confirm_password) {
        $error_message = "Las contraseñas no coinciden.";
    } else {
        // Asegurar que la contraseña cumpla con los requisitos de seguridad
        if (
            strlen($password) >= 8 &&
            preg_match('/[A-Z]/', $password) &&
            preg_match('/[a-z]/', $password) &&
            preg_match('/\d/', $password) &&
            preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)
        ) {
            // Hash de la contraseña
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Aquí debes actualizar la contraseña y la fecha de cambio en la base de datos
            $email = $_SESSION['correo']; // Supongamos que el email está almacenado en la sesión
            $sql = "UPDATE usuarios SET contrasena='$password_hash', fecha_cambio_contrasena=NOW() WHERE correo='$email'";

            if ($conn->query($sql) === TRUE) {
                // Redirigir al usuario a login.php
                header('Location: login.php?success=1');
                exit;
            } else {
                $error_message = "Error al actualizar la contraseña: " . $conn->error;
            }
        } else {
            $error_message = "La contraseña no cumple con los requisitos de seguridad.";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar Contraseña - Market Sena</title>
    <link rel="icon" href="logo.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=Montserrat:wght@700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #001f3f;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            overflow: hidden;
            flex-direction: column;
        }
        .form-container {
            background-color: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
            max-width: 400px;
            width: 100%;
            margin-top: 20px;
        }
        .form-title {
            color: white;
            text-align: center;
            margin-bottom: 10px;
        }
        label {
            color: black;
        }
        .requirement {
            color: red;
            font-size: 0.9em;
        }
        .requirement.fulfilled {
            color: green;
        }
        .error-message {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <h1 class="form-title">Cambiar Contraseña</h1>
    <div class="form-container">
        <form method="post">
            <div class="mb-3">
                <label for="password" class="form-label">Nueva Contraseña:</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="Ingrese su contraseña">
                <div id="password-requirements" class="requirement">
                    <ul>
                        <li id="length" class="requirement">Al menos 8 caracteres</li>
                        <li id="uppercase" class="requirement">Al menos una letra mayúscula</li>
                        <li id="lowercase" class="requirement">Al menos una letra minúscula</li>
                        <li id="number" class="requirement">Al menos un número</li>
                        <li id="special" class="requirement">Al menos un carácter especial</li>
                    </ul>
                </div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirmar Contraseña:</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="Confirme su contraseña">
            </div>
            <button type="submit" class="btn btn-primary w-100">Cambiar Contraseña</button>
            <?php if (isset($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
        </form>
    </div>
    <script>
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const lengthRequirement = document.getElementById('length');
        const uppercaseRequirement = document.getElementById('uppercase');
        const lowercaseRequirement = document.getElementById('lowercase');
        const numberRequirement = document.getElementById('number');
        const specialRequirement = document.getElementById('special');

        // Función para validar los requisitos de la contraseña
        passwordInput.addEventListener('input', function() {
            const passwordValue = passwordInput.value;
            lengthRequirement.classList.toggle('fulfilled', passwordValue.length >= 8);
            uppercaseRequirement.classList.toggle('fulfilled', /[A-Z]/.test(passwordValue));
            lowercaseRequirement.classList.toggle('fulfilled', /[a-z]/.test(passwordValue));
            numberRequirement.classList.toggle('fulfilled', /\d/.test(passwordValue));
            specialRequirement.classList.toggle('fulfilled', /[!@#$%^&*(),.?":{}|<>]/.test(passwordValue));
        });

        // Validar que las contraseñas coincidan
        confirmPasswordInput.addEventListener('input', function() {
            const passwordsMatch = passwordInput.value === confirmPasswordInput.value;
            confirmPasswordInput.setCustomValidity(passwordsMatch ? '' : 'Las contraseñas no coinciden');
        });
    </script>
</body>
</html>
