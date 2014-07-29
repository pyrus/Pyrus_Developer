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

class Xml implements CreatorInterface
{
    private $_done;
    private $_path;

    public function __construct($path)
    {
        if (!($this->_path = @fopen($path, 'w'))) {
            throw new Exception(
                'Cannot open path ' . $path . ' for writing'
            );
        }
    }

    /**
     * save a file inside this package
     * 
     * This only saves package.xml,
     * which is always the first file sent by the creator.
     * 
     * @param string          $path         relative path within the package
     * @param string|resource $fileOrStream file contents or open file handle
     * 
     * @return void
     */
    public function addFile($path, $fileOrStream)
    {
        if (!$this->_done) {
            $this->_done = true;
            if (is_resource($fileOrStream)) {
                stream_copy_to_stream($fileOrStream, $this->_path);
            } else {
                fwrite($this->_path, $fileOrStream);
            }
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
            $file = (string) $file;
            $relpath = str_replace($path . DIRECTORY_SEPARATOR, '', $file);
            $this->addFile($relpath, $file);
        }
    }

    /**
     * Initialize the package creator
     * 
     * @return void
     */
    public function init()
    {
        $this->_done = false;
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
        fclose($this->_path);
    }
}
