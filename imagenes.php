<?php

/**
 * Imagen
 */
class Imagen
{
    private $img;
    private $mime;
    public static $MIME_JPG = 'image/jpeg';
    public static $MIME_PNG = 'image/png';

    public function __construct($path)
    {
        $this->img=imagenes_cargar($path);
        
        if($this->img){
            $this->mime=self::mime($path);
        }

        return $this->img;
    }

    public static function mime($path)
    {
        return imagenes_mime_type($path);
    }

    public function isJPG()
    {
        return $this->mime === Imagen::$MIME_JPG;
    }

    public function isPNG()
    {
        return $this->mime === Imagen::$MIME_PNG;
    }

    public static function isWEBP($path) {}

    public function marcaAgua($marca, $factor = 1, $pos = 4, $opacidad = 100)
    {
        __marca_agua($this->img, $marca, $factor, $pos, $opacidad);
    }

    public function redimensionar($width)
    {
        __cambiar_dimensiones($this->img, $width);
    }

    public function salvar($nombre)
    {
        if ($this->mime == 'image/jpeg') {
            return imagejpeg($this->img, $nombre);
        }
        return false;
    }

    public function stream()
    {
        header('content-type: '.$this->mime);
        if($this->isJPG())
        {
            imagejpeg($this->img);
        }elseif($this->isPNG()){
            imagepng($this->img);
        }
    }

    public function getMime()
    {
        return $this->mime;
    }

    protected function setImg($img)
    {
        $this->img = $img;
    }

    protected function setMime($mime)
    {
        $this->mime = $mime;
    }

    public function getW()
    {
        return imagesx($this->img);
    }

    public function getH()
    {
        return imagesy($this->img);
    }

    public function crear($width, $height) {}
}

function imagenes_mime_type($path)
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $tipo_mime = finfo_file($finfo, $path);
    finfo_close($finfo);
    if(in_array($tipo_mime, ['image/jpeg', 'image/png', 'image/webp'])){
        return $tipo_mime;
    }
    return false;
}

function isJPG($path)
{
    return imagenes_mime_type($path) === 'image/jpeg';
}

function isPNG($path)
{
    return imagenes_mime_type($path) === 'image/png';
}

function isWEBP($path)
{
    return imagenes_mime_type($path) === 'image/webp';
}

function imagenes_cargar($path)
{
    if(isJPG($path))
    {
        return imagecreatefromjpeg($path);
    }else if(isPNG($path))
    {
        return imagecreatefrompng($path);
    }
    return false;
}

/**
 * crear_miniatura
 *
 * @param  mixed $photo
 * @param  mixed $thumbnailPath
 * @return void
 */
function imagenes_cambiar_dimensiones($path, $thumbnailPath, $thumbWidth = 300)
{
    $img = imagenes_cargar($path);
    $tmp_img =__cambiar_dimensiones($img, $thumbWidth);
    imagejpeg($tmp_img, $thumbnailPath);
}

function __cambiar_dimensiones($img, $w)
{
    // load image and get image size
    $width = imagesx($img);
    $height = imagesy($img);

    // calculate thumbnail size
    $new_width = $w;
    $new_height = floor($height * ($w / $width));

    // create a new temporary image
    $tmp_img = imagecreatetruecolor($new_width, $new_height);

    // copy and resize old image into new image
    imagecopyresized($tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    return $tmp_img;
}

function imagenes_jpg2png($fichero_jpg, $salida)
{
    $imagen_jpg = imagecreatefromjpeg($fichero_jpg);
    imagepng($imagen_jpg, $salida);
    imagedestroy($imagen_jpg);
}

function imagenes_png2jpg($fichero_png, $salida)
{
    $imagen_png = imagecreatefrompng($fichero_png);
    imagepng($imagen_png, $salida);
    imagedestroy($imagen_png);
}

function imagenes_webp2jpg($fichero_webp, $salida)
{
    $imagen_jpg = imagecreatefromwebp($fichero_webp);
    imagepng($imagen_jpg, $salida);
    imagedestroy($imagen_jpg);
}

/**
 * marca_agua
 *
 * @param  mixed $origen
 * @param  mixed $destino
 * @param  mixed $marca
 * @param  mixed $factor
 * @param  mixed $pos
 * @param  mixed $opacidad
 * @return void
 */
function imagenes_marca_agua($origen, $destino, $marca, $factor = 1, $pos = 4, $opacidad = 100)
{
    $image = imagecreatefromjpeg($origen);

    __marca_agua($image, $marca, $factor, $pos, $opacidad);

    imagejpeg($image, $destino);
    imagedestroy($image);
}

function __marca_agua(&$image, $marca, $factor, $pos, $opacidad)
{
    $watermark = imagecreatefrompng($marca);

    if ($factor >= 1) {
        $watermark_width = imagesx($watermark);
        $watermark_height = imagesy($watermark);
        $new_height = intval($watermark_height * $factor);
        $new_width = intval($watermark_width * $factor);
        $tmp_img = imagecreatetruecolor($new_width, $new_height);

        // Copia con transparencia
        imagealphablending($tmp_img, false);
        imagesavealpha($tmp_img, true);
        imagecopyresized($tmp_img, $watermark, 0, 0, 0, 0, $new_width, $new_height, $watermark_width, $watermark_height);

        imagedestroy($watermark);
        $watermark = $tmp_img;
    }

    // Modifica opacidad
    imagefilter($watermark, IMG_FILTER_COLORIZE, 0, 0, 0, 127 * (100 - $opacidad) / 100);
    $watermark_width = imagesx($watermark);
    $watermark_height = imagesy($watermark);

    if ($pos == 1) {
        $dest_x = 20;
        $dest_y = 20;
    } elseif ($pos == 2) {
        $dest_x = imagesx($image) - $watermark_width - 20;
        $dest_y = 20;
    } elseif ($pos == 3) {
        $dest_x = 20;
        $dest_y = imagesy($image) - $watermark_height - 20;
    } else {
        $dest_x = imagesx($image) - $watermark_width - 20;
        $dest_y = imagesy($image) - $watermark_height - 20;
    }

    imagecopy($image, $watermark, $dest_x, $dest_y, 0, 0, $watermark_width, $watermark_height);
    imagedestroy($watermark);
}
