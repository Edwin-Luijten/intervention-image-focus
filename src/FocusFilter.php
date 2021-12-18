<?php

declare(strict_types=1);

namespace EdwinLuijten\InterventionImageFocus;

use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class FocusFilter implements FilterInterface
{
    private int $width;

    private int $height;

    private string $fit;

    public function __construct(int $width, int $height, string $fit)
    {
        $this->width = $width;
        $this->height = $height;
        $this->fit = $fit;
    }

    public function applyFilter(Image $image): Image
    {
        if ($this->width !== $image->width() || $this->height !== $image->height()) {
            $image = $this->runResize($image);
        }

        return $image;
    }

    public function runResize(Image $image): Image
    {
        $imageWidth = $image->width();
        $imageHeight = $image->height();

        $crop = $this->getCrop();

        [$focalX, $focalY] = $crop;

        $focalPointX = round($imageWidth * ($focalX / 100));
        $focalPointY = round($imageHeight * ($focalY / 100));

        $widthRatio = $imageWidth / $this->width;
        $heightRatio = $imageHeight / $this->height;

        $scaleWidth = $this->width / $imageWidth;
        $scaleHeight = $this->height / $imageHeight;

        $scale = max($scaleWidth, $scaleHeight);

        $image->resize($imageWidth * $scale, $imageHeight * $scale);

        $xShift = 0;
        $yShift = 0;

        if ($widthRatio > $heightRatio) {
            $xShift = $this->getShift($heightRatio, $this->width, $imageWidth, $focalPointX);
        } else {
            $yShift = $this->getShift($widthRatio, $this->height, $imageHeight, $focalPointY, true);
        }

        return $image->crop($this->width, $this->height, $xShift, $yShift);
    }

    /**
     * @return int[]
     */
    private function getCrop(): array
    {
        $cropMethods = [
            'crop-top-left' => [0, 0],
            'crop-top' => [50, 0],
            'crop-top-right' => [100, 0],
            'crop-left' => [0, 50],
            'crop-center' => [50, 50],
            'crop-right' => [100, 50],
            'crop-bottom-left' => [0, 100],
            'crop-bottom' => [50, 100],
            'crop-bottom-right' => [100, 100],
        ];

        if (array_key_exists($this->fit, $cropMethods)) {
            return $cropMethods[$this->fit];
        }

        if (preg_match('/^([\d]{1,3})-([\d]{1,3})(?:-([\d]{1,3}(?:\.\d+)?))*$/', $this->fit, $matches)) {
            if ($matches[1] > 100 or $matches[2] > 100 or $matches[3] > 100) {
                return [50, 50];
            }

            return [
                (int)$matches[1],
                (int)$matches[2],
            ];
        }

        return [50, 50];
    }

    private function getShift(float $ratio, float $containerSize, float $imageSize, float $focusPosition, bool $toMinus = false): int
    {
        $containerCenter = floor($containerSize / 2);
        $focusFactor = $focusPosition / $imageSize;

        $scaledImage = floor($imageSize / $ratio);
        $focus = floor($focusFactor * $scaledImage);

        if ($toMinus) {
            $focus = $scaledImage - $focus;
        }

        $focusOffset = $focus - $containerCenter;
        $remainder = $scaledImage - $focus;
        $containerRemainder = $containerSize - $containerCenter;

        if ($remainder < $containerRemainder) {
            $focusOffset -= $containerRemainder - $remainder;
        }

        if ($focusOffset < 0) {
            $focusOffset = 0;
        }

        return (int)$focusOffset;
    }
}
