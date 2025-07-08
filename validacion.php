<?php

/**
 * Validador
 * Clase wrapper para validar datos de un formulario
 */
class Validador
{
    public $campos = [];
    private $method;

    public function __construct()
    {
    }

    /**
     * add
     *
     * @param  mixed $campo
     * @param  mixed $reglas
     * @return void
     */
    public function add($campo, $regla, $mensaje = null)
    {
        $this->campos[$campo]['reglas'][] = $regla;
        if ($mensaje) {
            $this->campos[$campo]['mensajes'][$regla] = $mensaje;
        }
    }

    /**
     * validar
     *
     * @return array
     */
    public function validar($formulario): array|false
    {
        if (!empty($this->campos)) {
            return validar_formulario($formulario, $this->campos);
        } else {
            return false;
        }
    }
}

/**
 * Validación de formularios
 */
function validar_formulario($datosFormulario, $campos)
{
    $errores = [];
    $valores = [];

    foreach ($campos as $campo => $config) {
        // Limpiar el valor antes de validar
        $valor = $valores[$campo] = trim(htmlspecialchars($datosFormulario[$campo] ?? $config['defecto']));

        $reglas = $config['reglas'] ?? [];
        $mensajes = $config['mensajes'] ?? [];

        foreach ($reglas as $regla) {
            // Mensaje predeterminado o personalizado
            $mensaje = $mensajes[$regla] ?? null;

            if ($regla === 'requerido') {
                if (empty($valor)) {
                    $errores[$campo][] = $mensaje ?: "El campo '$campo' es obligatorio.";
                }
            } elseif ($regla === 'email') {
                if (!empty($valor) && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    $errores[$campo][] = $mensaje ?: "El correo electrónico no es válido.";
                }
            } elseif ($regla === 'numero') {
                if (!empty($valor) && !is_numeric($valor)) {
                    $errores[$campo][] = $mensaje ?: "El valor debe ser un número entero.";
                }else{
                    $valores[$campo]=intval($valores[$campo]);
                }
            } elseif ($regla === 'decimal') {
                if (!empty($valor) && !is_numeric($valor)) {
                    $errores[$campo][] = $mensaje ?: "El valor debe ser un número (entero o decimal).";
                }
            } elseif (str_starts_with($regla, 'min:')) {
                $min = substr($regla, 4);
                if (strlen($valor) < $min) {
                    $msg = $mensaje ?: "Debe tener al menos $min caracteres.";
                    $errores[$campo][] = $msg;
                }
            } elseif (str_starts_with($regla, 'max:')) {
                $max = substr($regla, 4);
                if (strlen($valor) > $max) {
                    $msg = $mensaje ?: "No puede tener más de $max caracteres.";
                    $errores[$campo][] = $msg;
                }
            } elseif (str_starts_with($regla, 'confirmar:')) {
                $otroCampo = substr($regla, 10);
                $valorOtro = $datosFormulario[$otroCampo] ?? '';

                if ($valor !== trim(htmlspecialchars($valorOtro))) {
                    $msg = $mensaje ?: "Los valores no coinciden.";
                    $errores[$campo][] = $msg;
                }
            }elseif (str_starts_with($regla, 'en:')) {
                $valoresPermitidos = array_map('trim', explode(',', substr($regla, 3)));
                if (!empty($valor) && !in_array($valor, $valoresPermitidos)) {
                    $msg = $mensaje ?: "El valor '$valor' no es válido para el campo '$campo'.";
                    $errores[$campo][] = $msg;
                }
            }elseif ($regla === 'password') {
                // Regla 'password' completa: requerido, mínimo 8, alfanumérico
                if (empty($valor)) {
                    $errores[$campo][] = $mensaje ?: "La contraseña es obligatoria.";
                } elseif (strlen($valor) < 8) {
                    $errores[$campo][] = $mensaje ?: "La contraseña debe tener al menos 8 caracteres.";
                } elseif (!preg_match('/(?=^.{8,}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$/', $valor)) {
                    $errores[$campo][] = $mensaje ?: "La contraseña debe contener letras mayúsculas, minúsculas y números.";
                }
            }
        }
    }

    return [
        'valido' => empty($errores),
        'errores' => $errores,
        'valores' => $valores
    ];
}
