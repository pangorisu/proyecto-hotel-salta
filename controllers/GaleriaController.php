<?php
require_once 'models/GaleriaModel.php';

class GaleriaController {
    private $model;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->model = new GaleriaModel($conn);
    }

    public function index($lang) {
        global $conn;
        $contenido = $this->model->obtenerIntroduccion($lang);

        if (!$contenido) {
            $contenido = [
                'titulo' => 'Contenido no disponible',
                'subtitulo' => '',
                'cuerpo' => 'Información no disponible en este idioma.'
            ];
        }

        // Obtener imágenes de la carpeta assets/images/galeria
        $imagenes = [];
        $rutaGaleria = 'assets/images/galeria/';
        if (is_dir($rutaGaleria)) {
            $archivos = scandir($rutaGaleria);
            foreach ($archivos as $archivo) {
                if (in_array(strtolower(pathinfo($archivo, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif'])) {
                    $imagenes[] = $rutaGaleria . $archivo;
                }
            }
        }

        include 'views/layouts/header.php';
        include 'views/pages/galeria.php';
        include 'views/layouts/footer.php';
    }
}
?>