<?php
// controllers/HabitacionCRUDController.php

require_once 'models/HabitacionCRUDModel.php';

class HabitacionCRUDController {
    private $model;
    private $conn;
    private $idioma;

    public function __construct($conn, $idioma = 'es-AR') {
        $this->conn = $conn;
        $this->model = new HabitacionCRUDModel($conn);
        $this->idioma = $idioma;
    }

    // Listar habitaciones con paginación
    public function index() {
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $habitaciones = $this->model->listarHabitaciones($this->idioma, $limit, $offset);
        $total = $this->model->contarHabitaciones();
        $totalPages = ceil($total / $limit);

        include 'views/layouts/header_admin.php';
        include 'views/admin/habitaciones/listado_view.php';
        include 'views/layouts/footer_admin.php';
    }

    // Mostrar formulario para crear habitación
    public function create() {
        $tipos = $this->model->obtenerTiposHabitacion($this->idioma);
        $idiomas = ['es-AR', 'en-US']; // idiomas soportados
        $errores = [];
        $data = [];
        include 'views/layouts/header_admin.php';
        include 'views/admin/habitaciones/form.php';
        include 'views/layouts/footer_admin.php';
    }

    // Guardar nueva habitación
    public function store() {
        $tipos = $this->model->obtenerTiposHabitacion($this->idioma);
        $idiomas = ['es-AR', 'en-US'];
        $errores = [];
        $data = $_POST;

        // Validaciones básicas
        if (empty($data['numero'])) {
            $errores['numero'] = 'El número es obligatorio';
        }
        if (empty($data['tipo_habitacion'])) {
            $errores['tipo_habitacion'] = 'El tipo de habitación es obligatorio';
        }
        if (empty($data['capacidad']) || !is_numeric($data['capacidad'])) {
            $errores['capacidad'] = 'La capacidad es obligatoria y debe ser numérica';
        }
        if (empty($data['precio_noche']) || !is_numeric($data['precio_noche'])) {
            $errores['precio_noche'] = 'El precio por noche es obligatorio y debe ser numérico';
        }
        if (empty($data['estado'])) {
            $errores['estado'] = 'El estado es obligatorio';
        }

        // Validar imagen principal subida
        $imagen_principal = null;
        if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->subirImagen($_FILES['imagen_principal']);
            if ($uploadResult['success']) {
                $imagen_principal = $uploadResult['path'];
            } else {
                $errores['imagen_principal'] = $uploadResult['error'];
            }
        } else {
            $errores['imagen_principal'] = 'La imagen principal es obligatoria';
        }

        if (!empty($errores)) {
            include 'views/layouts/header_admin.php';
            include 'views/admin/habitaciones/form.php';
            include 'views/layouts/footer_admin.php';
            return;
        }

        // Guardar habitación
        $habitacionData = [
            'numero' => $data['numero'],
            'tipo_habitacion' => $data['tipo_habitacion'],
            'capacidad' => $data['capacidad'],
            'precio_noche' => $data['precio_noche'],
            'imagen_principal' => $imagen_principal,
            'estado' => $data['estado'],
            'piso' => $data['piso'] ?? null,
            'metros_cuadrados' => $data['metros_cuadrados'] ?? null,
        ];

        $id = $this->model->crearHabitacion($habitacionData);
        if (!$id) {
            $errores['general'] = 'Error al guardar la habitación';
            include 'views/layouts/header_admin.php';
            include 'views/admin/habitaciones/form.php';
            include 'views/layouts/footer_admin.php';
            return;
        }

        // Guardar traducciones
        foreach ($idiomas as $lang) {
            $this->model->guardarTraduccion($id, $lang, [
                'nombre' => $data['nombre'][$lang] ?? '',
                'titulo' => $data['titulo'][$lang] ?? '',
                'resumen' => $data['resumen'][$lang] ?? '',
                'descripcion' => $data['descripcion'][$lang] ?? '',
                'imagen_url' => $data['imagen_url'][$lang] ?? '',
                'imagen_alt' => $data['imagen_alt'][$lang] ?? '',
            ]);
        }

