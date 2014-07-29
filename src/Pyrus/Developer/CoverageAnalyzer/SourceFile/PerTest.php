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

namespace Pyrus\Developer\CoverageAnalyzer\SourceFile;

use Pyrus\Developer\CoverageAnalyzer\AbstractSourceDecorator;
use Pyrus\Developer\CoverageAnalyzer\Aggregator;
use Pyrus\Developer\CoverageAnalyzer\DefaultSourceDecorator;
use Pyrus\Developer\CoverageAnalyzer\SourceFile;

class PerTest extends SourceFile
{
    protected $testname;

    public function __construct(
        $path,
        Aggregator $agg,
        $testpath,
        $sourcepath,
        $testname,
        $coverage = true
    ) {
        $this->testname = $testname;
        parent::__construct($path, $agg, $testpath, $sourcepath, $coverage);
    }

    public function setCoverage()
    {
        $this->coverage = $this->aggregator->retrieveCoverageByTest(
            $this->path,
            $this->testname
        );
    }

    public function coveredLines()
    {
        $info = $this->aggregator->coverageInfoByTest($this->path, $this->testname);
        return $info[0];
    }

    public function render(AbstractSourceDecorator $decorator = null)
    {
        if ($decorator === null) {
            $decorator = new DefaultSourceDecorator('.');
        }
        return $decorator->render($this, $this->testname);
    }

    public function coveragePercentage()
    {
        return $this->aggregator->coveragePercentage($this->path, $this->testname);
    }

    public function coverageInfo()
    {
        return $this->aggregator->coverageInfoByTest($this->path, $this->testname);
    }
}
