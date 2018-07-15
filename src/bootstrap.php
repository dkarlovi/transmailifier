<?php

declare(strict_types=1);

$autoLoaders = [
    // own auto-loader
    __DIR__.'/../vendor/autoload.php',

    // project auto-loader
    __DIR__.'/../../../autoload.php',
];

foreach ($autoLoaders as $autoLoader) {
    if (true === file_exists($autoLoader)) {
        /* @noinspection PhpIncludeInspection */
        return include $autoLoader;
    }
}

fwrite(
    STDERR,
    'You must set up the project dependencies using `composer install`'.PHP_EOL
);
exit(1);
