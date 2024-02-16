<?php

require_once ("database/config.php");
require_once ("StartingAreaController.php");

/**
 * @var database\Database $connection
 */
StartingAreaController::updateInventory($connection);

exit();