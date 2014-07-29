<?php

/**
 * ~~summary~~
 *
 * ~~description~~
 *
 * PHP version 5.3
 *
 * @category Pyrus
 * @package  Pyrus_Developer
 * @author   Greg Beaver <greg@chiaraquartet.net>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  GIT: $Id$
 * @link     https://github.com/pyrus/Pyrus_Developer
 */

namespace Pyrus\Developer\Creator\Phar;

use Phar;
use Pyrus\Developer\Creator\Exception;
use Pyrus\Developer\Creator\Phar as P;

/**
 * Create a phar with PHP_Archive embedded
 */
class PHPArchive extends P
{
    /**
     * @var Phar
     */
    protected $phar;
    protected $path;
    protected $stub;
    protected $startup;

    public function __construct(
        $path,
        $startupfile = false,
        $fileformat = Phar::PHAR,
        $compression = Phar::NONE,
        array $others = null
    ) {
        parent::__construct($path, false, $fileformat, $compression, $others);
        $phparchive = @file_get_contents('PHP/Archive.php', true);
        if (!$phparchive) {
            throw new Exception(
                'Could not locate PHP_Archive class for phar creation'
            );
        }
        $phparchive = '?>' . $phparchive . '<?php';
        $template = @file_get_contents(
            dirname(__FILE__) .
            '/../../../../../data/pear2.php.net/PEAR2_Pyrus_Developer/phartemplate.php'
        );
        if (!$template) {
            $template = file_get_contents(
                __DIR__ . '/../../../../data/phartemplate.php'
            );
        }
        $this->stub = str_replace('@PHPARCHIVE@', $phparchive, $template);
        if ($startupfile === false) {
            $startupfile = '<?php
$extract = getcwd();
$loc = dirname(__FILE__);
foreach (new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(dirname(__FILE__))
) as $path => $file
) {
    if ($file->getFileName() === \'__index.php\') {
        continue;
    }
    $newpath = str_replace(
        \'/\',
        DIRECTORY_SEPARATOR,
        $extract . str_replace($loc, \'\', $path)
    );
    if (!file_exists(dirname($newpath))) {
        mkdir(dirname($newpath), 0755, true);
    }
    file_put_contents($newpath, file_get_contents($path));
}
echo "Extracted files available in current directory\n";';
        }
        $this->startup = $startupfile;
    }

    /**
     * Initialize the package creator
     * 
     * @return void
     */
    public function init()
    {
        parent::init();
        $this->phar['__index.php'] = $this->startup;
    }
}
