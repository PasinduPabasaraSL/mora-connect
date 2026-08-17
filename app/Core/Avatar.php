<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Turns an uploaded file into a profile picture.
 *
 * Everything here treats the upload as hostile. The browser's declared type and
 * the file name are both ignored: the type is read from the image data, and the
 * file is decoded and re-encoded rather than moved, which is what makes a
 * polyglot file — valid image with script bytes appended — harmless, because
 * only the pixels survive the round trip.
 */
final class Avatar
{
    /** Squares of this size, which covers a 128px display at 2x */
    private const SIZE = 256;

    private const MAX_BYTES = 4 * 1024 * 1024;

    /**
     * A hard ceiling on pixel count, checked before decoding. A small file can
     * describe an enormous image, and decoding it is what exhausts memory.
     */
    private const MAX_PIXELS = 30000000;

    /** @var array<int, string> GD image type => extension */
    private const TYPES = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF  => 'gif',
    ];

    public static function directory(): string
    {
        return ROOT_PATH . '/uploads/avatars';
    }

    /**
     * Validates and stores an upload, returning the stored file name.
     *
     * @param  array<string, mixed> $file one entry from $_FILES
     * @return array{name: ?string, error: ?string}
     */
    public static function store(array $file): array
    {
        $error = self::rejectionReason($file);

        if ($error !== null) {
            return ['name' => null, 'error' => $error];
        }

        /** @var string $tmp */
        $tmp = $file['tmp_name'];

        $info = @getimagesize($tmp);

        if ($info === false || !isset(self::TYPES[$info[2]])) {
            return ['name' => null, 'error' => 'That file is not a JPEG, PNG, WebP or GIF image.'];
        }

        [$width, $height] = $info;

        if ($width < 1 || $height < 1) {
            return ['name' => null, 'error' => 'That image appears to be empty.'];
        }

        if ($width * $height > self::MAX_PIXELS) {
            return ['name' => null, 'error' => 'That image has too many pixels to process. Try a smaller one.'];
        }

        $source = self::read($tmp, $info[2]);

        if ($source === null) {
            return ['name' => null, 'error' => 'That image could not be read. It may be damaged.'];
        }

        $square = self::square($source, $width, $height);

        imagedestroy($source);

        if (!is_dir(self::directory()) && !@mkdir(self::directory(), 0755, true)) {
            imagedestroy($square);

            return ['name' => null, 'error' => 'The server has nowhere to store the picture.'];
        }

        // The name is generated, never taken from the upload: a name chosen by
        // the person uploading is a path traversal waiting to happen.
        $name = bin2hex(random_bytes(16)) . '.jpg';
        $ok   = imagejpeg($square, self::directory() . '/' . $name, 88);

        imagedestroy($square);

        if (!$ok) {
            return ['name' => null, 'error' => 'The picture could not be saved. Please try again.'];
        }

        return ['name' => $name, 'error' => null];
    }

    /**
     * Why this upload cannot be accepted, or null if it can.
     *
     * @param array<string, mixed> $file
     */
    private static function rejectionReason(array $file): ?string
    {
        $code = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
            return 'That picture is too large. The limit is 4 MB.';
        }

        if ($code === UPLOAD_ERR_NO_FILE) {
            return 'Choose a picture to upload first.';
        }

        if ($code !== UPLOAD_ERR_OK) {
            return 'That picture did not finish uploading. Please try again.';
        }

        $tmp = $file['tmp_name'] ?? '';

        // Proves the path came from PHP's own upload handling rather than being
        // some other file on the server that a crafted request named.
        if (!is_string($tmp) || !is_uploaded_file($tmp)) {
            return 'That upload could not be verified.';
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_BYTES) {
            return 'That picture is too large. The limit is 4 MB.';
        }

        return null;
    }

    /**
     * @return \GdImage|null
     */
    private static function read(string $path, int $type)
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG  => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            IMAGETYPE_GIF  => @imagecreatefromgif($path),
            default        => false,
        };

        return $image === false ? null : $image;
    }

    /**
     * Centre-crops to a square and scales to SIZE.
     *
     * The crop is centred rather than stretched because a squashed face is worse
     * than a cropped one, and the result is flattened onto white so a
     * transparent PNG does not become a black square once it is JPEG.
     *
     * @param  \GdImage $source
     * @return \GdImage
     */
    private static function square($source, int $width, int $height)
    {
        $edge = min($width, $height);
        $x    = (int) (($width - $edge) / 2);
        $y    = (int) (($height - $edge) / 2);

        $canvas = imagecreatetruecolor(self::SIZE, self::SIZE);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledrectangle($canvas, 0, 0, self::SIZE, self::SIZE, $white);

        imagecopyresampled($canvas, $source, 0, 0, $x, $y, self::SIZE, self::SIZE, $edge, $edge);

        return $canvas;
    }

    /**
     * Removes a stored picture. Called when one is replaced or the account goes,
     * so old files do not accumulate for ever.
     */
    public static function delete(?string $name): void
    {
        if ($name === null || $name === '') {
            return;
        }

        // Guards against a stored value that somehow contains a path
        $safe = basename($name);
        $path = self::directory() . '/' . $safe;

        if ($safe === $name && is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * The public URL for a stored picture.
     */
    public static function url(string $name): string
    {
        return url('uploads/avatars/' . basename($name));
    }
}
