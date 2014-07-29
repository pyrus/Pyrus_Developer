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

namespace Pyrus\Developer\CoverageAnalyzer;

abstract class AbstractSourceDecorator
{
    abstract public function render(SourceFile $source);
    abstract public function renderSummary(
        Aggregator $agg,
        array $results,
        $basePath,
        $istest = false,
        $total = 1,
        $covered = 1
    );
    abstract public function renderTestCoverage(
        Aggregator $agg,
        $testpath,
        $basePath
    );
}
