<?php
if (!Phar::canWrite()) {
    die('pass -d phar.readonly=0 to this script');
}
$phar = new Phar(__DIR__ . '/pear2coverage.phar.php');
$phar->setStub('<?php
function __autoload($class)
{
    $class = str_replace("pear2\\Pyrus\\Developer\\CoverageAnalyzer", "", $class);
    include "phar://" . __FILE__ . str_replace("\\\\", "/", $class) . ".php";
}
Phar::webPhar("pear2coverage.phar.php");
echo "This phar is a web application, run within your web browser to use\n";
exit -1;
__HALT_COMPILER();');

$phar['Web/Controller.php'] = file_get_contents(__DIR__ . '/Web/Controller.php');
$phar['Web/View.php'] = file_get_contents(__DIR__ . '/Web/View.php');
$phar['Web/Aggregator.php'] = file_get_contents(__DIR__ . '/Web/Aggregator.php');
$phar['Web/Exception.php'] = file_get_contents(__DIR__ . '/Web/Exception.php');

$phar['SourceFile.php'] = file_get_contents(__DIR__ . '/SourceFile.php');
$phar['Aggregator.php'] = file_get_contents(__DIR__ . '/Aggregator.php');
$phar['Exception.php'] = file_get_contents(__DIR__ . '/Exception.php');
$phar['Sqlite.php'] = file_get_contents(__DIR__ . '/Sqlite.php');
$phar['SourceFile/PerTest.php'] = file_get_contents(__DIR__ . '/SourceFile/PerTest.php');

$phar['cover.css'] = '
.ln {background-color:#f6bd0f; padding-right: 4px;}
.cv {background-color:#afd8f8;}
.nc {background-color:#d64646;}
.dead {background-color:#ff8e46;}

ul { list-style-type: none; }

div.bad, div.ok, div.good {
    white-space:pre;
    font-family:courier;
    width: 160px;
    float: left;
    margin-right: 10px;
}
.bad {background-color:#d64646; }
.ok {background-color:#f6bd0f; }
.good {background-color:#588526;}
';
$phar['index.php'] = '<?php
namespace pear2\Pyrus\Developer\CoverageAnalyzer {
session_start();
$view = new Web\View;
$rooturl = parse_url($_SERVER["REQUEST_URI"]);
$rooturl = $rooturl["path"];
$controller = new Web\Controller($view, $rooturl);
$controller->route();
}';
?>
