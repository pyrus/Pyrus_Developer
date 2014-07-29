<?php
if (!Phar::canWrite()) {
    die('pass -d phar.readonly=0 to this script');
}

$phar = new Phar(__DIR__ . '/pear2coverage.phar.php');
$phar->setStub(
    '<?php
function __autoload($class)
{
    $class = str_replace("Pyrus\\Developer\\CoverageAnalyzer\\\", "", $class);
    var_dump($class);
    include "phar://" . __FILE__ . "/" . str_replace("\\\\", "/", $class) . ".php";
}
Phar::webPhar("pear2coverage.phar.php");
echo "This phar is a web application, run within your web browser to use\n";
exit -1;
__HALT_COMPILER();'
);

$path = __DIR__ . '/../../../../../../pear2/PEAR2_Templates_Savant/src/';
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($path),
    RecursiveIteratorIterator::SELF_FIRST
);
while ($iterator->valid()) {
    if ($iterator->isDot()) {
        $iterator->next();
        continue;
    }


    $file = $iterator->current()->getPathName();
    $phar[str_replace($path, '', $file)] = file_get_contents(realpath($file));
    $iterator->next();
}

foreach (scandir(__DIR__ . '/Web/') as $file) {
    if ($file{0} === '.') {
        continue;
    }

    $phar['Web/' . $file] = file_get_contents(__DIR__ . '/Web/' . $file);
}


$phar['SourceFile.php']         = file_get_contents(__DIR__ . '/SourceFile.php');
$phar['Aggregator.php']         = file_get_contents(__DIR__ . '/Aggregator.php');
$phar['Exception.php']          = file_get_contents(__DIR__ . '/Exception.php');
$phar['Sqlite.php']             = file_get_contents(__DIR__ . '/Sqlite.php');
$phar['SourceFile/PerTest.php'] = file_get_contents(
    __DIR__ . '/SourceFile/PerTest.php'
);

$phar['cover.css'] = file_get_contents(
    __DIR__ . '/../../../../www/CoverageAnalyzer/cover.css'
);
$phar['index.php'] = '<?php
namespace Pyrus\Developer\CoverageAnalyzer {
    ini_set("display_errors", true);
    session_start();
    $view = new Web\View;
    $rooturl = parse_url($_SERVER["REQUEST_URI"]);
    $rooturl = $rooturl["path"];

    $controller = new Web\Controller($_GET);
    $controller::$rooturl = $rooturl;

    $savant = new \PEAR2\Templates\Savant\Main();
    $savant->setClassToTemplateMapper(new Web\ClassToTemplateMapper);
    $savant->setTemplatePath(
        __DIR__ . "/../../../../www/CoverageAnalyzer/templates"
    );
    $savant->setEscape("htmlentities");
    echo $savant->render($controller);
}';
