<?php

function copiar_directorio($origen, $destino)
{
    // Verificar que el directorio de origen exista
    if (!is_dir($origen)) {
        throw new InvalidArgumentException("El directorio de origen no existe: $origen");
    }

    // Crear el directorio de destino si no existe
    if (!is_dir($destino)) {
        mkdir($destino, 0777, true);
    }

    // Abrir el directorio de origen
    $directorio = opendir($origen);
    while (false !== ($item = readdir($directorio))) {
        // Ignorar los directorios especiales "." y ".."
        if ($item == '.' || $item == '..') {
            continue;
        }

        $rutaOrigen = $origen . '/' . $item;
        $rutaDestino = $destino . '/' . $item;

        if (is_dir($rutaOrigen)) {
            // Si es un directorio, llamar recursivamente
            copiar_directorio($rutaOrigen, $rutaDestino);
        } else {
            // Si es un archivo, copiarlo
            copy($rutaOrigen, $rutaDestino);
        }
    }
    closedir($directorio);
}

function comprimirDirectorio($rutaDirectorio, $archivoZip)
{
    // Verificar que el directorio exista
    if (!is_dir($rutaDirectorio)) {
        throw new InvalidArgumentException("El directorio no existe: $rutaDirectorio");
    }

    // Crear el objeto ZipArchive
    $zip = new ZipArchive();
    if ($zip->open($archivoZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        throw new RuntimeException("No se pudo crear el archivo ZIP: $archivoZip");
    }

    // Función recursiva para agregar archivos y carpetas
    $iterador = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rutaDirectorio, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($iterador as $archivo) {
        // Solo agregar archivos (no directorios)
        if ($archivo->isFile()) {
            $rutaRelativa = substr($archivo->getPathname(), strlen($rutaDirectorio) + 1);
            $zip->addFile($archivo->getPathname(), $rutaRelativa);
        }
    }

    // Cerrar el archivo ZIP
    $zip->close();
}


function borrarDirectorio($carpeta) {
    if (!is_dir($carpeta)) {
        throw new InvalidArgumentException("La ruta no es un directorio: $carpeta");
    }

    $archivos = scandir($carpeta);
    foreach ($archivos as $archivo) {
        if ($archivo == '.' || $archivo == '..') {
            continue;
        }

        $rutaCompleta = $carpeta . DIRECTORY_SEPARATOR . $archivo;

        if (is_dir($rutaCompleta)) {
            // Si es un subdirectorio, llamar recursivamente
            borrarDirectorio($rutaCompleta);
        } else {
            // Si es un archivo, borrarlo
            unlink($rutaCompleta);
        }
    }

    // Ahora que está vacío, borrar el directorio
    rmdir($carpeta);
}
