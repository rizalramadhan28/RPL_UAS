<?php
declare(strict_types=1);

require __DIR__ . '/../src/autoload.php';

use App\Core\App;
use App\Core\Request;
use App\Core\Router;

App::bootstrap();

$router = new Router();
require __DIR__ . '/../src/routes.php';

$router->dispatch(new Request());
