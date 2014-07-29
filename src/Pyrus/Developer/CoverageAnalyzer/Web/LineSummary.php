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

use Pyrus\Developer\CoverageAnalyzer\SourceFile;

class LineSummary extends \ArrayIterator
{
    public $source;
    public $line;

    public function __construct($source, $line)
    {
        $this->source = $source;
        $this->line   = $line;
        parent::__construct($source->getLineLinks($this->line));
    }

    public function __call($method, $args)
    {
        return $this->source->$method();
    }

    public function __get($var)
    {
        return $this->source->$var;
    }
}
