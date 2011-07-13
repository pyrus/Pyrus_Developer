<?php
namespace Pyrus\Developer\PackageFile\Commands;

/**
 * This class creates the package skeleton.
 *
 * @author Greg Beaver
 * @author Brett Bieber
 * @author Helgi
 * @author Till Klampaeckel
 *
 * @see \Pyrus\Developer\PackageFile\Commands
 */
class PEAR2Skeleton
{
    /**
     * @var array $args
     */
    protected $args;
    
    /**
     * @var array $info
     */
    protected $info;
    
    /**
     * @var string
     */
    protected $extraSetupFile      = 'extrasetup.php';
    protected $packageXmlSetupFile = 'packagexmlsetup.php';
    protected $stubFile            = 'stub.php';

    /**
     * @var array $releaseFiles Release files and their content.
     * @see self::createReleaseFiles()
     */
    protected $releaseFiles = array(
        'README'        => 'Package __PACKAGE__ summary.\n\n" . "Package detailed description here (found in README)',
        'CREDITS'       => ";; put your info here\nYour Name [handle] <handle@php.net> (lead)",
        'RELEASE-0.1.0' => 'Package  __PACKAGE__ release notes for version 0.1.0.',
        'API-0.1.0'     => 'Package __PACKAGE__ API release notes for version 0.1.0.',
    );

    /**
     * @var string $templatePath Path to templates for the above.
     * @see self::__construct()
     * @see self::generate()
     */
    protected $templatePath;

    /**
     * __construct
     *
     * @param array  $args
     * @param array  $info
     * @param string $format
     *
     * @return $this
     * @throws \Pyrus\Developer\Creator\Exception When the path of the package does
     *                                            not exist.
     */
    public function __construct(array $args, array $info, $format = 'simple')
    {
        if (file_exists($info['path'])) {
            throw new \Pyrus\Developer\Creator\Exception(
                'Path ' . $info['path'] . ' already exists'
            );
        }

        if ($this->isInfoValid($info) === false) {
            throw new \Pyrus\Developer\Creator\Exception(
                "Info is missing a required key."
            );
        }

        $this->args = $args;
        $this->info = $info;

        $this->templatePath = __DIR__ . '/templates';
    }
    
    /**
     * Start creating.
     *
     * @return void
     */
    public function generate()
    {
        // creates the base package directory
        mkdir($this->info['path']);
        chdir($this->info['path']);

        $this->createMainClass();

        $this->createDirectories();
        $this->createReleaseFiles($this->info['package']);

        $extraSetup = file_get_contents($this->templatePath . '/extrasetup.php.tpl');
        file_put_contents($this->getExtraSetup(), $extraSetup);

        $packageXmlSetup = file_get_contents($this->templatePath . '/packagexmlsetup.php.tpl');
        file_put_contents($this->getPackageXmlSetup(), $packageXmlSetup);

        $this->createStub();
    }

    public function getExtraSetup()
    {
        return $this->extraSetupFile;
    }

    public function getPackageXmlSetup()
    {
        return $this->packageXmlSetupFile;
    }

    /**
     * @return array
     */
    public function getReleaseFiles()
    {
        return $this->releaseFiles;
    }

    public function getStub()
    {
        return $this->stubFile;
    }

    /**
     * Create repo/src/NameSpace/Main.php
     *
     * @return void
     * @uses   self::$info
     */
    protected function createMainClass()
    {
        $mainClass = file($this->templatePath . '/Main.php.tpl');
        $mainClass = str_replace('__MAIN_CLASS__', $this->info['mainClass'], $mainClass);
        $mainClass = str_replace('__PACKAGE__', $this->info['package'], $mainClass);
        $mainClass = str_replace('__YEAR__', date('Y'), $mainClass);
        $mainClass = str_replace('__VCS__', $this->info['svn'], $mainClass);
        $mainClass = str_replace('__MAIN_NAMESPACE__', $this->info['mainNamespace'], $mainClass);

        mkdir('src/' . $this->info['mainPath'], 0777, true);

        file_put_contents('src/' . $this->info['mainPath'] . '/Main.php', $mainClass);
    }

    protected function createDirectories()
    {
        $dirs = array('data', 'tests', 'docs', 'example', 'www'
            // 'customcommand', 'customrole', 'customtask'
        );
        foreach ($dirs as $dir) {
            mkdir($dir);
        }
    }

    /**
     * A small wrapper to create all the files necessary to create a package.
     *
     * @param string $packageName The name of the package.
     *
     * @return void
     * @throws \Pyrus\Developer\Creator\Exception On write/permission problems.
     * @uses   self::$releaseFiles
     */
    protected function createReleaseFiles($packageName)
    {
        foreach ($this->releaseFiles as $fileName => $fileContent) {
            $fileContent = str_replace('__PACKAGE__', $packageName, $fileContent);
            $status = @file_put_contents($fileName, $fileContent);
            if ($status === false) {
                throw new \Pyrus\Developer\Creator\Exception(
                    "Could not create {$fileName} for {$packageName}"
                );
            }
        }
    }

    /**
     * Create the stub.php file from the template.
     *
     * @return void
     * @uses   self::getStub()
     */
    protected function createStub()
    {
        $stub = file_get_contents($this->templatePath . '/stub.php.tpl');
        $stub = str_replace('__PACKAGE__', $this->info['package'], $stub);
        file_put_contents($this->getStub(), $stub);
    }

    /**
     * Validate the info passed into {@link self::__construct()}.
     *
     * @return boolean
     *
     * @uses self::$info
     * @see  self::__construct()
     */
    protected function isInfoValid($info)
    {
        if (!isset($info['mainClass']) || empty($info['mainClass'])) {
            return false;
        }
        if (!isset($info['package']) || empty($info['package'])) {
            return false;
        }
        if (!isset($info['svn'])) {
            return false;
        }
        if (!isset($info['mainPath'])) {
            return false;
        }
        if (!isset($info['mainNamespace']) || empty($info['mainNamespace'])) {
            return false;
        }
        return true;
    }
}
