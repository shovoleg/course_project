<?php

use App\Kernel;

foreach (['IMGBB_API_KEY'] as $name) {
    $value = $_SERVER[$name] ?? $_ENV[$name] ?? getenv($name);
    if ($value === false || $value === null || $value === '') {
        unset($_SERVER[$name], $_ENV[$name]);
        putenv($name);
    }
}

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
