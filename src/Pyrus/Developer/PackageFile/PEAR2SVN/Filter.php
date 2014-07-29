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

namespace Pyrus\Developer\PackageFile\PEAR2SVN;

class Filter extends \FilterIterator
{
    protected $ignore;
    protected $path;
    protected $role;
    public function __construct(array $ignore, $path, $it, $role)
    {
        $this->ignore = $ignore;
        $this->path = $path;
        $this->role = $role;
        parent::__construct($it);
    }

    public function accept()
    {
        if ($this->getInnerIterator()->isDot()) {
            return false;
        }

        $path = str_replace('\\', '/', $this->path);
        $path = str_replace($path, '', $this->getInnerIterator()->current()->getPathName());
        if ($path && $path[0] === DIRECTORY_SEPARATOR) {
            $path = substr($path, 1);
        }

        // Ignore CVS folders.
        if (preg_match('@(?:^|/)CVS/@', $path)) {
            return false;
        }

        // Exclude modern VCS folders (eg. ".svn", ".hg", ".bzr", ".git")
        // and VCS files (eg. ".gitignore" & ".gitmodules").
        if (preg_match('@(?:^|/)\..*@', $path)) {
            return false;
        }

        // Ignore some common backup files:
        // "anything.bak" & "anything~".
        if (preg_match('@(?:\.bak|~)$@', $path)) {
            return false;
        }

        foreach ($this->ignore as $testpath => $type) {
            if ($type == 'file') {
                $test = $path;
            } elseif ($type == 'dir') {
                $test = dirname($path);
            }
            if (strpos($test, $testpath) !== false) {
                return false;
            }
        }
        switch($this->role) {
            case 'test':
                return $this->filterTestsDir();
        }
        return true;
    }
    
    public function filterTestsDir()
    {
        if ($this->getInnerIterator()->current()->getBasename() == 'pear2coverage.db') {
            return false;
        }
        $invalid_extensions = array('diff','exp','log','out', 'xdebug');
        $info = pathinfo($this->getInnerIterator()->current()->getPathName());
        if (!isset($info['extension'])) {
            return true;
        }
        if ($info['extension'] == 'php'
            && file_exists($info['dirname'].DIRECTORY_SEPARATOR.$info['filename'].'.phpt')) {
            // Assume this is the result of a failed .phpt test
            return false;
        }
        return !in_array($info['extension'], $invalid_extensions);
    }
}
