<?php
$coverage = $context->retrieveProjectCoverage();
list($covered, $total, $dead) = $coverage;
?>
  <h2>Code Coverage Files</h2>
  <h3>Total lines: <?php echo $total; ?>, covered lines: <?php echo $covered; ?>, dead lines: <?php echo $dead; ?></h3>

    <?php
    $percent = 0;
    if ($total > 0) {
        $percent = round(($covered / $total) * 100, 1);
    }

    function getClass($percent)
    {
        if ($percent < 50) {
            return 'bad';
        } elseif ($percent < 75) {
            return 'ok';
        } else {
            return 'good';
        }
    }
    
    ?>
    <p class="<?php echo getClass($percent); ?>"><?php echo $percent; ?>% code coverage</p>
    <p>
    <a href="/workspace/PEAR2/Pyrus_Developer/www/CoverageAnalyzer/?test=TOC">Code Coverage per PHPT test</a>
    </p>

  <ul>
   <?php foreach ($context as $sourceFile): ?>
   <li>
    <div class="<?php echo getClass($sourceFile->coveragePercentage()); ?>"><?php echo ' Coverage: ' . str_pad($sourceFile->coveragePercentage() . '%', 4, ' ', STR_PAD_LEFT); ?></div>
    <a href="<?php echo $parent->context->getFileLink($sourceFile->name()); ?>"><?php echo $sourceFile->shortName(); ?></a>
   </li>
   <?php endforeach; ?>
  </ul>
