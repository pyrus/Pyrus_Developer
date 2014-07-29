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

namespace Pyrus\Developer\PackageFile;

use Pyrus\Developer\PackageFile\PECL\Filter;
use Pyrus\PackageFile;
use Pyrus\XMLWriter\Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Create a brand new package.xml from the cvs layout of a PECL package
 *
 * This class assumes:
 *
 *  1. the source layout is:
 *     <pre>
 *     /*.c/*.h/*.m4/*.w32/*.frag             [role="src"]
 *     /examples                              [role="doc"]
 *     /doc                                   [role="doc"]
 *     /data                                  [role="data"]
 *     /tests                                 [role="test"]
 *     </pre>
 *  2. if file PackageName/README exists, it contains the summary as the first line,
 *     and the description as the rest of the file
 *  3. if file PackageName/CREDITS exists, it contains the maintainers
 *     in this format:
 *     ; comment ignored
 *     Name [handle] <email> (role)
 *     Name2 [handle2] <email> (role/inactive)
 */
class PECL extends PEAR2SVN
{
    protected $sourceExtensions;

    /**
     * Create or update a package.xml from CVS
     *
     * @param string $path             full path to the CVS checkout
     * @param string $packagename      Package name (phar, for example)
     * @param string $channel          Channel (pecl.php.net)
     * @param array  $sourceextensions file extensions that represent source files
     * @param bool   $return           if true, creation is not attempted
     *     in the constructor, otherwise, the constructor writes package.xml
     *     to disk if possible
     */
    public function __construct(
        $path,
        $packagename = '##set me##',
        $channel = 'pecl.php.net',
        $sourceextensions = array('c', 'cc', 'h', 'm4', 'w32', 're', 'y', 'l'),
        $return = false
    ) {
        if (file_exists($path . DIRECTORY_SEPARATOR . 'package.xml')) {
            try {
                $this->pxml = new PackageFile(
                    $path . DIRECTORY_SEPARATOR . 'package.xml',
                    'Pyrus\Developer\PackageFile\v2'
                );
                $this->pxml = $this->pxml->info;
                $this->pxml->setFilelist(array());
            } catch (Exception $e) {
                $this->pxml = new v2;
                $this->pxml->name = $packagename;
                $this->pxml->channel = $channel;
            }
        } else {
            $this->pxml = new v2;
            $this->pxml->name = $packagename;
            $this->pxml->channel = $channel;
        }
        $this->path = $path;
        $this->sourceExtensions = $sourceextensions;

        $this->parseREADME();
        $this->parseCREDITS();
        $this->parseRELEASE();

        $this->scanFiles(null);
        $this->pxml->dependencies['required']->pearinstaller->min = '1.4.8';
        $this->pxml->type = 'extsrc';
        $this->pxml->providesextension = $this->pxml->name;

        $this->validate();
        try {
            if (!$return) {
                $this->save();
            }
        } catch (Exception $e) {
            // ignore - we'll let the user do this business
            echo 'WARNING: validation failed in constructor, ' .
                'you must fix the package.xml manually:', $e;
        }
    }

    /**
     * Scan the directories to populate the package file contents.
     */
    public function scanFiles($packagepath)
    {
        
        $rolemap = array(
            'data'          => 'data',
            'doc'           => 'doc',
            'tests'         => 'test',
            'examples'      => 'doc',
        );
        // first add the obvious non-source files
        foreach ($rolemap as $dir => $role) {
            if (file_exists($this->path . DIRECTORY_SEPARATOR . $dir)) {
                $basepath = ($dir === 'examples') ? 'examples' : '';
                foreach (new Filter(
                    $this->path . DIRECTORY_SEPARATOR . $dir,
                    new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator(
                            $this->path . DIRECTORY_SEPARATOR . $dir
                        ),
                        RecursiveIteratorIterator::LEAVES_ONLY
                    ),
                    $role
                ) as $file
                ) {
                    $curpath = str_replace(
                        $this->path . DIRECTORY_SEPARATOR . $dir,
                        '',
                        $file->getPathName()
                    );
                    if ($curpath && $curpath[0] === DIRECTORY_SEPARATOR) {
                        $curpath = substr($curpath, 1);
                    }
                    $curpath = $dir . '/' . $curpath;
                    $curpath = str_replace('\\', '/', $curpath);
                    $curpath = str_replace('//', '/', $curpath);
                    $this->pxml->files[$curpath] = array(
                        'attribs' => array('role' => $role)
                    );
                }
            }
        }
        foreach (new Filter(
            $this->path,
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->path),
                RecursiveIteratorIterator::LEAVES_ONLY
            )
        ) as $file
        ) {
            $curpath = str_replace(
                $this->path,
                '',
                $file->getPathName()
            );
            if ($curpath && $curpath[0] === DIRECTORY_SEPARATOR) {
                $curpath = substr($curpath, 1);
            }
            $curpath = str_replace('\\', '/', $curpath);
            $curpath = str_replace('//', '/', $curpath);
            if (isset($this->pxml->files[$curpath])) {
                continue;
            }
            $info = pathinfo($curpath);
            if (!isset($info['extension'])) {
                // ignore this file
                continue;
            }
            if (!in_array($info['extension'], $this->sourceExtensions)) {
                continue;
            }
            $this->pxml->files[$curpath] = array(
                'attribs' => array('role' => 'src')
            );
        }
    }
}
