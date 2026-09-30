<?php

declare(strict_types=1);

namespace App\Servicios;

use finfo;
use InvalidArgumentException;
use RuntimeException;

/** Valida el contenido de las imágenes subidas y administra sus archivos. [SEGURIDAD] [VALIDACION] */
final class GestorImagenes
{
    private const MAX_BYTES = 2 * 1024 * 1024;
    private const EXTENSIONES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    public function __construct(private readonly string $directorio)
    {
    }

    /** @param array<string,mixed> $archivo Devuelve null si la subida es válida. */
    public function validar(array $archivo, bool $obligatoria): ?string
    {
        $error = $archivo['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return $obligatoria ? 'Selecciona una imagen JPG, PNG o WEBP.' : null;
        }
        if (!is_int($error) || $error !== UPLOAD_ERR_OK) {
            return 'No se pudo recibir la imagen. Revisa que no supere 2 MB.';
        }

        $temporal = $archivo['tmp_name'] ?? null;
        $tamano = $archivo['size'] ?? null;
        if (!is_string($temporal) || !is_file($temporal) || !is_int($tamano)
            || $tamano < 1 || $tamano > self::MAX_BYTES || filesize($temporal) > self::MAX_BYTES) {
            return 'La imagen debe pesar entre 1 byte y 2 MB.';
        }

        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);
        if (!isset(self::EXTENSIONES[$mime]) || getimagesize($temporal) === false) {
            return 'El archivo debe ser una imagen JPG, PNG o WEBP real.';
        }
        return null;
    }

    /** @param array<string,mixed> $archivo Devuelve el nombre generado o null si la subida opcional está vacía. */
    public function guardar(array $archivo, bool $obligatoria = true): ?string
    {
        $error = $this->validar($archivo, $obligatoria);
        if ($error !== null) {
            throw new InvalidArgumentException($error);
        }
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        $temporal = $archivo['tmp_name'];
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporal);
        $nombre = bin2hex(random_bytes(16)) . '.' . self::EXTENSIONES[$mime];
        if (!is_dir($this->directorio) && !mkdir($this->directorio, 0755, true) && !is_dir($this->directorio)) {
            throw new RuntimeException('No se pudo crear el directorio de imágenes.');
        }
        if (!move_uploaded_file($temporal, $this->directorio . '/' . $nombre)) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }
        return $nombre;
    }

    public function eliminar(?string $nombre): void
    {
        if ($nombre === null || !preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/D', $nombre)) {
            return;
        }
        $ruta = $this->directorio . '/' . $nombre;
        if (is_file($ruta) && !unlink($ruta)) {
            error_log('No se pudo eliminar una imagen del catálogo: ' . $nombre);
        }
    }
}
