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

namespace Pyrus\Developer\CoverageAnalyzer\Web;

use Pyrus\Developer\CoverageAnalyzer;

class Aggregator extends CoverageAnalyzer\Aggregator
{
    public $codepath;
    public $testpath;
    protected $sqlite;
    public $totallines = 0;
    public $totalcoveredlines = 0;

    /**
     * @var string $testpath Location of .phpt files
     * @var string $codepath Location of code whose coverage we are testing
     */
    public function __construct($db = ':memory:')
    {
        $this->sqlite = new CoverageAnalyzer\Sqlite($db);
        $this->codepath = $this->sqlite->codepath;
        $this->testpath = $this->sqlite->testpath;
    }

    public function retrieveLineLinks($file)
    {
        return $this->sqlite->retrieveLineLinks($file);
    }

    public function retrievePaths()
    {
        return $this->sqlite->retrievePaths();
    }

    public function retrievePathsForTest($test)
    {
        return $this->sqlite->retrievePathsForTest($test);
    }

    public function retrieveTestPaths()
    {
        return $this->sqlite->retrieveTestPaths();
    }

    public function coveragePercentage($sourcefile, $testfile = null)
    {
        return $this->sqlite->coveragePercentage($sourcefile, $testfile);
    }

    public function coverageInfo($path)
    {
        return $this->sqlite->retrievePathCoverage($path);
    }

    public function coverageInfoByTest($path, $test)
    {
        return $this->sqlite->retrievePathCoverageByTest($path, $test);
    }

    public function retrieveCoverage($path)
    {
        return $this->sqlite->retrieveCoverage($path);
    }

    public function retrieveProjectCoverage()
    {
        return $this->sqlite->retrieveProjectCoverage();
    }

    public function retrieveCoverageByTest($path, $test)
    {
        return $this->sqlite->retrieveCoverageByTest($path, $test);
    }
}
