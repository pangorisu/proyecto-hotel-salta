<?php
require_once 'models/IntroduccionModel.php';

class IntroduccionController {
    private $model;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->model = new IntroduccionModel($conn);
    }

    public function index($lang) {
        $contenido = $this->model->obtenerIntroduccion($lang);

        if (!$contenido) {
            $contenido = [
                'titulo' => 'Contenido no disponible',
                'subtitulo' => '',
                'cuerpo' => 'Información no disponible en este idioma.',
                'imagen_url' => '',
                'imagen_alt' => ''
            ];
        }
        $conn = $this->conn; // pasa conexión

        //include 'views/layouts/header.php';
        include 'views/pages/introduccion.php';
        //include 'views/layouts/footer.php';
    }
}
?>