<?php

namespace Smknstd\FakerPicsumImages\Tests;

use PHPUnit\Framework\TestCase;
use Smknstd\FakerPicsumImages\FakerPicsumImagesProvider;

class FakerPicsumImagesProviderTest extends TestCase
{
    public function testImageUrlUses640x680AsTheDefaultSize()
    {
        $this->assertMatchesRegularExpression('#^https://picsum.photos/640/480#', FakerPicsumImagesProvider::imageUrl());
    }

    public function testImageUrlAcceptsCustomWidthAndHeight()
    {
        $this->assertMatchesRegularExpression('#^https://picsum.photos/800/400#', FakerPicsumImagesProvider::imageUrl(800, 400));
    }

    public function testImageUrlWithBlur()
    {
        $this->assertMatchesRegularExpression('#^https://picsum\.photos/800/400\?blur=#', FakerPicsumImagesProvider::imageUrl(800, 400, null, false, false, true));
    }

    public function testImageUrlGray()
    {
        $this->assertMatchesRegularExpression('#^https://picsum\.photos/800/400\?grayscale=#', FakerPicsumImagesProvider::imageUrl(800, 400, null, false, true));
    }

    public function testImageUrlSeed()
    {
        $this->assertMatchesRegularExpression('#^https://picsum\.photos/seed/1234567/800/400#', FakerPicsumImagesProvider::imageUrl(800, 400, null, false, seed: '1234567'));
    }

    public function testImageUrlWithIdAndCustomWidthAndHeight()
    {
        $this->assertMatchesRegularExpression('#^https://picsum.photos/id/871/800/400#', FakerPicsumImagesProvider::imageUrl(800, 400, 871));
    }

    public function testImageUrlWithGrayAndBlur()
    {
        $imageUrl = FakerPicsumImagesProvider::imageUrl(
            800,
            400,
            null,
            false,
            true,
            true
        );

        $this->assertSame('https://picsum.photos/800/400?grayscale=&blur=', $imageUrl);
    }

    public function testImageUrlAddsARandomGetParameterByDefault()
    {
        $url = FakerPicsumImagesProvider::imageUrl(800, 400);
        $splitUrl = explode('?', $url);

        $this->assertEquals(count($splitUrl), 2);
        $this->assertMatchesRegularExpression('#random=\d{5}#', $splitUrl[1]);
    }

    public function testImageDownloadWithDefaults()
    {
        $file = FakerPicsumImagesProvider::image(sys_get_temp_dir());
        $this->assertFileExists($file);
        if (function_exists('getimagesize')) {
            list($width, $height, $type) = getimagesize($file);
            $this->assertEquals(640, $width);
            $this->assertEquals(480, $height);
            $this->assertEquals(constant('IMAGETYPE_JPEG'), $type);
        } else {
            $this->assertEquals('jpg', pathinfo($file, PATHINFO_EXTENSION));
        }
        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * @dataProvider picusmImageExtensionsProvider
     *
     * @param string $imageExtension
     */
    public function testImageUrlWithImageExtension($imageExtension)
    {
        $imageUrl = FakerPicsumImagesProvider::imageUrl(
            800,
            400,
            null,
            false,
            true,
            true,
            $imageExtension
        );
        $expectedString = sprintf('https://picsum.photos/800/400.%s?grayscale=&blur=', $imageExtension);

        $this->assertSame($expectedString, $imageUrl);
    }

    public function testNotSupportedImageExtension()
    {
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Invalid image extension');

        FakerPicsumImagesProvider::imageUrl(
            800,
            400,
            null,
            false,
            true,
            true,
            'wrongExtension'
        );
    }

    public function picusmImageExtensionsProvider()
    {
        return [
            [FakerPicsumImagesProvider::JPG_IMAGE],
            [FakerPicsumImagesProvider::WEBP_IMAGE],
        ];
    }
}
