<?php
require 'config.php';
session_start();

// Verifica la sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php'); // Redirige si no hay sesión activa
    exit;
}

// Configuración del límite de inactividad (30 minutos)
$inactivity_limit = 1800;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $inactivity_limit)) {
    session_unset(); // Limpia la sesión
    session_destroy(); // Destruye la sesión
    header('Location: login.php'); // Redirige al login
    exit;
}

$_SESSION['last_activity'] = time(); // Actualiza la última actividad

// Obtiene el ID del vendedor desde la sesión
$vendedor_id = $_SESSION['user_id']; // Asegúrate de que `user_id` sea la clave correcta al iniciar sesión

try {
    // Consulta para cargar datos del vendedor
    $stmt = $pdo->prepare("SELECT foto_usuario, nombre, correo, telefono FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$vendedor_id]);
    $vendedor = $stmt->fetch();

    // Verifica si el vendedor existe
    if (!$vendedor) {
        echo "Error: Usuario no encontrado.";
        exit;
    }

    // Consulta para cargar las publicaciones del vendedor
    $stmt = $pdo->prepare("SELECT * FROM publicaciones WHERE id_usuario = ?");
    $stmt->execute([$vendedor_id]);
    $publicaciones = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Error al cargar publicaciones: " . $e->getMessage();
    exit;
}

// Procesar la edición de la publicación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_publicacion'])) {
    // Recuperar datos del formulario
    $id_publicacion = $_POST['id_publicacion'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precio'];
    $condicion = $_POST['condicion'];

    // Manejo de fotos (si se suben nuevas)
    $fotos = [];

    // Si se han subido nuevas fotos, procesarlas
    if (!empty($_FILES['fotos']['name'][0])) {
        $uploads_dir = 'uploads/';
        foreach ($_FILES['fotos']['name'] as $key => $name) {
            $tmp_name = $_FILES['fotos']['tmp_name'][$key];
            $new_name = uniqid('foto_', true) . '.' . pathinfo($name, PATHINFO_EXTENSION);
            move_uploaded_file($tmp_name, $uploads_dir . $new_name);
            $fotos[] = $new_name;
        }
    }

    // Si no se subieron fotos nuevas, conserva las fotos existentes
    if (empty($fotos)) {
        // Obtener las fotos actuales de la publicación
        $stmt = $pdo->prepare("SELECT imagen FROM publicaciones WHERE id_publicacion = ?");
        $stmt->execute([$id_publicacion]);
        $publicacion = $stmt->fetch();

        // Mantener las fotos actuales si no se subieron nuevas
        $fotos_str = $publicacion['imagen'];
    } else {
        // Si se subieron nuevas fotos, las concatenamos
        $fotos_str = implode(',', $fotos);
    }

    // Actualizar la base de datos con los nuevos datos
    try {
        $stmt = $pdo->prepare("UPDATE publicaciones SET nombre = ?, descripcion = ?, precio = ?, condicion = ?, imagen = ? WHERE id_publicacion = ?");
        $stmt->execute([$nombre, $descripcion, $precio, $condicion, $fotos_str, $id_publicacion]);
        $_SESSION['mensaje'] = "Publicación actualizada con éxito"; // Guardamos mensaje en la sesión
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error al actualizar la publicación: " . $e->getMessage();
    }

    // Redirigir a publicaciones.php después de guardar los cambios
    header("Location: publicaciones.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Publicaciones</title>
    <link rel="icon" href="carrito.ico">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #001f3f;
            color: white;
        }

        .card {
            background-color: #f8f9fa;
            border: none;
            border-radius: 8px;
            overflow: hidden;
            transition: transform 0.2s;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .card-title {
            color: #001f3f;
            font-weight: bold;
        }

        .card-text {
            color: #333333;
        }

        /* Fondo azul claro con opacidad para resaltar las letras */
        .card-body {
            background-color: rgba(173, 216, 230, 0.8);
            color: #003366;
            padding: 15px;
        }

        /* Modal */
        .modal-header {
            background-color: #003366;
            color: white;
        }

        .modal-body {
            background-color: #f0f8ff;
            color: #003366;
        }

        .modal-footer {
            background-color: #f0f8ff;
        }

        .modal-title {
            font-size: 1.5rem;
        }

        .modal-content {
            border-radius: 8px;
        }

        .modal-body p {
            margin-bottom: 15px;
        }

        .modal-footer .btn {
            border-radius: 5px;
        }

        /* Estilo para el carrusel */
        .carousel-item img {
            width: 100%;
            height: auto;
            object-fit: contain;
            /* Asegura que la imagen no se distorsione */
            max-height: 350px;
            /* Limitar la altura máxima */
            border: 2px solid #001f3f;
            /* Borde alrededor de las imágenes */
            border-radius: 8px;
            /* Bordes redondeados */
        }

        /* Estilo para las imágenes de la tarjeta */
        .card-img-top {
            width: 100%;
            height: 300px;
            object-fit: contain;
            /* Ajusta la imagen sin recortarla */
            border-bottom: 2px solid #001f3f;
        }


        /* Flechas de navegación del carrusel */
        .carousel-control-prev,
        .carousel-control-next {
            position: absolute;
            top: 50%;
            z-index: 10;
            color: #007bff;
            background-color: rgba(0, 0, 0, 0.5);
            border: none;
            font-size: 2rem;
            padding: 15px;
            transform: translateY(-50%);
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <h1 class="text-white">Mis Publicaciones</h1>
        <a href="paginainicial.php" class="btn btn-m btn-primary mt-3 mb-4">
            <i class="fas fa-arrow-left"></i> Volver
        </a>

        <!-- Mostrar mensaje cuando no hay publicaciones -->
        <?php if (empty($publicaciones)): ?>
            <div class="col-12">
                <p class="text-center text-white fs-4">No tienes publicaciones activas.</p>
            </div>
        <?php else: ?>

            <!-- Mensajes de éxito o error -->
            <?php if (isset($_SESSION['mensaje'])): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $_SESSION['mensaje']; ?>
                    <?php unset($_SESSION['mensaje']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $_SESSION['error']; ?>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <!-- Renderizar las publicaciones -->
            <div class="row row-cols-1 row-cols-md-3 g-4">
                <?php foreach ($publicaciones as $publicacion): ?>
                    <div class="col">
                        <div class="card mb-4 shadow-sm">
                            <img src="uploads/<?php echo htmlspecialchars(explode(',', $publicacion['imagen'])[0]); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($publicacion['nombre']); ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($publicacion['nombre']); ?></h5>
                                <p class="card-text">
                                    <?php echo htmlspecialchars(mb_strimwidth($publicacion['descripcion'], 0, 100, '...')); ?>
                                </p>
                                <p><strong>Precio:</strong> $<?php echo number_format(htmlspecialchars($publicacion['precio']), 2); ?> COP</p>
                                <p><strong>Cantidad Disponible:</strong> <?php echo htmlspecialchars($publicacion['cantidad_disponible']); ?></p>

                                <!-- Botón Ver más -->
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal<?php echo $publicacion['id_publicacion']; ?>">
                                    Ver más
                                </button>

                                <!-- Botón para editar -->
                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editarModal<?php echo $publicacion['id_publicacion']; ?>">
                                    Editar Publicación
                                </button>

                                <!-- Botón para abrir el modal de confirmación -->
                                <button class="btn btn-sm btn-danger text-white" data-bs-toggle="modal" data-bs-target="#confirmDeleteModal<?php echo $publicacion['id_publicacion']; ?>">
                                    Borrar publicación
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Modal para mostrar los detalles completos -->
                    <div class="modal fade" id="modal<?php echo $publicacion['id_publicacion']; ?>" tabindex="-1" aria-labelledby="modalLabel<?php echo $publicacion['id_publicacion']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="modalLabel<?php echo $publicacion['id_publicacion']; ?>"><?php echo htmlspecialchars($publicacion['nombre']); ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- Carrusel de imágenes -->
                                    <div id="carousel<?php echo $publicacion['id_publicacion']; ?>" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
                                        <div class="carousel-inner">
                                            <?php
                                            $imagenes = explode(',', $publicacion['imagen']);
                                            foreach ($imagenes as $index => $imagen):
                                            ?>
                                                <div class="carousel-item <?php echo ($index === 0) ? 'active' : ''; ?>">
                                                    <img src="uploads/<?php echo htmlspecialchars($imagen); ?>" class="d-block w-100 preview-image" alt="Imagen del producto">
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <button class="carousel-control-prev" type="button" data-bs-target="#carousel<?php echo $publicacion['id_publicacion']; ?>" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Anterior</span>
                                        </button>
                                        <button class="carousel-control-next" type="button" data-bs-target="#carousel<?php echo $publicacion['id_publicacion']; ?>" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Siguiente</span>
                                        </button>
                                    </div>

                                    <!-- Descripción y detalles -->
                                    <h3 class="mt-4"><strong>Descripción</strong></h3>
                                    <p><?php echo nl2br(htmlspecialchars($publicacion['descripcion'])); ?></p>
                                    <h5><strong>Precio: </strong>$<?php echo number_format(htmlspecialchars($publicacion['precio']), 2, ',', '.'); ?> COP</h5>
                                    <p><strong>Cantidad Disponible:</strong> <?php echo htmlspecialchars($publicacion['cantidad_disponible']); ?></p>
                                    <p><strong>Condición:</strong> <?php echo htmlspecialchars($publicacion['condicion']); ?></p>
                                    <p><strong>Categoría:</strong> <?php echo htmlspecialchars($publicacion['categoria']); ?></p>
                                    <p><strong>Teléfono de contacto:</strong> <?php echo htmlspecialchars($publicacion['telefono']); ?></p>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de Confirmación para Borrar Publicación -->
                    <div class="modal fade" id="confirmDeleteModal<?php echo $publicacion['id_publicacion']; ?>" tabindex="-1" aria-labelledby="confirmDeleteModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirmación de Eliminación</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    ¿Estás seguro de que deseas eliminar esta publicación? Esta acción no se puede deshacer.
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                                    <form action="eliminar_publicacion.php" method="POST">
                                        <input type="hidden" name="id_publicacion" value="<?php echo $publicacion['id_publicacion']; ?>">
                                        <button type="submit" class="btn btn-danger">Sí, Eliminar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Modal de edición -->
                    <div class="modal fade" id="editarModal<?php echo $publicacion['id_publicacion']; ?>" tabindex="-1" aria-labelledby="editarModalLabel<?php echo $publicacion['id_publicacion']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editarModalLabel<?php echo $publicacion['id_publicacion']; ?>">Editar Publicación</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form action="publicaciones.php" method="POST" enctype="multipart/form-data">
                                    <div class="modal-body">
                                        <input type="hidden" name="id_publicacion" value="<?php echo $publicacion['id_publicacion']; ?>">
                                        <!-- Campos del formulario -->
                                        <div class="mb-3">
                                            <label for="nombre" class="form-label">Nombre del Producto</label>
                                            <input type="text" class="form-control" name="nombre" value="<?php echo htmlspecialchars($publicacion['nombre']); ?>" placeholder="Ejemplo: Camiseta deportiva Nike" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="cantidad_disponible" class="form-label">Cantidad Disponible (Stock)</label>
                                            <input type="number" class="form-control" name="cantidad_disponible" value="<?php echo htmlspecialchars($publicacion['cantidad_disponible']); ?>" placeholder="Ejemplo: 10" required>
                                        </div>

                                        <div class="mb-3">
                                            <label for="descripcion" class="form-label">Descripción</label>
                                            <textarea class="form-control" name="descripcion" placeholder="Incluye detalles como el color, tamaño, material, etc." required><?php echo htmlspecialchars($publicacion['descripcion']); ?></textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label for="fotos" class="form-label">Fotos</label>
                                            <input type="file" class="form-control" name="fotos[]" multiple>
                                        </div>
                                        <div class="mb-3">
                                            <label for="precio" class="form-label">Precio</label>
                                            <input type="number" class="form-control" name="precio" value="<?php echo htmlspecialchars($publicacion['precio']); ?>" step="0.01" placeholder="Ejemplo: 50000" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="categoria" class="form-label">Categoría</label>
                                            <select class="form-select" name="categoria" required>
                                                <option value="comida" <?php echo ($publicacion['categoria'] === 'comida') ? 'selected' : ''; ?>>Comida</option>
                                                <option value="servicio" <?php echo ($publicacion['categoria'] === 'servicio') ? 'selected' : ''; ?>>Servicio</option>
                                                <option value="ropa" <?php echo ($publicacion['categoria'] === 'ropa') ? 'selected' : ''; ?>>Ropa</option>
                                                <option value="tecnologia" <?php echo ($publicacion['categoria'] === 'tecnologia') ? 'selected' : ''; ?>>Tecnología</option>
                                                <option value="hogar" <?php echo ($publicacion['categoria'] === 'hogar') ? 'selected' : ''; ?>>Hogar</option>
                                            </select>
                                        </div>

                                        <div class="mb-3">
                                            <label for="condicion" class="form-label">Condición</label>
                                            <select class="form-select" name="condicion" required>
                                                <option value="nuevo" <?php echo ($publicacion['condicion'] === 'nuevo') ? 'selected' : ''; ?>>Nuevo</option>
                                                <option value="usado" <?php echo ($publicacion['condicion'] === 'usado') ? 'selected' : ''; ?>>Usado</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label for="whatsapp_link" class="form-label">Link a tu chat de WhatsApp</label>
                                            <input type="url" class="form-control" name="whatsapp_link" value="<?php echo htmlspecialchars($publicacion['whatsapp_link']); ?>" required placeholder="Ejemplo: https://wa.me/3001234567">
                                        </div>

                                        <label for="telefono" class="form-label">Teléfono de Contacto</label>
                                        <input type="text" class="form-control" name="telefono" value="<?php echo htmlspecialchars($publicacion['telefono']); ?>" required pattern="^\d{10}$" title="Debe ingresar un número de teléfono válido de 10 dígitos" placeholder="Ejemplo: 3001234567">

                                        <button type="submit" class="btn btn-primary mt-3">Guardar Cambios</button>
                                        <button type="button" class="btn btn-secondary mt-3" data-bs-dismiss="modal">Cerrar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>