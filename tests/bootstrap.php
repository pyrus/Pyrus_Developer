<?php

/**
 * bootstrap.php for Pyrus_Developer.
 *
 * PHP version 5.3
 *
 * @category Pyrus
 * @package  Pyrus_Developer
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  GIT: $Id$
 * @link     https://github.com/pyrus/Pyrus_Developer
 */

/**
 * Possible autoloader to initialize.
 */
use PEAR2\Autoload;

chdir(__DIR__);

$autoloader = stream_resolve_include_path('PEAR2/Autoload.php');
if (false !== $autoloader) {
    include_once $autoloader;
    Autoload::initialize(realpath('../src'));
} else {
    die('No recognized autoloader is available.');
}
unset($autoloader);
