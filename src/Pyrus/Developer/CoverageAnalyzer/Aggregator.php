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

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

class Aggregator
{
    protected $codepath;
    protected $testpath;
    protected $sqlite;
    public $totallines = 0;
    public $totalcoveredlines = 0;

    /**
     * Creates a new aggregator.
     * 
     * @param string $testpath Location of .phpt files
     * @param string $codepath Location of code whose coverage we are testing
     * @param string $db       Location of SQLite database
     */
    public function __construct($testpath, $codepath, $db = ':memory:')
    {
        $newcodepath = realpath($codepath);
        if (!$newcodepath) {
            if (!strpos($codepath, '://') || !file_exists($codepath)) {
                // stream wrapper not found
                throw new Exception('Can not find code path ' . $codepath);
            }
        } else {
            $codepath = $newcodepath;
        }

        $files = array();
        foreach (new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $codepath,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            ),
            '/\.php$/'
        ) as $file
        ) {
            if (strpos((string) $file, '.svn') || strpos($testpath, (string)$file)) {
                continue;
            }

            $files[] = realpath((string) $file);
        }

        $this->sqlite = new Sqlite($db, $codepath, $testpath, $files);
        $this->codepath = $codepath;
        $this->sqlite->begin();

        echo "Scanning for xdebug coverage files...\n";
        $files = $this->scan($testpath);
        echo "done\n";

        echo "Parsing xdebug results\n";
        if (!count($files)) {
            echo "done (no modified xdebug files)\n";
            return;
        }

        $delete = array();
        foreach ($files as $testid => $xdebugfile) {
            $phpt = str_replace('.xdebug', '.phpt', $xdebugfile);
            if (!file_exists($phpt)) {
                $delete[] = $xdebugfile;
                continue;
            }

            $id = $this->sqlite->addTest($phpt);
            echo '(' . $testid . ' of ' . count($files) . ') ' . $xdebugfile;
            $this->retrieveXdebug($xdebugfile, $id);
            echo "\ndone\n";
        }

        $this->sqlite->addNoCoverageFiles();
        $this->sqlite->updateAllLines();
        $this->sqlite->updateTotalCoverage();
        $this->sqlite->commit();

        if (count($delete)) {
            echo "\nNote: The following .xdebug files were outdated relics " .
                "and have been deleted\n";
            foreach ($delete as $d) {
                unlink($d);
                echo "$d\n";
            }
            echo "\n";
        }
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

    public function retrieveCoverageByTest($path, $test)
    {
        return $this->sqlite->retrieveCoverageByTest($path, $test);
    }

    public function retrieveProjectCoverage()
    {
        return $this->sqlite->retrieveProjectCoverage();
    }

    public function retrieveXdebug($path, $testid)
    {
        if (file_exists($path) === false) {
            return;
        }

        $source = '$xdebug = ' . file_get_contents($path) . ";\n";
        eval($source);
        $this->sqlite->addCoverage(
            str_replace('.xdebug', '.phpt', $path),
            $testid,
            $xdebug
        );
    }

    public function scan($path)
    {
        $testpath = realpath($path);
        if (!$testpath) {
            throw new Exception('Unable to process path' . $path);
        }

        $this->testpath = str_replace('\\', '/', $testpath);

        // get a list of all xdebug files
        $xdebugs = array();
        foreach (new RegexIterator(
            new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $this->testpath,
                    RecursiveDirectoryIterator::SKIP_DOTS
                )
            ),
            '/\.xdebug$/'
        ) as $file
        ) {
            if (strpos((string) $file, '.svn')) {
                continue;
            }

            $xdebugs[] = realpath((string) $file);
        }
        echo count($xdebugs), " total...\n";

        $unmodified = $modified = array();
        foreach ($xdebugs as $path) {
            if ($this->sqlite->unChangedXdebug($path)) {
                $unmodified[$path] = true;
                continue;
            }

            $modified[] = $path;
        }

        $xdebugs = $modified;
        sort($xdebugs);
        // index from 1
        array_unshift($xdebugs, '');
        unset($xdebugs[0]);
        $test = array_flip($xdebugs);

        echo "\n\n";
        foreach ($this->sqlite->retrieveTestPaths() as $path) {
            $xdebugpath = str_replace('.phpt', '.xdebug', $path);
            if (isset($test[$xdebugpath]) || isset($unmodified[$xdebugpath])) {
                continue;
            }

            // remove outdated tests
            echo "Removing results from $xdebugpath\n";
            $this->sqlite->removeOldTest($path);
        }

        return $xdebugs;
    }

    public function render($toPath)
    {
        $decorator = new DefaultSourceDecorator(
            $toPath,
            $this->testpath,
            $this->codepath
        );
        echo "Generating project coverage data...\n";
        $coverage = $this->sqlite->retrieveProjectCoverage();
        echo "done\n";
        $decorator->renderSummary(
            $this,
            $this->retrievePaths(),
            $this->codepath,
            false,
            $coverage[1],
            $coverage[0],
            $coverage[2]
        );
        $a = $this->codepath;
        echo "[Step 2 of 2] Rendering per-test coverage...\n";
        $decorator->renderTestCoverage($this, $this->testpath, $a);
        echo "done\n";
    }
}
