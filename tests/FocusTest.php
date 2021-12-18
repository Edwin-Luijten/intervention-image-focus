<?php

declare(strict_types=1);

use EdwinLuijten\InterventionImageFocus\FocusFilter;
use Intervention\Image\ImageManager;
use PHPUnit\Framework\TestCase;

final class FocusTest extends TestCase
{
    private array $dimensions = [
        '1284x602',
        '642x602',
        '428x602',

        '1284x301',
        '642x301',
        '428x301',

        '1284x200',
        '642x200',
        '428x200',
    ];

    private ImageManager $manager;

    protected function setUp(): void
    {
        $this->manager = new ImageManager();

        foreach ($this->dimensions as $dimension) {
            $image = $this->manager->make(__DIR__ . '/images/source/base.jpg');

            [$width, $height] = explode('x', $dimension);
            $image->filter(new FocusFilter((int)$width, (int)$height, '75-50'));

            $image->encode('jpg');
            $image->save(__DIR__ . '/images/' . $dimension . '.jpg');

            $image->destroy();
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->dimensions as $dimension) {
            unlink(__DIR__ . '/images/' . $dimension . '.jpg');
        }
    }

    public function testCorrectDimensions()
    {
        foreach ($this->dimensions as $dimension) {
            $image = $this->manager->make(__DIR__ . '/images/' . $dimension . '.jpg');

            [$width, $height] = explode('x', $dimension);

            $this->assertEquals((int)$width, $image->width());
            $this->assertEquals((int)$height, $image->height());

            $image->destroy();
        }
    }

    public function testValidateImagesAgainstSource()
    {
        foreach ($this->dimensions as $dimension) {
            $this->assertEquals(0, $this->diff($dimension));
        }
    }

    public function testValidateImageAgainstSourceOff()
    {
        $this->assertNotEquals(0, $this->diff('off-428x602'));
    }

    private function diff(string $dimension): int
    {
        $rTolerance = 0;
        $gTolerance = 0;
        $bTolerance = 0;

        $a = imagecreatefromjpeg(__DIR__ . '/images/source/' . $dimension . '.jpg');
        $b = imagecreatefromjpeg(__DIR__ . '/images/' . str_replace('off-', '', $dimension) . '.jpg');

        $out = 0;
        for ($width = 0; $width <= imagesx($a) - 1; $width++) {
            for ($height = 0; $height <= imagesy($a) - 1; $height++) {
                $rgbA = imagecolorat($a, $width, $height);
                $rgbB = imagecolorat($b, $width, $height);

                $rA = ($rgbA >> 16) & 0xFF;
                $gA = ($rgbA >> 8) & 0xFF;
                $bA = $rgbA & 0xFF;

                $rB = ($rgbB >> 16) & 0xFF;
                $gB = ($rgbB >> 8) & 0xFF;
                $bB = $rgbB & 0xFF;

                if (!($rA >= $rB - $rTolerance && $rA <= $rB + $rTolerance)) {
                    $out++;
                }

                if (!($gA >= $gB - $gTolerance && $gA <= $gB + $gTolerance)) {
                    $out++;
                }

                if (!($bA >= $bB - $bTolerance && $bA <= $bB + $bTolerance)) {
                    $out++;
                }
            }
        }

        return $out;
    }
}