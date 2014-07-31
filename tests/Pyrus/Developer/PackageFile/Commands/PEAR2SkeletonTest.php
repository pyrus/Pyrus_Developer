<?php

namespace Pyrus\Developer;

use PHPUnit_Framework_TestCase;
use Pyrus\Developer\PackageFile\Commands;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * A test case to cover the codemess.
 */
class PEAR2SkeletonTest extends PHPUnit_Framework_TestCase
{
    protected $base;
    protected $packageName = 'PEAR2_Foo';

    protected function setUp()
    {
        $this->base = __DIR__ . '/package-test';
        @mkdir($this->base, 0777, true);
        chdir($this->base);
    }

    protected function tearDown()
    {
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $this->base,
                RecursiveDirectoryIterator::UNIX_PATHS
                | RecursiveDirectoryIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        ) as $path) {
            $path = $path->getPathname();
            if (is_dir($path)) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }

        @rmdir($this->base);
    }

    /**
     * Info array is just like the array returned from
     * {@link Pyrus\Developer\PackageFile\Commands::parsePackageName()}
     *
     * @return void
     */
    public function testPear2Skeleton()
    {
        $info = Commands::parsePackageName('PEAR2_Foo', 'pear2.php.net');
        $skeleton = new Commands\GeneratePEAR2($info);
        $skeleton->generate();

        $this->assertFileExists($this->base . '/' . $info['__PATH__'] . '/src/' . $info['__MAIN_PATH__'] . '.php');

        $this->assertFileExists($this->base . '/' . $info['__PATH__'] . '/stub.php');
        $this->assertFileExists($this->base . '/' . $info['__PATH__'] . '/extrasetup.php');
        $this->assertFileExists($this->base . '/' . $info['__PATH__'] . '/packagexmlsetup.php');
        $this->assertFileExists($this->base . '/' . $info['__PATH__'] . '/CREDITS');
        $this->assertFileExists($this->base . '/' . $info['__PATH__'] . '/README');
        $this->assertFileExists($this->base . '/' . $info['__PATH__'] . '/API-0.1.0');
        $this->assertFileExists($this->base . '/' . $info['__PATH__'] . '/RELEASE-0.1.0');
    }
}
