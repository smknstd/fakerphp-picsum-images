<?php

namespace Smknstd\FakerPicsumImages;

use Faker\Provider\Base as BaseProvider;
use InvalidArgumentException;

class FakerPicsumImagesProvider extends BaseProvider
{
    public const JPG_IMAGE = 'jpg';
    public const WEBP_IMAGE = 'webp';

    private static array $IMAGE_EXTENSIONS = [
        self::JPG_IMAGE,
        self::WEBP_IMAGE,
    ];

    protected static string $baseUrl = "https://picsum.photos/";

    public static function imageUrl(
        int $width = 640,
        int $height = 480,
        int $id = null,
        bool $randomize = true,
        bool $gray = false,
        int $blur = null,
        string $imageExtension = null
    ): string {
        $url = '';
        if ($id) {
            $url = 'id/' . $id . '/';
        }
        $url .= "{$width}/{$height}";
        $queryString = self::buildQueryString($gray, $blur, $randomize);

        return self::buildPicsumUrl($url, $queryString, $imageExtension);
    }

    /**
     * Download a remote image from picsum api to disk and return its filename/path
     *
     * Requires curl, or allow_url_fopen to be on in php.ini.
     *
     * @example '/path/to/dir/13b73edae8443990be1aa8f1a483bc27.jpg'
     */
    public static function image(
        string $dir = null,
        int $width = 640,
        int $height = 480,
        bool $isFullPath = true,
        int $id = null,
        bool $randomize = true,
        bool $gray = false,
        int $blur = null,
        string $imageExtension = null
    ): bool|\RuntimeException|string {
        $url = static::imageUrl($width, $height, $id, $randomize, $gray, $blur, $imageExtension);

        return self::fetchImage($url, $dir, $isFullPath, $imageExtension ?? self::JPG_IMAGE);
    }

    private static function fetchImage(
        string $url,
        ?string $dir,
        bool $isFullPath,
        string $imageExtension
    ): bool|\RuntimeException|string {
        $dir = $dir === null ? sys_get_temp_dir() : $dir; // GNU/Linux / OS X / Windows compatible
        // Validate directory path
        if (! is_dir($dir) || ! is_writable($dir)) {
            throw new \InvalidArgumentException(sprintf('Cannot write to directory "%s"', $dir));
        }

        // Generate a random filename. Use the server address so that a file
        // generated at the same time on a different server won't have a collision.
        $name = md5(uniqid(empty($_SERVER['SERVER_ADDR']) ? '' : $_SERVER['SERVER_ADDR'], true));
        $filename = $name . "." . $imageExtension;
        $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

        // save file
        if (function_exists('curl_exec')) {
            // use cURL
            $fp = fopen($filepath, 'w');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Linux x86_64; rv:104.0) Gecko/20100101 Firefox/104.0');
            $success = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
            fclose($fp);
            curl_close($ch);

            if (! $success) {
                unlink($filepath);

                // could not contact the distant URL or HTTP error - fail silently.
                return false;
            }
        } elseif (ini_get('allow_url_fopen')) {
            // use remote fopen() via copy()
            $success = copy($url, $filepath);
            if (! $success) {
                // could not contact the distant URL or HTTP error - fail silently.
                return false;
            }
        } else {
            return new \RuntimeException('The image formatter downloads an image from a remote HTTP server. Therefore, it requires that PHP can request remote hosts, either via cURL or fopen()');
        }

        return $isFullPath ? $filepath : $filename;
    }

    private static function buildQueryString(?bool $gray, ?int $blur, ?bool $randomize): string
    {
        $queryParams = [];
        $queryString = '';

        if ($gray) {
            $queryParams['grayscale'] = '';
        }

        if ($blur) {
            $queryParams['blur'] = '';
        }

        if ($randomize) {
            $queryParams['random'] = static::randomNumber(5, true);
        }

        if (! empty($queryParams)) {
            $queryString = '?' . http_build_query($queryParams);
        }

        return $queryString;
    }

    private static function buildPicsumUrl($path, $queryString, $imageExtension = null)
    {
        if ($imageExtension) {
            if (! in_array($imageExtension, self::$IMAGE_EXTENSIONS, true)) {
                throw new InvalidArgumentException(sprintf('Invalid image extension "%s"', $imageExtension));
            }
            $path .= '.' . $imageExtension;
        }

        return self::$baseUrl . $path . $queryString;
    }
}
