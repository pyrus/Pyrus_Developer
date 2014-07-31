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
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @version  GIT: $Id$
 * @link     https://github.com/pyrus/Pyrus_Developer
 */

namespace Pyrus\Developer\Utils;

/**
 * Works over these sorts of packages.
 */
use Pyrus\Developer\PackageFile\v2;

/**
 * Used to iterate over the package's files.
 */
use RecursiveDirectoryIterator;

/**
 * Used to more easily iterate over subfolders.
 */
use RecursiveIteratorIterator;

/**
 * package.xml manager
 *
 * Provides common "pyrus make" time transformations for package.xml files.
 *
 * @category Pyrus
 * @package  Pyrus_Developer
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     https://github.com/pyrus/Pyrus_Developer
 */
class PackageFileManager
{
    /**
     * Used in {@link addEolReplacement()}
     * to remove previously assigned replacement on matching files.
     */
    const EOL_DEFAULT = null;

    /**
     * Used in {@link addEolReplacement()}
     * to set Windows EOL on matching files.
     */
    const EOL_WINDOWS = 'windows';

    /**
     * Used in {@link addEolReplacement()}
     * to set UNIX EOL on matching files.
     */
    const EOL_UNIX = 'unix';

    /**
     * @var v2
     */
    protected $package;

    /**
     * @var v2
     */
    protected $compatible;

    /**
     * @var bool Whether rebuilding "install" elements is enabled.
     *     By default, it's enabled.
     */
    protected $isRebuildInstallEnabled = true;

    /**
     * @var array<string,array<string,string>>
     */
    protected $replacements = array();

    /**
     * @var array<string,string>
     */
    protected $eolReplacements = array();

    /**
     * Perform taks over the package objects.
     *
     * @param v2 $package    The object representing the main package.
     * @param v2 $compatible The object representing the compatible package.
     */
    public function __construct(v2 $package, v2 $compatible = null)
    {
        $this->package = $package;
        $this->compatible = $compatible;
    }

    /**
     * Perform taks over the package objects.
     *
     * @param v2 $package    The object representing the main package.
     * @param v2 $compatible The object representing the compatible package.
     */
    public static function create(v2 $package, v2 $compatible = null)
    {
        return new static($package, $compatible);
    }

    /**
     * Enables/disables the rebuilding of "install" elements.
     *
     * @param bool $enabled Whether to enable rebuilding of "install" elements.
     *
     * @return $this
     */
    public function setRebuildInstall($enabled)
    {
        $this->isRebuildInstallEnabled = (bool)$enabled;

        return $this;
    }

    /**
     * Checks whether rebuilding of "install" elements is currently enabled.
     *
     * @return bool The current value for the setting.
     *     TRUE (enabled) by default.
     */
    public function isRebuildInstallEnabled()
    {
        return $this->isRebuildInstallEnabled;
    }

    /**
     * Add an install time replacement over all files that need it.
     *
     * @param string $from String to look for.
     * @param string $to   The name of an attribute,
     *     the value of which will be used as a replacement.
     * @param string $type The type of attribute.
     *
     * @return $this
     */
    public function addReplacement($from, $to, $type)
    {
        $this->replacements[$from] = array(
            'to' => $to,
            'type' => $type
        );

        return $this;
    }

    /**
     * Remove a previously added install time replacement.
     *
     * @param string $from The string that was supposed to be replaced.
     *
     * @return $this
     */
    public function removeReplacement($from)
    {
        unset($this->replacements[$from]);

        return $this;
    }

    /**
     * Add an install time EOL replacement.
     *
     * @param string $fileMatch fnmatch() pattern to match files against.
     * @param string $eolType   The type of EOL to use for matching files.
     *     Should be one of this class' EOL_* constants.
     *
     * @return $this
     */
    public function addEolReplacement($fileMatch, $eolType)
    {
        if (static::EOL_DEFAULT === $eolType) {
            unset($this->eolReplacements[$fileMatch]);
        } else {
            $this->eolReplacements[$fileMatch] = $eolType;
        }

        return $this;
    }

    /**
     * Applies the transformations to the package file(s).
     *
     * @return $this
     */
    public function save()
    {
        $tasksNs = $this->package->getTasksNs();
        $cTasksNs = $this->compatible ? $this->compatible->getTasksNs() : '';
        $oldCwd = getcwd();
        chdir($this->package->filepath);
        if ($this->isRebuildInstallEnabled) {
            $this->package->setRawRelease('php', array());
            $release = $this->package->getReleaseToInstall('php', true);
            if ($this->compatible) {
                $this->compatible->setRawRelease('php', array());
                $cRelease = $this->compatible->getReleaseToInstall('php', true);
            }
        }
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                '.',
                RecursiveDirectoryIterator::UNIX_PATHS
                | RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::LEAVES_ONLY
        ) as $path) {
                $filename = substr($path->getPathname(), 2);
                $cFilename = str_replace('src/', 'php/', $filename);
            if (isset($this->package->files[$filename])) {
                $parsedFilename = pathinfo($filename);
                $as = (strpos($filename, 'examples/') === 0)
                    ? $filename
                    : substr($filename, strpos($filename, '/') + 1);
                if (strpos($filename, 'scripts/') === 0) {
                    if (isset($parsedFilename['extension'])
                        && 'php' === $parsedFilename['extension']
                        && !is_file(
                            $parsedFilename['dirname'] . '/' .
                            $parsedFilename['filename']
                        )
                        && is_file(
                            $parsedFilename['dirname'] . '/' .
                            $parsedFilename['filename'] . '.bat'
                        )
                    ) {
                        $as = substr($as, 0, -4);
                    }
                }
                if (isset($release)) {
                    $release->installAs($filename, $as);
                }
                if (isset($cRelease)) {
                    $cRelease->installAs($cFilename, $as);
                }

                $contents = file_get_contents($filename);
                foreach ($this->replacements as $from => $attribs) {
                    if (strpos($contents, $from) !== false) {
                        $attribs['from'] = $from;
                        $this->package->files[$filename]
                            = array_merge_recursive(
                                $this->package->files[$filename]
                                    ->getArrayCopy(),
                                array(
                                    "{$tasksNs}:replace" => array(
                                        array(
                                            'attribs' => $attribs
                                        )
                                    )
                                )
                            );

                        if ($this->compatible) {
                            $this->compatible->files[$cFilename]
                                = array_merge_recursive(
                                    $this->compatible->files[$cFilename]
                                        ->getArrayCopy(),
                                    array(
                                        "{$cTasksNs}:replace" => array(
                                            array(
                                                'attribs' => $attribs
                                            )
                                        )
                                    )
                                );
                        }
                    }
                }

                foreach ($this->eolReplacements as $pattern => $platform) {
                    if (fnmatch($pattern, $filename)) {
                        $this->package->files[$filename]
                            = array_merge_recursive(
                                $this->package->files[$filename]
                                    ->getArrayCopy(),
                                array(
                                    "{$tasksNs}:{$platform}eol" => array()
                                )
                            );

                        if ($this->compatible) {
                            $this->compatible->files[$cFilename]
                                = array_merge_recursive(
                                    $this->compatible->files[$cFilename]
                                        ->getArrayCopy(),
                                    array(
                                        "{$cTasksNs}:{$platform}eol" => array()
                                    )
                                );
                        }
                    }
                }
            }
        }
        chdir($oldCwd);

        return $this;
    }
}
