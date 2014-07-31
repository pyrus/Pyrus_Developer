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

namespace Pyrus\Developer\PackageFile\Commands;

use Pyrus\Developer\Creator\Exception;
use Pyrus\Developer\PackageFile\Commands;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * This class creates the package skeleton.
 *
 * @author Greg Beaver <greg@chiaraquartet.net>
 * @author Brett Bieber <brett.bieber@gmail.com>
 * @author Helgi <helgi@php.net>
 * @author Till Klampaeckel <till@php.net>
 * @author Vasil Rangelov <boen.robot@gmail.com>
 *
 * @see Commands
 */
class GeneratePEAR2
{
    /**
     * @var array $info
     * @see Commands::parsePackageName()
     */
    protected $info;

    /**
     * @var array
     */
    protected $replacements;

    /**
     * @var string $templatePath Path to templates for the above.
     * @see self::__construct()
     * @see self::generate()
     * @see self::$extraSetupFile
     * @see self::$packageXmlSetupFile
     * @see self::$stubFile
     */
    protected $templatePath;

    /**
     * __construct
     *
     * @param array $info
     *
     * @throws Exception When the path of the package
     *     does not exist.
     */
    public function __construct(array $info)
    {
        if (file_exists($info['__PATH__'])) {
            throw new Exception(
                'Path ' . $info['__PATH__'] . ' already exists'
            );
        }

        if (!static::isInfoValid($info)) {
            throw new Exception(
                "Info is missing a required key."
            );
        }

        $this->info = $info;

        $dataDir = __DIR__ . '/../../../../../data';
        if (!is_dir($dataDir)) {
            throw new Exception(
                "Unable to find data directory."
            );
        }
        
        if (is_dir($dataDir . '/pyrus.net/Pyrus_Developer/pear2skeleton')) {
            $this->templatePath = $dataDir .
                '/pyrus.net/Pyrus_Developer/pear2skeleton';
        } elseif (is_dir($dataDir . '/pear2skeleton')) {
            $this->templatePath = $dataDir .
                '/pear2skeleton';
        } else {
            throw new Exception(
                "Unable to find 'pear2skeleton'. Check the data directory."
            );
        }
    }
    
    /**
     * Start creating.
     *
     * @return void
     */
    public function generate()
    {
        // creates the base package directory
        if (!mkdir($this->info['__PATH__'])) {
            throw new Exception(
                "Unable to create new package's directory."
            );
        }
        $projectDir = getcwd();
        chdir($this->templatePath);

        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                '.',
                RecursiveDirectoryIterator::UNIX_PATHS
                | RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        ) as $path) {
            $filename = substr($path->getPathname(), 2/*strlen('./')*/);

            if (is_dir($filename)) {
                mkdir(
                    $projectDir . DIRECTORY_SEPARATOR .
                    $this->info['__PATH__'] . DIRECTORY_SEPARATOR .
                    $filename,
                    0777,
                    true
                );
                continue;
            }
            
            //.placeholder files are added in empty folders for the sake of GIT.
            //They should be ignored upon creation.
            if (substr($filename, -13) === '/.placeholder') {
                continue;
            }

            $dst = $projectDir . DIRECTORY_SEPARATOR .
                $this->info['__PATH__'] . DIRECTORY_SEPARATOR .
                $filename;
            if ('src/Main.php' === $filename) {
                $dst = $projectDir . DIRECTORY_SEPARATOR .
                    $this->info['__PATH__'] . DIRECTORY_SEPARATOR .
                    '/src/' . $this->info['__MAIN_PATH__'] . '.php';
                mkdir(
                    dirname($dst),
                    0777,
                    true
                );
            }
            
            file_put_contents(
                $dst,
                str_replace(
                    array_keys($this->info),
                    array_values($this->info),
                    file_get_contents($filename)
                )
            );
        }
        chdir($projectDir);
    }

    /**
     * Validate the info passed into {@link self::__construct()}.
     *
     * @return boolean
     *
     * @uses self::$info
     * @see  self::__construct()
     */
    protected static function isInfoValid($info)
    {
        if (!isset($info['__MAIN_CLASS__'])
            || empty($info['__MAIN_CLASS__'])
        ) {
            return false;
        }
        if (!isset($info['__PACKAGE__'])
            || empty($info['__PACKAGE__'])
        ) {
            return false;
        }
        if (!isset($info['__REPO__'])) {
            return false;
        }
        if (!isset($info['__MAIN_PATH__'])) {
            return false;
        }
        if (!isset($info['__MAIN_NAMESPACE__'])
            || empty($info['__MAIN_NAMESPACE__'])
        ) {
            return false;
        }
        return true;
    }
}