        // Manejar imágenes adicionales (múltiples)
        if (isset($_FILES['imagenes_adicionales'])) {
            $files = $_FILES['imagenes_adicionales'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $fileArray = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];
                    $uploadResult = $this->subirImagen($fileArray);
                    if ($uploadResult['success']) {
                        $descripcion = $_POST['descripcion_imagenes'][$i] ?? '';
                        $orden = $i + 1;
                        $this->model->guardarImagen($id, $uploadResult['path'], $descripcion, $orden);
                    }
                }
            }
        }

        header('Location: index.php?controller=habitacionesAdmin&action=index&msg=created');
        exit;
    }

    // Mostrar formulario para editar habitación
    public function edit() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: index.php?controller=habitacionesAdmin&action=index');
            exit;
        }
        $habitacion = $this->model->obtenerHabitacion($id, $this->idioma);
        if (!$habitacion) {
            header('Location: index.php?controller=habitacionesAdmin&action=index');
            exit;
        }
        $tipos = $this->model->obtenerTiposHabitacion($this->idioma);
        $idiomas = ['es-AR', 'en-US'];
        $errores = [];
        $data = $habitacion;

        include 'views/layouts/header_admin.php';
        include 'views/admin/habitaciones/form.php';
        include 'views/layouts/footer_admin.php';
    }

    // Actualizar habitación
    public function update() {
        $id = $_POST['id'] ?? null;
        if (!$id) {
            header('Location: index.php?controller=habitacionesAdmin&action=index');
            exit;
        }
        $tipos = $this->model->obtenerTiposHabitacion($this->idioma);
        $idiomas = ['es-AR', 'en-US'];
        $errores = [];
        $data = $_POST;

        // Validaciones similares a store()
        if (empty($data['numero'])) {
            $errores['numero'] = 'El número es obligatorio';
        }
        if (empty($data['tipo_habitacion'])) {
            $errores['tipo_habitacion'] = 'El tipo de habitación es obligatorio';
        }
        if (empty($data['capacidad']) || !is_numeric($data['capacidad'])) {
            $errores['capacidad'] = 'La capacidad es obligatoria y debe ser numérica';
        }
        if (empty($data['precio_noche']) || !is_numeric($data['precio_noche'])) {
            $errores['precio_noche'] = 'El precio por noche es obligatorio y debe ser numérico';
        }
        if (empty($data['estado'])) {
            $errores['estado'] = 'El estado es obligatorio';
        }

        // Manejar imagen principal subida (opcional)
        $imagen_principal = $data['imagen_principal_actual'] ?? null;
        if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = $this->subirImagen($_FILES['imagen_principal']);
            if ($uploadResult['success']) {
                $imagen_principal = $uploadResult['path'];
            } else {
                $errores['imagen_principal'] = $uploadResult['error'];
            }
        }

        if (!empty($errores)) {
            $habitacion = $this->model->obtenerHabitacion($id, $this->idioma);
            $tipos = $this->model->obtenerTiposHabitacion($this->idioma);
            $idiomas = ['es-AR', 'en-US'];
            include 'views/layouts/header_admin.php';
            include 'views/admin/habitaciones/form.php';
            include 'views/layouts/footer_admin.php';
            return;
        }

        $habitacionData = [
            'numero' => $data['numero'],
            'tipo_habitacion' => $data['tipo_habitacion'],
            'capacidad' => $data['capacidad'],
            'precio_noche' => $data['precio_noche'],
            'imagen_principal' => $imagen_principal,
            'estado' => $data['estado'],
            'piso' => $data['piso'] ?? null,
            'metros_cuadrados' => $data['metros_cuadrados'] ?? null,
        ];

        $this->model->actualizarHabitacion($id, $habitacionData);

        // Guardar traducciones
        foreach ($idiomas as $lang) {
            $this->model->guardarTraduccion($id, $lang, [
                'nombre' => $data['nombre'][$lang] ?? '',
                'titulo' => $data['titulo'][$lang] ?? '',
                'resumen' => $data['resumen'][$lang] ?? '',
                'descripcion' => $data['descripcion'][$lang] ?? '',
                'imagen_url' => $data['imagen_url'][$lang] ?? '',
                'imagen_alt' => $data['imagen_alt'][$lang] ?? '',
            ]);
        }

        // Manejar imágenes adicionales (múltiples)
        if (isset($_FILES['imagenes_adicionales'])) {
            $files = $_FILES['imagenes_adicionales'];
            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $fileArray = [
                        'name' => $files['name'][$i],
                        'type' => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error' => $files['error'][$i],
                        'size' => $files['size'][$i],
                    ];
                    $uploadResult = $this->subirImagen($fileArray);
                    if ($uploadResult['success']) {
                        $descripcion = $_POST['descripcion_imagenes'][$i] ?? '';
                        $orden = $i + 1;
                        $this->model->guardarImagen($id, $uploadResult['path'], $descripcion, $orden);
                    }
                }
            }
        }

        header('Location: index.php?controller=habitacionesAdmin&action=index&msg=updated');
        exit;
    }

    // Eliminar habitación
    public function delete() {
        $id = $_GET['id'] ?? null;
        if ($id) {
            $this->model->eliminarHabitacion($id);
        }
        header('Location: index.php?controller=habitacionesAdmin&action=index&msg=deleted');
        exit;
    }

    // Eliminar imagen individual
    public function eliminarImagen() {
        $id = $_GET['id'] ?? null;
        $habitacion_id = $_GET['habitacion_id'] ?? null;
        if ($id && $habitacion_id) {
            $this->model->eliminarImagen($id);
            header("Location: editar.php?id=$habitacion_id&msg=img_deleted");
            exit;
        }

        header('Location: index.php?controller=habitacionesAdmin&action=index');
        exit;
    }

    // Función para subir imagen y guardarla en assets/images/habitaciones/
    private function subirImagen($file) {
        $allowedExtensions = ['jpg', 'jpeg', 'png'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB

        $fileName = $file['name'];
        $fileTmpPath = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];

        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if ($fileError !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Error al subir el archivo'];
        }

        if (!in_array($fileExtension, $allowedExtensions)) {
            return ['success' => false, 'error' => 'Solo se permiten archivos JPG y PNG'];
        }

        if ($fileSize > $maxFileSize) {
            return ['success' => false, 'error' => 'El archivo supera el tamaño máximo permitido (5MB)'];
        }

        $newFileName = 'habitacion_' . time() . '_' . bin2hex(random_bytes(5)) . '.' . $fileExtension;
        $uploadDir = 'assets/images/habitaciones/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $destPath = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $destPath)) {
            return ['success' => true, 'path' => $destPath];
        } else {
            return ['success' => false, 'error' => 'Error al mover el archivo subido'];
        }
    }
}
?>