<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/La_Paz');

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/app/Models/Conexion.php';
require_once BASE_PATH . '/app/Models/Usuario.php';
require_once BASE_PATH . '/app/Models/Rol.php';
require_once BASE_PATH . '/app/Models/PeriodoAcademico.php';
require_once BASE_PATH . '/app/Models/Carrera.php';
require_once BASE_PATH . '/app/Models/Postulante.php';
require_once BASE_PATH . '/app/Models/Documento.php';
require_once BASE_PATH . '/app/Models/Pago.php';
require_once BASE_PATH . '/app/Models/Inscripcion.php';
require_once BASE_PATH . '/app/Models/GrupoAcademico.php';
require_once BASE_PATH . '/app/Models/ResultadoFinal.php';
require_once BASE_PATH . '/app/Models/Docente.php';
require_once BASE_PATH . '/app/Models/PlanificacionHorario.php';
require_once BASE_PATH . '/app/Models/ConflictoHorario.php';

require_once BASE_PATH . '/app/Http/Controllers/Auth/AuthController.php';
require_once BASE_PATH . '/app/Http/Controllers/DashboardController.php';
require_once BASE_PATH . '/app/Http/Controllers/UsuarioController.php';
require_once BASE_PATH . '/app/Http/Controllers/RolController.php';
require_once BASE_PATH . '/app/Http/Controllers/CarreraController.php';
require_once BASE_PATH . '/app/Http/Controllers/PostulanteController.php';
require_once BASE_PATH . '/app/Http/Controllers/DocumentoController.php';
require_once BASE_PATH . '/app/Http/Controllers/PagoController.php';
require_once BASE_PATH . '/app/Http/Controllers/InscripcionController.php';
require_once BASE_PATH . '/app/Http/Controllers/GrupoAcademicoController.php';
require_once BASE_PATH . '/app/Http/Controllers/ResultadoFinalController.php';
require_once BASE_PATH . '/app/Http/Controllers/DocenteController.php';
require_once BASE_PATH . '/app/Http/Controllers/PlanificacionHorarioController.php';
require_once BASE_PATH . '/app/Http/Controllers/ConflictoHorarioController.php';

