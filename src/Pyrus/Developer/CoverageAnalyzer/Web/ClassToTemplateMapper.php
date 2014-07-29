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

class ClassToTemplateMapper extends \PEAR2\Templates\Savant\ClassToTemplateMapper
{

    public function map($class)
    {
        if ($class == 'Pyrus\Developer\CoverageAnalyzer\SourceFile\PerTest') {
            return 'SourceFile.tpl.php';
        }
        $class = str_replace(
            array(
                'Pyrus\Developer\CoverageAnalyzer\Web',
                'Pyrus\Developer\CoverageAnalyzer'
            ),
            '',
            $class
        );
        return parent::map($class);
    }
}
