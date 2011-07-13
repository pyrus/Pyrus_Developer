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
require_once $topLevel . '/vendor/PEAR2_Exception/src/Exception.php';
require_once $topLevel . '/vendor/PEAR2_Autoload/src/PEAR2/Autoload.php';
\PEAR2\Autoload::initialize($topLevel . '/src');

use \Pyrus\Developer\PackageFile;
use \Pyrus\Developer\PackageFile\Commands;

/**
 * A test case to cover the codemess.
 */
class PEAR2SkeletonTestCase extends \PHPUnit_Framework_TestCase
{
    protected $base;
    protected $packageName = 'PEAR2_Foo_Bar';

    protected function setUp()
    {
        $this->base = __DIR__ . '/package-test';
        @mkdir($this->base);
    }

    protected function tearDown()
    {
        system("rm -rf {$this->base}/{$this->packageName}");
    }

    /**
     * We need to verify the content of $info
     *
     * @return void
     */
    public function testPear2Skeleton()
    {
        $info = array();

        $info['path']          = $this->base . '/' . $this->packageName;
        $info['mainClass']     = 'Bar';
        $info['mainNamespace'] = '\PEAR2\Foo';
        $info['svn']           = 'http://svn.example.org/' . $this->packageName;
        $info['package']       = $this->packageName;
        $info['mainPath']      = 'PEAR2/Foo';
        $args = array();
        
        $args['package'] = $this->packageName;
        $args['channel'] = 'pear2.php.net';

        $skeleton = new Commands\PEAR2Skeleton($args, $info);
        $skeleton->generate();

        $this->assertFileExists($info['path'] . '/src/' . $info['mainPath'] . '/Main.php');

        $this->assertFileExists($info['path'] . '/' . $skeleton->getStub());
        $this->assertFileExists($info['path'] . '/' . $skeleton->getExtraSetup());
        $this->assertFileExists($info['path'] . '/' . $skeleton->getPackageXmlSetup());
        
        $releaseFiles = $skeleton->getReleaseFiles();
        foreach ($releaseFiles as $releaseFile => $fileContent) {
            $this->assertFileExists($info['path'] . '/' . $releaseFile);
        }
    }
}
