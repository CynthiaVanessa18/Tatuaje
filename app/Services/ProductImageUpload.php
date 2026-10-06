<?php
declare(strict_types=1);

final class ProductImageUpload
{
    public static function save(?array $file, string $folder = 'productos'): ?string
    {
        if (!in_array($folder, ['productos', 'perfiles'], true)) throw new DomainException('Carpeta de imágenes inválida.');
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return null;
        if (($file['error'] ?? null) !== UPLOAD_ERR_OK) {
            throw new DomainException('No se pudo subir la imagen. Elige un archivo de hasta 5 MB.');
        }
        $tmp = $file['tmp_name'] ?? '';
        if (!is_string($tmp) || !is_uploaded_file($tmp)) throw new DomainException('Archivo de imagen inválido.');
        if (filesize($tmp) > 5 * 1024 * 1024) throw new DomainException('La imagen no puede superar 5 MB.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        $extensions = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'image/webp'=>'webp'];
        if (!isset($extensions[$mime]) || !@getimagesize($tmp)) {
            throw new DomainException('Selecciona una imagen JPG, PNG o WebP válida.');
        }
        $directory = __DIR__.'/../../public/uploads/'.$folder;
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new DomainException('No se pudo preparar la carpeta de imágenes.');
        }
        $path = 'uploads/'.$folder.'/'.bin2hex(random_bytes(16)).'.'.$extensions[$mime];
        if (!move_uploaded_file($tmp, __DIR__.'/../../public/'.$path)) {
            throw new DomainException('No se pudo guardar la imagen. Intenta nuevamente.');
        }
        return $path;
    }

    public static function discard(string $path): void
    {
        if (preg_match('~^uploads/(?:productos|perfiles)/[a-f0-9]{32}\.(jpg|png|webp)$~D', $path)) {
            $absolute = __DIR__.'/../../public/'.$path;
            if (is_file($absolute)) unlink($absolute);
        }
    }
}
