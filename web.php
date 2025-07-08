<?php


function sortable($text, $field, $default = false)
{
    $q = $_SERVER['QUERY_STRING'];
    parse_str($q, $params);
    $icon = "sort-down";

    if (isset($params['orden']) and $params['orden'] == $field) {
        if (isset($params['orden_dir']) and $params['orden_dir'] == 'asc') {
            $icon = "sort-up";
        }
        if (isset($params['orden_dir']) and $params['orden_dir'] == 'desc') {
            $icon = "sort-down";
        }
        $params['orden_dir'] = isset($_GET['orden_dir']) ? ($_GET['orden_dir'] == 'desc' ? 'asc' : 'desc') : 'asc';
    } else {
        $icon = "sort";
        $params['orden_dir'] = 'asc';
    }

    if ($default and !isset($params['orden'])) {
        $icon = "sort-up";
        $params['orden_dir'] = 'desc';
    }

    $params['orden'] = $field;
    if (isset($params['p'])) {
        unset($params['p']);
    }

    $parts = parse_url($_SERVER['REQUEST_URI']);
    $url = $parts['path'] . '?' . http_build_query($params);
    $html = "<a href=\"{$url}\">{$text}</a> <i class=\"fas fa-{$icon}\"></i>";

    return $html;
}

function addQueryParameter($param, $value)
{
    $q = $_SERVER['QUERY_STRING'];
    parse_str($q, $params);
    $params[$param] = $value;

    return $_SERVER['PHP_SELF'] . '?' . http_build_query($params);
}

function valor_array($campo, $array = null)
{
    return is_array($array) ? ($array[$campo] ?? '') : '';
}

function check_access()
{
    if (!isset($_SESSION['usuario'])) {
        redirect(ruta('login'));
    }
}

function print_array($array)
{
    foreach ($array as $item) {

        foreach ($item as $k => $v) {
            echo "$v\t";
        }
        echo PHP_EOL;
    }
}

/**
 * ruta
 *
 * @param  mixed $r
 * @param  mixed $idioma
 * @return void
 */
function ruta($r, $usarParams = false, $params = null, $delParams = null)
{
    $url = URL_BASE . $r;

    if ($usarParams) {
        $parts = parse_url($_SERVER['REQUEST_URI']);
        $lastParams = [];
        if(isset($parts['query'])){
            parse_str($parts['query'], $lastParams);
        }
        if ($params) {
            foreach ($params as $p => $v) {
                $lastParams[$p] = $v;
            }
        }
        
        if ($delParams and !empty($lastParams)) {
            foreach ($delParams as $p) {
                unset($lastParams[$p]);
            }
        }       
        if(!empty($lastParams)){ 
        $url .= '?' . http_build_query($lastParams);
        }
        //print_r($lastParams);
    }

    return $url;
}

/**
 * redirect
 *
 * @param  mixed $url
 * @return void
 */
function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function cargar_vista($vista, $plantilla, $titulo="", $vars=[]) {

}

/**
 * sendRequest
 *
 * @param  mixed $url
 * @param  mixed $data
 * @param  mixed $method
 * @param  mixed $lang
 * @return void
 */
function sendRequest($url, $data, $method = 'POST', $lang = 'es')
{
    if ($method == 'POST') {
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => $method,
                'content' => http_build_query($data),
            ]
        ];
    } else {
        $options = [
            'http' => [
                'method' => $method,
                // Use CRLF \r\n to separate multiple headers
                'header' => "Accept-language: $lang\r\n"
            ]
        ];
    }

    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

/**
 * cargar_textos_idioma
 *
 * @param  mixed $fichero
 * @param  mixed $idioma
 * @return void
 */
function cargar_textos_idioma($fichero, $idioma)
{
    global $_textos;
    $path = __DIR__ . "/lang/$idioma/$fichero.ini";
    if (file_exists($path)) {
        $_textos = array_merge($_textos, parse_ini_file($path));
    }
}

/**
 * idioma_actual
 *
 * @return void
 */
function idioma_actual()
{
    global $_idioma;
    return $_idioma;
}

/**
 * t
 *
 * @param  mixed $nombre
 * @return void
 */
function t($nombre)
{
    global $_textos;

    if (isset($_textos[$nombre])) {
        return $_textos[$nombre];
    } else {
        return $nombre;
    }
}


/**
 * procesarUrl
 *
 * @param  mixed $routes
 * @return void
 */
function procesarUrl($routes)
{
    $uri = $_SERVER['REQUEST_URI'];
    //$idiomasValidos=['es'=>'es_ES', 'en'=>'en_GB', 'fr', 'ca'];

    // Procesamos la URL
    if (strpos($uri, URL_BASE) === 0) {
        $uri = substr($uri, strlen(URL_BASE));
    }
    $uri = trim(parse_url($uri)['path'], '/');
    $segmentos = explode('/', $uri);

    /*
    $idioma = 'es';

    if (!empty($segmentos[0]) && in_array($segmentos[0], array_keys($idiomasValidos))) {
        $idioma = array_shift($segmentos);
        if(empty($segmentos))
        {
          $segmentos[]='';
        }
    }
    */
    // Busqueda en las rutas definidas en rutas.php
    $metodoHttp = strtolower($_SERVER['REQUEST_METHOD']);
    if (!isset($routes[$metodoHttp])) {
        return ['error' => 'Método HTTP no soportado'];
    }

    foreach ($routes[$metodoHttp] as $ruta => $config) {
        $segmentosRuta = explode('/', $ruta);
        if (count($segmentosRuta) !== count($segmentos)) continue;

        $params = [];
        $match = true;

        foreach ($segmentosRuta as $i => $parte) {
            if (preg_match('/^{([a-zA-Z]+)}$/', $parte, $matches)) {
                $params[$matches[1]] = $segmentos[$i];
            } elseif ($parte !== $segmentos[$i]) {
                $match = false;
                break;
            }
        }

        if ($match) {

            return [
                'ruta' => $ruta,
                'config' => $config,
                'params' => $params
            ];
        }
    }

    return ['error' => 'No se encontró la ruta'];
}
