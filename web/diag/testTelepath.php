<?php

/*
 * ******************************************************************************
 * Copyright 2011-2017 DANTE Ltd. and GÉANT on behalf of the GN3, GN3+, GN4-1 
 * and GN4-2 consortia
 *
 * License: see the web/copyright.php file in the file structure
 * ******************************************************************************
 */
require_once(dirname(dirname(__DIR__)) . "/config/_config.php");

$telepath = new \core\diag\Telepath($_GET['realm'], $_GET['visited']);
$validator = new \web\lib\common\InputValidation();

echo "<pre>";
echo "Testing ".$validator->realm($_GET['realm'])." in ".$validator->string($_GET['visited']);
print_r($telepath->magic());
echo "</pre>";