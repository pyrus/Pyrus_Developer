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

namespace Pyrus\Developer\Creator;

use Pyrus\Package\CreatorInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class Zip implements CreatorInterface
{
    /**
     * Path to archive file
     *
     * @var string
     */
    protected $archive;
    /**
     * @var ZIPArchive
     */
    protected $zip;
    protected $path;
    public function __construct($path)
    {
        if (!class_exists('ZIPArchive')) {
            throw new Exception(
                'Zip extension is not available'
            );
        }
        $this->path = $path;
    }

    /**
     * save a file inside this package
     * 
     * @param string          $path         relative path within the package
     * @param string|resource $fileOrStream file contents or open file handle
     * 
     * @return void
     */
    public function addFile($path, $fileOrStream)
    {
        if (is_resource($fileOrStream)) {
            $this->zip->addFromString($path, stream_get_contents($fileOrStream));
        } else {
            $this->zip->addFromString($path, $fileOrStream);
        }
    }

    public function addDir($path)
    {
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        ) as $file
        ) {
            $contents = file_get_contents((string)$file);
            $relpath = str_replace($path . DIRECTORY_SEPARATOR, '', $file);
            $this->addFile($relpath, $contents);
        }
    }

    /**
     * Initialize the package creator
     * 
     * @return void
     */
    public function init()
    {
        $this->zip = new ZipArchive;
        if (true !== $this->zip->open($this->path, ZipArchive::CREATE)) {
            throw new Exception(
                'Cannot open ZIP archive ' . $this->path
            );
        }
    }

    /**
     * Create an internal directory, creating parent directories as needed
     * 
     * This is a no-op for the tar creator
     * 
     * @param string $dir
     * 
     * @return void
     */
    public function mkdir($dir)
    {
    }

    /**
     * Finish saving the package
     * 
     * @return void
     */
    public function close()
    {
        $this->zip->close();
    }
}
