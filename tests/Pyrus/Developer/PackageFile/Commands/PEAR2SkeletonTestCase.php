<?php
/**
 * @desc oh no, it uses PHPUnit, deal with it ;-)
 * @ignore
 */
require_once 'PHPUnit/Autoload.php';

/**
 * @desc $topLevel Root dir.
 */
$topLevel = __DIR__ . '/../../../../..';

/**
 * @ignore
 */
require_once $topLevel . '/../Pyrus/vendor/php/PEAR2/Exception.php';
require_once $topLevel . '/../Pyrus/vendor/php/PEAR2/Autoload.php';
\PEAR2\Autoload::initialize($topLevel . '/src');

use \Pyrus\Developer\PackageFile;
use \Pyrus\Developer\PackageFile\Commands;

/**
 * A test case to cover the codemess.
 */
class PEAR2SkeletonTestCase extends \PHPUnit_Framework_TestCase
{
    protected $base;
    protected $packageName = 'PEAR2_Foo';

    protected function setUp()
    {
        $this->base = __DIR__ . '/package-test';
        @mkdir($this->base);
        chdir($this->base);
    }

    protected function tearDown()
    {
        exec("rm -rf {$this->base}/Foo");
    }

    /**
     * Info array is just like the array returned from
     * {@link Pyrus\Developer\PackageFile\Commands::parsePackageName()}
     *
     * @return void
     */
    public function testPear2Skeleton()
    {
        $info = array();

        $info['path']          = 'Foo';
        $info['mainPath']      = 'Foo';
        $info['mainClass']     = 'PEAR2\Foo\Main';
        $info['mainNamespace'] = 'PEAR2\Foo';
        $info['svn']           = 'http://svn.php.net/repository/pear2/PEAR2_Foo';
        $info['package']       = $this->packageName;

        $skeleton = new Commands\PEAR2Skeleton($info);
        $skeleton->generate();

        $this->assertFileExists($this->base . '/' . $info['path'] . '/src/' . $info['mainPath'] . '/Main.php');

        $this->assertFileExists($this->base . '/' . $info['path'] . '/' . $skeleton->getStub());
        $this->assertFileExists($this->base . '/' . $info['path'] . '/' . $skeleton->getExtraSetup());
        $this->assertFileExists($this->base . '/' . $info['path'] . '/' . $skeleton->getPackageXmlSetup());
        
        $releaseFiles = $skeleton->getReleaseFiles();
        foreach ($releaseFiles as $releaseFile => $fileContent) {
            $this->assertFileExists($this->base . '/' . $info['path'] . '/' . $releaseFile);
        }
    }
}