if (!function_exists('e')) {
    function e($valor)
    {
        return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url($path = '/')
    {
        $path = trim($path);

        if ($path === '' || $path === '/') {
            return '/index.php';
        }

        $queryString = '';

        if (strpos($path, '?') !== false) {
            [$path, $queryString] = explode('?', $path, 2);
        } elseif (strpos($path, '&') !== false) {
            [$path, $queryString] = explode('&', $path, 2);
        }

        $path = '/' . ltrim($path, '/');

        $url = '/index.php?url=' . rawurlencode($path);

        if ($queryString !== '') {
            $url .= '&' . $queryString;
        }

        return $url;
    }
}

if (!function_exists('redirect')) {
    function redirect($path)
    {
        header('Location: ' . url($path));
        exit;
    }
}

if (!function_exists('set_flash')) {
    function set_flash($tipo, $mensaje)
    {
        $_SESSION['flash'][$tipo] = $mensaje;
    }
}

if (!function_exists('flash')) {
    function flash($tipo)
    {
        if (isset($_SESSION['flash'][$tipo])) {
            $mensaje = $_SESSION['flash'][$tipo];
            unset($_SESSION['flash'][$tipo]);
            return $mensaje;
        }

        return null;
    }
}

if (!function_exists('keep_old')) {
    function keep_old($datos)
    {
        $old = $datos;
        unset($old['password']);
        $_SESSION['old'] = $old;
    }
}

if (!function_exists('old')) {
    function old($campo, $default = '')
    {
        return $_SESSION['old'][$campo] ?? $default;
    }
}

if (!function_exists('is_active')) {
    function is_active($valor)
    {
        if (is_bool($valor)) {
            return $valor;
        }

        $valor = strtolower((string)$valor);

        return in_array($valor, ['1', 't', 'true', 'activo', 'active', 'si', 'sí'], true);
    }
}

if (!function_exists('selected_value')) {
    function selected_value($actual, $esperado)
    {
        return (string)$actual === (string)$esperado ? 'selected' : '';
    }
}

if (!function_exists('checked_value')) {
    function checked_value($valor)
    {
        return is_active($valor) ? 'checked' : '';
    }
}

if (!function_exists('format_date')) {
    function format_date($fecha)
    {
        if (empty($fecha)) {
            return '-';
        }

        return date('d/m/Y H:i', strtotime($fecha));
    }
}

if (!function_exists('format_date_short')) {
    function format_date_short($fecha)
    {
        if (empty($fecha)) {
            return '-';
        }

        return date('d/m/Y', strtotime($fecha));
    }
}

if (!function_exists('current_path')) {
    function current_path()
    {
        if (isset($_GET['url'])) {
            $path = $_GET['url'];
        } else {
            $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        }

        $path = '/' . trim($path, '/');

        if ($path === '/index.php' || $path === '/index.php/') {
            $path = '/';
        }

        return $path;
    }
}

if (!function_exists('require_login')) {
    function require_login()
    {
        if (!isset($_SESSION['usuario'])) {
            set_flash('error', 'Debes iniciar sesión para acceder al sistema.');
            redirect('/login');
        }
    }
}

if (!function_exists('has_role')) {
    function has_role($rolesPermitidos)
    {
        $rolesPermitidos = (array)$rolesPermitidos;
        $rolesUsuario = $_SESSION['usuario']['roles'] ?? [];

        foreach ($rolesPermitidos as $rol) {
            if (in_array($rol, $rolesUsuario, true)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('view')) {
    function view($vista, $datos = [])
    {
        $rutaVista = BASE_PATH . '/resources/views/' . str_replace('.', '/', $vista) . '.blade.php';

        if (!file_exists($rutaVista)) {
            http_response_code(500);
            echo 'Error: no existe la vista ' . e($vista);
            exit;
        }

        extract($datos);

        $titulo = $datos['titulo'] ?? 'SIGIE';

        ob_start();
        include $rutaVista;
        $contenido = ob_get_clean();

        include BASE_PATH . '/resources/views/layouts/app.blade.php';

        unset($_SESSION['old']);
    }
}

$method = $_SERVER['REQUEST_METHOD'];
$path = current_path();

$routes = [
    'GET' => [
        '/' => [\App\Http\Controllers\Auth\AuthController::class, 'loginForm'],
        '/login' => [\App\Http\Controllers\Auth\AuthController::class, 'loginForm'],
        '/logout' => [\App\Http\Controllers\Auth\AuthController::class, 'logout'],

        '/dashboard' => [\App\Http\Controllers\DashboardController::class, 'index'],

        '/usuarios' => [\App\Http\Controllers\UsuarioController::class, 'index'],
        '/usuarios/create' => [\App\Http\Controllers\UsuarioController::class, 'create'],
        '/usuarios/edit' => [\App\Http\Controllers\UsuarioController::class, 'edit'],
        '/usuarios/cambiar-estado' => [\App\Http\Controllers\UsuarioController::class, 'cambiarEstado'],

        '/roles' => [\App\Http\Controllers\RolController::class, 'index'],
        '/roles/create' => [\App\Http\Controllers\RolController::class, 'create'],
        '/roles/edit' => [\App\Http\Controllers\RolController::class, 'edit'],

        '/carreras' => [\App\Http\Controllers\CarreraController::class, 'index'],
        '/carreras/show' => [\App\Http\Controllers\CarreraController::class, 'show'],
        '/carreras/create' => [\App\Http\Controllers\CarreraController::class, 'create'],
        '/carreras/edit' => [\App\Http\Controllers\CarreraController::class, 'edit'],
        '/carreras/cupos' => [\App\Http\Controllers\CarreraController::class, 'cupos'],

        '/postulantes' => [\App\Http\Controllers\PostulanteController::class, 'index'],
        '/postulantes/show' => [\App\Http\Controllers\PostulanteController::class, 'show'],
        '/postulantes/create' => [\App\Http\Controllers\PostulanteController::class, 'create'],
        '/postulantes/edit' => [\App\Http\Controllers\PostulanteController::class, 'edit'],

        '/documentos' => [\App\Http\Controllers\DocumentoController::class, 'index'],
        '/documentos/show' => [\App\Http\Controllers\DocumentoController::class, 'show'],

        '/pagos' => [\App\Http\Controllers\PagoController::class, 'index'],
        '/pagos/show' => [\App\Http\Controllers\PagoController::class, 'show'],
        '/pagos/create' => [\App\Http\Controllers\PagoController::class, 'create'],

        '/inscripciones' => [\App\Http\Controllers\InscripcionController::class, 'index'],
        '/inscripciones/show' => [\App\Http\Controllers\InscripcionController::class, 'show'],

        '/grupos-academicos' => [\App\Http\Controllers\GrupoAcademicoController::class, 'index'],
        '/grupos-academicos/create' => [\App\Http\Controllers\GrupoAcademicoController::class, 'create'],

        '/resultados' => [\App\Http\Controllers\ResultadoFinalController::class, 'index'],
        '/resultados/show' => [\App\Http\Controllers\ResultadoFinalController::class, 'show'],

        '/docentes' => [\App\Http\Controllers\DocenteController::class, 'index'],
        '/docentes/show' => [\App\Http\Controllers\DocenteController::class, 'show'],
        '/docentes/create' => [\App\Http\Controllers\DocenteController::class, 'create'],
        '/docentes/edit' => [\App\Http\Controllers\DocenteController::class, 'edit'],

        '/planificacion-horaria' => [\App\Http\Controllers\PlanificacionHorarioController::class, 'index'],
        '/planificacion-horaria/show' => [\App\Http\Controllers\PlanificacionHorarioController::class, 'show'],
        '/planificacion-horaria/create' => [\App\Http\Controllers\PlanificacionHorarioController::class, 'create'],
        '/planificacion-horaria/edit' => [\App\Http\Controllers\PlanificacionHorarioController::class, 'edit'],

        '/conflictos-horarios' => [\App\Http\Controllers\ConflictoHorarioController::class, 'index'],
    ],

    'POST' => [
        '/login' => [\App\Http\Controllers\Auth\AuthController::class, 'login'],

        '/usuarios/store' => [\App\Http\Controllers\UsuarioController::class, 'store'],
        '/usuarios/update' => [\App\Http\Controllers\UsuarioController::class, 'update'],

        '/roles/store' => [\App\Http\Controllers\RolController::class, 'store'],
        '/roles/update' => [\App\Http\Controllers\RolController::class, 'update'],

        '/carreras/store' => [\App\Http\Controllers\CarreraController::class, 'store'],
        '/carreras/update' => [\App\Http\Controllers\CarreraController::class, 'update'],
        '/carreras/actualizar-cupo' => [\App\Http\Controllers\CarreraController::class, 'actualizarCupo'],

        '/postulantes/store' => [\App\Http\Controllers\PostulanteController::class, 'store'],
        '/postulantes/update' => [\App\Http\Controllers\PostulanteController::class, 'update'],

        '/documentos/update' => [\App\Http\Controllers\DocumentoController::class, 'update'],

        '/pagos/store' => [\App\Http\Controllers\PagoController::class, 'store'],
        '/pagos/cambiar-estado' => [\App\Http\Controllers\PagoController::class, 'cambiarEstado'],
        '/pagos/generar-cuenta' => [\App\Http\Controllers\PagoController::class, 'generarCuenta'],

        '/inscripciones/store' => [\App\Http\Controllers\InscripcionController::class, 'store'],

        '/grupos-academicos/store' => [\App\Http\Controllers\GrupoAcademicoController::class, 'store'],
        '/grupos-academicos/recalcular' => [\App\Http\Controllers\GrupoAcademicoController::class, 'recalcular'],
        '/grupos-academicos/cambiar-estado' => [\App\Http\Controllers\GrupoAcademicoController::class, 'cambiarEstado'],

        '/resultados/generar' => [\App\Http\Controllers\ResultadoFinalController::class, 'generar'],

        '/docentes/store' => [\App\Http\Controllers\DocenteController::class, 'store'],
        '/docentes/update' => [\App\Http\Controllers\DocenteController::class, 'update'],

        '/planificacion-horaria/store' => [\App\Http\Controllers\PlanificacionHorarioController::class, 'store'],
        '/planificacion-horaria/update' => [\App\Http\Controllers\PlanificacionHorarioController::class, 'update'],
    ],
];

if (isset($routes[$method][$path])) {
    [$controllerClass, $action] = $routes[$method][$path];

    $controller = new $controllerClass();
    $controller->$action();
    exit;
}

http_response_code(404);

$titulo = 'Página no encontrada';
$contenido = '
<div class="alert alert-danger">
    <h4 class="mb-2">404 - Página no encontrada</h4>
    <p class="mb-0">La ruta solicitada no existe dentro del sistema SIGIE.</p>
</div>';

include BASE_PATH . '/resources/views/layouts/app.blade.php';