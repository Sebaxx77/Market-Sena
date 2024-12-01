<?php
require 'config.php';
session_start();

// Verificar si se pasó el ID del vendedor en la URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $vendedor_id = (int)$_GET['id'];
} else {
    // Redirige a la página principal si no se proporciona un ID de vendedor válido
    header('Location: paginainicial.php');
    exit;
}

try {
    // Obtener la información del vendedor
    $stmt = $pdo->prepare("SELECT foto_usuario, nombre, correo, telefono FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$vendedor_id]);
    $vendedor = $stmt->fetch();

    // Si el vendedor no existe, redirige a la página principal
    if (!$vendedor) {
        header('Location: paginainicial.php');
        exit;
    }

    // Determinar la ruta de la foto de perfil
    $image_url = !empty($vendedor['foto_usuario'])
        ? 'uploads/profile/' . htmlspecialchars($vendedor['foto_usuario'])  // Foto del vendedor desde la base de datos
        : 'uploads/default/default_avatar.jpg';  // Imagen predeterminada si no tiene foto

    // Obtener las publicaciones del vendedor
    $stmt = $pdo->prepare("SELECT * FROM publicaciones WHERE id_usuario = ?");
    $stmt->execute([$vendedor_id]);
    $publicaciones = $stmt->fetchAll();
} catch (PDOException $e) {
    echo "Error al cargar el perfil del vendedor: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil del Vendedor</title>
    <link rel="icon" href="carrito.png">
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
        <div class="mb-3">
            <?php
            // Verifica si el campo 'foto_usuario' tiene un valor
            if (!empty($vendedor['foto_usuario'])) {
                // Obtén la ruta completa desde la base de datos
                $image_url = htmlspecialchars($vendedor['foto_usuario']);

                // Verifica si la imagen realmente existe en la carpeta
                if (!file_exists($image_url)) {
                    // Si la imagen no existe, usa la imagen predeterminada
                    $image_url = 'uploads/default/default_avatar.jpg';
                    $no_image_message = "La foto del vendedor no se encuentra, se mostrará la imagen predeterminada.";
                }
            } else {
                // Si no hay foto de usuario, usa la imagen predeterminada
                $image_url = 'uploads/default/default_avatar.jpg';
                $no_image_message = "Este vendedor no tiene foto de perfil.";
            }
            ?>

            <h1 class="text-white mb-3"><?php echo htmlspecialchars($vendedor['nombre']); ?></h1>
            <div>
                <!-- Mostrar imagen con borde blanco -->
                <img src="<?php echo $image_url; ?>" alt="Foto de perfil" class="rounded-circle" style="border: 2px solid white; width: 180px; height: 180px;">
            </div>
            <?php if (isset($no_image_message)): ?>
                <p class="text-warning"><?php echo $no_image_message; ?></p>
            <?php endif; ?>
            <br>
            <p><strong>Correo:</strong> <?php echo htmlspecialchars($vendedor['correo']); ?></p>
            <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($vendedor['telefono']); ?></p>

            <a href="paginainicial.php" class="btn btn-m btn-primary mt-3">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <h3 class="text-white mt-4 mb-3">Publicaciones del Vendedor</h3>
        <?php if (count($publicaciones) > 0): ?>
            <div class="row">
                <?php foreach ($publicaciones as $publicacion): ?>
                    <div class="col-md-4">
                        <div class="card mb-4 shadow-sm">
                            <?php $imagenes = explode(',', $publicacion['imagen']); ?>
                            <img src="uploads/<?php echo htmlspecialchars($imagenes[0]); ?>" class="card-img-top preview-image" alt="<?php echo htmlspecialchars($publicacion['nombre']); ?>" data-bs-toggle="modal" data-bs-target="#modal<?php echo $publicacion['id_publicacion']; ?>">

                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($publicacion['nombre']); ?></h5>
                                <p class="card-text">
                                    <?php
                                    $descripcion_corta = mb_strimwidth($publicacion['descripcion'], 0, 100, '...');
                                    echo htmlspecialchars($descripcion_corta);
                                    ?>
                                </p>
                                <p><strong>Precio:</strong> $<?php echo number_format(htmlspecialchars($publicacion['precio']), 2); ?> COP</p>
                                <p><strong>Cantidad Disponible:</strong> <?php echo htmlspecialchars($publicacion['cantidad_disponible']); ?></p>
                                <p class="text-muted"><strong>Condición: </strong> <?php echo htmlspecialchars($publicacion['condicion']); ?></p>
                                <p class="text-muted"><strong>Teléfono de contacto: </strong><?php echo htmlspecialchars($publicacion['telefono']); ?></p>

                                <!-- Botón Ver más -->
                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modal<?php echo $publicacion['id_publicacion']; ?>">
                                    Ver más
                                </button>

                                <!-- Botón de WhatsApp -->
                                <?php if (!empty($publicacion['whatsapp_link'])): ?>
                                    <a href="<?php echo htmlspecialchars($publicacion['whatsapp_link']); ?>" target="_blank" class="btn btn-sm btn-success">
                                        Contactar en WhatsApp
                                    </a>
                                <?php endif; ?>
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
                                            <?php foreach ($imagenes as $index => $imagen): ?>
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

                                    <!-- Botón de WhatsApp en el modal -->
                                    <?php if (!empty($publicacion['whatsapp_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($publicacion['whatsapp_link']); ?>" target="_blank" class="btn btn-success">
                                            Contactar en WhatsApp
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-white">Este vendedor no tiene publicaciones disponibles.</p>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>