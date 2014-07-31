<?php
/**
 * extrasetup.php for __PACKAGE__
 *
 * Extra package.xml settings such as dependencies.
 * More information: http://pear.php.net/manual/en/pyrus.commands.make.php#pyrus.commands.make.packagexmlsetup
 *
 * @package __PACKAGE__
 */
 
 use Pyrus\Developer\Utils\PackageFileManager;

/*
//for example:
$package->dependencies['required']->package['pear2.php.net/PEAR2_Autoload']->save();
$package->dependencies['required']->package['pear2.php.net/PEAR2_Exception']->save();
$package->dependencies['required']->package['pear2.php.net/PEAR2_MultiErrors']->save();
$package->dependencies['required']->package['pear2.php.net/PEAR2_HTTP_Request']->save();

$compatible->dependencies['required']->package['pear2.php.net/PEAR2_Autoload']->save();
$compatible->dependencies['required']->package['pear2.php.net/PEAR2_Exception']->save();
$compatible->dependencies['required']->package['pear2.php.net/PEAR2_MultiErrors']->save();
$compatible->dependencies['required']->package['pear2.php.net/PEAR2_HTTP_Request']->save();

// ignore files
unset($package->files['www/config.inc.php']);
unset($package->files['www/.htaccess']);
*/

//Some common modifications can be done with the provided utility
PackageFileManager::create($package, $compatible)
    /*
    //Such as adding install time replacements in all files that need it...
    ->addReplacement('../src', 'php_dir', 'pear-config')
    ->addReplacement('GIT: $Id$', 'version', 'package-info')
    //... or changing the end of line characters for OS specific files.
    ->addEolReplacement('*.bat', PackageFileManager::EOL_WINDOWS)
    ->addEolReplacement('*.sh', PackageFileManager::EOL_UNIX)
    */
    ->save();
