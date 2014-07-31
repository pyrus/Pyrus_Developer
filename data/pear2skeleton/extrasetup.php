<?php

/**
 * extrasetup.php for __PACKAGE__
 *
 * This file is used to provide extra files/packages outside package.xml
 * More information: http://pear.php.net/manual/en/pyrus.commands.package.php#pyrus.commands.package.extrasetup
 
 * @package __PACKAGE__
 */

use Pyrus\Developer\Utils\ExtrafilesGenerator;

//It's a good practice to include every package mentioned in your package.xml...
$extrafiles = ExtrafilesGenerator::create()
    //NOTE: This assumes you've already installed all dependencies.
    ->addPackages('package.xml')
    ->generate();

/*
//... but you can include additional files not mentioned too,
//by further amending the $extrafiles array, e.g.
$extrafiles['path/in/windows/project.php'] = 'D:\path\on\windows\system.php';
$extrafiles['path/in/unix/project.php'] = '/the/path/on/unix/system.php';
*/