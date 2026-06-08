<?php

namespace App\Models;

use PDO;
use PDOException;
use Exception;

class Conexion
{
    private static $conexion = null;

    public static function getConexion()
    {
        if (self::$conexion === null) {
            $env = self::cargarEnv();

            /*
             * Primero intenta leer variables reales del servidor:
             * Render usa variables de entorno.
             *
             * Si no existen, lee el archivo .env local.
             * Si tampoco existen, usa valores por defecto locales.
             */
            $host = getenv('DB_HOST') ?: ($env['DB_HOST'] ?? '127.0.0.1');
            $port = getenv('DB_PORT') ?: ($env['DB_PORT'] ?? '5432');
            $database = getenv('DB_DATABASE') ?: ($env['DB_DATABASE'] ?? 'sigie');
            $username = getenv('DB_USERNAME') ?: ($env['DB_USERNAME'] ?? 'postgres');
            $password = getenv('DB_PASSWORD') ?: ($env['DB_PASSWORD'] ?? '');

            $dsn = "pgsql:host={$host};port={$port};dbname={$database}";

            try {
                self::$conexion = new PDO($dsn, $username, $password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                throw new Exception('Error de conexión a PostgreSQL: ' . $e->getMessage());
            }
        }

        return self::$conexion;
    }

    private static function cargarEnv()
    {
        $rutaEnv = dirname(__DIR__, 2) . '/.env';
        $variables = [];

        if (!file_exists($rutaEnv)) {
            return $variables;
        }

        $lineas = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            if ($linea === '' || str_starts_with($linea, '#')) {
                continue;
            }

            if (!str_contains($linea, '=')) {
                continue;
            }

            [$clave, $valor] = explode('=', $linea, 2);

            $clave = trim($clave);
            $valor = trim($valor);

            $valor = trim($valor, '"');
            $valor = trim($valor, "'");

            $variables[$clave] = $valor;
        }

        return $variables;
    }
}