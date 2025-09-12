<?php
declare(strict_types=1);

use Services\ServiceLocator;
use Services\DB;
use Services\EventBus;
use Services\Logger;

ServiceLocator::singleton('db', fn() => DB::conn());
ServiceLocator::singleton('logger', fn() => Logger::class);
ServiceLocator::singleton('events', fn() => EventBus::class);
