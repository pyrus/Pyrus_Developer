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

class SourceFile
{
    protected $source;
    protected $path;
    protected $sourcepath;
    protected $coverage;
    protected $aggregator;
    protected $testpath;
    protected $linelinks;

    public function __construct(
        $path,
        Aggregator $agg,
        $testpath,
        $sourcepath,
        $coverage = true
    ) {
        $this->source = file($path);
        $this->path = $path;
        $this->sourcepath = $sourcepath;

        array_unshift($this->source, '');
        unset($this->source[0]); // make source array indexed by line number

        $this->aggregator = $agg;
        $this->testpath = $testpath;
        if ($coverage === true) {
            $this->setCoverage();
        }
    }

    public function setCoverage()
    {
        $this->coverage = $this->aggregator->retrieveCoverage($this->path);
    }

    public function aggregator()
    {
        return $this->aggregator;
    }

    public function testpath()
    {
        return $this->testpath;
    }

    public function render(AbstractSourceDecorator $decorator = null)
    {
        if ($decorator === null) {
            $decorator = new DefaultSourceDecorator('.');
        }
        return $decorator->render($this);
    }

    public function coverage($line = null)
    {
        if ($line === null) {
            return $this->coverage;
        }

        if (!isset($this->coverage[$line])) {
            return false;
        }

        return $this->coverage[$line];
    }

    public function coveragePercentage()
    {
        return $this->aggregator->coveragePercentage($this->path);
    }

    /**
     * Get all the coverage info for this file
     *
     * @return array(covered, total, dead)
     */
    public function coverageInfo()
    {
        return $this->aggregator->coverageInfo($this->path);
    }

    public function name()
    {
        return $this->path;
    }

    public function shortName()
    {
        return str_replace($this->sourcepath . DIRECTORY_SEPARATOR, '', $this->path);
    }

    public function source()
    {
        $cov = $this->coverage();
        if (empty($cov)) {
            return $this->source;
        }

        /* Make sure we have as many lines as required
         * Sometimes Xdebug returns coverage on one line beyond what
         * our file has, this is PHP doing a return on the file.
         */
        $endLine = max(array_keys($cov));
        if (count($this->source) < $endLine) {
            // Add extra new line if required since we use <pre> to format
            $secondLast = $endLine - 1;
            $this->source[$secondLast] = str_replace(
                "\r",
                '',
                $this->source[$secondLast]
            );
            $len = strlen($this->source[$secondLast]) - 1;
            if (substr($this->source[$secondLast], $len) != "\n") {
                $this->source[$secondLast] .= "\n";
            }

            $this->source[$endLine] = "\n";
        }

        return $this->source;
    }

    public function coveredLines()
    {
        $info = $this->aggregator->coverageInfo($this->path);
        return $info[0];
    }

    public function getLineLinks($line)
    {
        if (!isset($this->linelinks)) {
            $this->linelinks = $this->aggregator->retrieveLineLinks($this->path);
        }

        if (isset($this->linelinks[$line])) {
            return $this->linelinks[$line];
        }

        return false;
    }
}
