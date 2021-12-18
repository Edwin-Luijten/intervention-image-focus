<?php

use EdwinLuijten\InterventionImageFocus\FocusFilter;
use Intervention\Image\ImageManager;

require __DIR__ . '/vendor/autoload.php';

$manager = new ImageManager();

$sizes = [
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

foreach ($sizes as $size) {
    $image = $manager->make(__DIR__ . '/tests/images/source/base.jpg');

    [$width, $height] = explode('x', $size);
    $image->filter(new FocusFilter($width, $height, '71-50'));

    $image->encode('jpg');
    $image->save(__DIR__ . '/tests/images/source/off-' . $size . '.jpg');

    echo $size . ' saved' . PHP_EOL;

    $image->destroy();
}