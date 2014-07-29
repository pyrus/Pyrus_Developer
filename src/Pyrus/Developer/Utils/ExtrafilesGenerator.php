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
 * Used in {@link ExtrafilesGenerator::addPackages()}
 * to open package.xml document.
 */
use DOMDocument;

/**
 * Used in {@link ExtrafilesGenerator::addPackages()}
 * to search package.xml documents.
 */
use DOMXPath;

/**
 * Used in  in {@link ExtrafilesGenerator::generate()}
 * to get the installed packages.
 */
use Pyrus\Config;

/**
 * Generator for $extrafiles
 *
 * Allows easy bundling of external packages during "pyrus package".
 *
 * @category Pyrus
 * @package  Pyrus_Developer
 * @author   Vasil Rangelov <boen.robot@gmail.com>
 * @license  http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @link     https://github.com/pyrus/Pyrus_Developer
 */
class ExtrafilesGenerator
{
    /**
     * Used in {@link static::addPackage()} as part of a bitmask.
     * When it is the only value, specifies the removal of a previously included
     * package.
     */
    const ROLE_NONE = 0;

    /**
     * Used in {@link static::addPackage()} as part of a bitmask.
     * Specifies that files with role "php" should be included in the generated
     * $extrafiles.
     */
    const ROLE_PHP = 1;

    /**
     * Used in {@link static::addPackage()} as part of a bitmask.
     * Specifies that files with role "data" should be included in the generated
     * $extrafiles.
     */
    const ROLE_DATA = 2;

    /**
     * Used in {@link static::addPackage()} as part of a bitmask.
     * Specifies that files with role "www" should be included in the generated
     * $extrafiles.
     */
    const ROLE_WWW = 4;

    /**
     * Used in {@link static::addPackage()} as part of a bitmask.
     * Specifies that files with role "test" should be included in the generated
     * $extrafiles.
     */
    const ROLE_TEST = 8;

    /**
     * Used in {@link static::addPackage()} as part of a bitmask.
     * Specifies that files with role "doc" should be included in the generated
     * $extrafiles.
     */
    const ROLE_DOC = 16;

    /**
     * Used in {@link static::addPackage()} as part of a bitmask.
     * Specifies that files with role "script" should be included in the
     * generated $extrafiles.
     */
    const ROLE_SCRIPT = 32;

    /**
     * Used in {@link static::addPackage()} as part of a bitmask.
     * Specifies that files with role "ext" should be included in the generated
     * $extrafiles.
     */
    const ROLE_EXT = 64;

    /**
     * Used in {@link static::addPackage()} as part of a bitmask.
     * Specifies that all files should be included in the generated
     * $extrafiles.
     */
    const ROLE_ALL = 127;

    /**
     * Used in {@link static::addPackage()} as the default value of a bitmask.
     * Specifies that files with role "php", "data" and "www" should be included
     * in the generated $extrafiles.
     */
    const ROLE_DEFAULT = 7;

    /**
     * @var string Namespace URI for package.xml.
     */
    protected static $packageNs = 'http://pear.php.net/dtd/package-2.1';

    /**
     * @var array<string,array<string,int>> An array where each key is a channel
     *     with packages to be bundled, the value is an array with each package
     *     being the array key, and the value being a bitmask of self::ROLE_*
     *     constants, representing the roles that should be included in the
     *     generated $extrafiles.
     */
    protected $packages = array();

    /**
     * Create a new instance of this class.
     *
     * @return static
     */
    public static function create()
    {
        return new static;
    }

    /**
     * Adds a package to be inclued in the generated $extrafiles.
     *
     * Note that with the exception of files with role "php", all files
     * include the channel name and package name under the role folder,
     * same as how it would be if they were installed by Pyrus.
     *
     * @param string $channel The PEAR channel of the package.
     * @param string $package The name of the package.
     * @param int    $roles   Limit files to be included by those of matching
     *     certain roles. Defaults to "php", "data" and "www".
     *     This value is a bitmask of ROLE_* constants.
     *
     * @return $this
     */
    public function addPackage(
        $channel,
        $package,
        $roles = self::ROLE_DEFAULT
    ) {
        if (static::ROLE_NONE === $roles) {
            unset($this->packages[$channel][$package]);
        } else {
            $this->packages[$channel][$package] = $roles;
        }

        return $this;
    }

    /**
     * Adds all packages described in a file.
     *
     * @param string $packageFile Location of package.xml file,
     *     the dependencies of which will be included in the generated
     *     $extrafiles.
     * @param int    $roles       Limit files to be included by those of
     *     matching certain roles. Defaults to "php", "data" and "www".
     *     This value is a bitmask of ROLE_* constants.
     *
     * @return $this
     */
    public function addPackages($packageFile, $roles = self::ROLE_DEFAULT)
    {
        $dom = new DOMDocument();
        $dom->loadXML(file_get_contents($packageFile));
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('p', self::$packageNs);

        foreach ($xpath->query(
            '/p:package/p:dependencies/p:*/p:package'
        ) as $reqPkg) {
            $this->addPackage(
                $reqPkg->getElementsByTagName('channel')->item(0)->nodeValue,
                $reqPkg->getElementsByTagName('name')->item(0)->nodeValue,
                $roles
            );
        }

        return $this;
    }

    /**
     * Generates an $extrafiles array.
     *
     * @return array The generated value to then be assigned to $extrafiles,
     *     when invoking this method from extrasetup.php.
     */
    public function generate()
    {
        $config = Config::current();
        $registry = $config->registry;
        $pearDir = explode(PATH_SEPARATOR, $config->my_pear_path);
        $pearDir = $pearDir[0];

        $extrafiles = array();
        foreach ($this->packages as $channel => $channelInfo) {
            foreach ($channelInfo as $package => $roles) {
                foreach ($registry->toPackage($package, $channel)
                    ->installcontents as $file => $info) {
                    if (('php' === $info->role)
                        && ($roles & static::ROLE_PHP)
                    ) {
                        if (strpos($file, 'php/') === 0) {
                            $file = substr($file, 4);
                        }
                        $extrafiles['src/' . $file] = realpath(
                            $pearDir . DIRECTORY_SEPARATOR .
                            'php' . DIRECTORY_SEPARATOR .$file
                        );
                    } elseif (defined(
                        $roleConstant = get_called_class() . '\\' .
                        'ROLE_' . strtoupper($info->role)
                    )
                        && ($roles & constant($roleConstant))
                    ) {
                        $extrafiles[$file] = realpath(
                            $pearDir . DIRECTORY_SEPARATOR . $file
                        );
                    }
                }
            }
        }

        return $extrafiles;
    }
}
