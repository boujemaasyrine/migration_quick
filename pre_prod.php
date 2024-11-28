<?php

umask(0000);

$consoleDir = __DIR__."/app/console ";
$cacheDir= __DIR__."/app/cache/*";
$webDir = __DIR__."/web ";

echo `php $consoleDir cache:clear --env=prod`;

echo `php $consoleDir doctrine:schema:update --force --env=prod`;

echo `php $consoleDir assets:install $webDir --env=prod`;

echo `php $consoleDir bazinga:js-translation:dump  --env=prod`;