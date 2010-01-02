<?php
namespace pear2\Pyrus\Developer\CoverageAnalyzer;
class Sqlite
{
    protected $db;
    protected $totallines = 0;
    protected $coveredlines = 0;
    protected $deadlines = 0;
    protected $pathCovered = array();
    protected $pathTotal = array();
    protected $pathDead = array();
    public $codepath;
    public $testpath;

    private $statement;

    const COVERAGE_COVERED      = 1;
    const COVERAGE_NOT_EXECUTED = 0;
    const COVERAGE_NOT_COVERED  = -1;
    const COVERAGE_DEAD         = -2;

    function __construct($path = ':memory:', $codepath = null, $testpath = null)
    {
        $this->db = new \Sqlite3($path);

        $sql = 'SELECT version FROM analyzerversion';
        if (@$this->db->querySingle($sql) == '5.2.0') {
            $this->codepath = $this->db->querySingle('SELECT codepath FROM paths');
            $this->testpath = $this->db->querySingle('SELECT testpath FROM paths');
            return;
        }

        // restart the database
        echo "Upgrading database to version 5.1.0";
        if (!$codepath || !$testpath) {
            throw new Exception('Both codepath and testpath must be set in ' .
                                'order to initialize a coverage database');
        }

        $this->codepath = $codepath;
        $this->testpath = $testpath;
        $this->db->exec('DROP TABLE IF EXISTS coverage;');
        echo ".";
        $this->db->exec('DROP TABLE IF EXISTS coverage_nonsource;');
        echo ".";
        $this->db->exec('DROP TABLE IF EXISTS not_covered;');
        echo ".";
        $this->db->exec('DROP TABLE IF EXISTS files;');
        echo ".";
        $this->db->exec('DROP TABLE IF EXISTS tests;');
        echo ".";
        $this->db->exec('DROP TABLE IF EXISTS paths;');
        echo ".";
        $this->db->exec('DROP TABLE IF EXISTS coverage_per_file;');
        echo ".";
        $this->db->exec('DROP TABLE IF EXISTS line_info;');
        echo ".";
        $this->db->exec('DROP TABLE IF EXISTS all_lines;');
        echo ".";
        $this->db->exec('DROP TABLE IF EXISTS xdebugs;');
        echo ".";
        $this->db->exec('DROP TABLE IF EXISTS analyzerversion;');
        echo ".";
        $this->db->exec('VACUUM;');

        echo ".";
        $this->db->exec('BEGIN');

        $query = '
            CREATE TABLE coverage (
              files_id integer NOT NULL,
              tests_id integer NOT NULL,
              linenumber INTEGER NOT NULL,
              state INTEGER NOT NULL,
              PRIMARY KEY (files_id, linenumber, tests_id)
            );

            CREATE INDEX idx_coveragestats ON coverage (files_id, tests_id, state);

            CREATE TABLE all_lines (
              files_id integer NOT NULL,
              linenumber INTEGER NOT NULL,
              state INTEGER NOT NULL,
              PRIMARY KEY (files_id, linenumber, state)
            );

             CREATE INDEX idx_all_lines_stats ON all_lines (files_id, linenumber);

            CREATE TABLE line_info (
              files_id integer NOT NULL,
              covered INTEGER NOT NULL,
              dead  INTEGER NOT NULL,
              total INTEGER NOT NULL,
              PRIMARY KEY (files_id)
            );
          ';
        $worked = $this->db->exec($query);
        if (!$worked) {
            @$this->db->exec('ROLLBACK');
            $error = $this->db->lastErrorMsg();
            throw new Exception('Unable to create Code Coverage SQLite3 database: ' . $error);
        }

        echo ".";
        $query = '
          CREATE TABLE coverage_nonsource (
            files_id integer NOT NULL,
            tests_id integer NOT NULL,
            PRIMARY KEY (files_id, tests_id)
          );
          ';
        $worked = $this->db->exec($query);
        if (!$worked) {
            @$this->db->exec('ROLLBACK');
            $error = $this->db->lastErrorMsg();
            throw new Exception('Unable to create Code Coverage SQLite3 database: ' . $error);
        }

        echo ".";
        $query = '
          CREATE TABLE files (
            id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            filepath TEXT(500) NOT NULL,
            filepathmd5 TEXT(32) NOT NULL,
            issource BOOL NOT NULL,
            UNIQUE (filepath)
          );
          CREATE INDEX files_issource on files (issource);
          ';
        $worked = $this->db->exec($query);
        if (!$worked) {
            @$this->db->exec('ROLLBACK');
            $error = $this->db->lastErrorMsg();
            throw new Exception('Unable to create Code Coverage SQLite3 database: ' . $error);
        }

        echo ".";
        $query = '
          CREATE TABLE xdebugs (
            xdebugpath TEXT(500) NOT NULL,
            xdebugpathmd5 TEXT(32) NOT NULL,
            PRIMARY KEY (xdebugpath)
          );';
        $worked = $this->db->exec($query);
        if (!$worked) {
            @$this->db->exec('ROLLBACK');
            $error = $this->db->lastErrorMsg();
            throw new Exception('Unable to create Code Coverage SQLite3 database: ' . $error);
        }

        echo ".";
        $query = '
          CREATE TABLE tests (
            id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
            testpath TEXT(500) NOT NULL,
            testpathmd5 TEXT(32) NOT NULL,
            UNIQUE (testpath)
          );';
        $worked = $this->db->exec($query);
        if (!$worked) {
            @$this->db->exec('ROLLBACK');
            $error = $this->db->lastErrorMsg();
            throw new Exception('Unable to create Code Coverage SQLite3 database: ' . $error);
        }

        echo ".";
        $query = '
          CREATE TABLE analyzerversion (
            version TEXT(5) NOT NULL
          );

          INSERT INTO analyzerversion VALUES("5.2.0");

          CREATE TABLE paths (
            codepath TEXT NOT NULL,
            testpath TEXT NOT NULL
          );';
        $worked = $this->db->exec($query);
        if (!$worked) {
            @$this->db->exec('ROLLBACK');
            $error = $this->db->lastErrorMsg();
            throw new Exception('Unable to create Code Coverage SQLite3 database: ' . $error);
        }

        echo ".";
        $query = '
          INSERT INTO paths VALUES(
            "' . $this->db->escapeString($codepath) . '",
            "' . $this->db->escapeString($testpath). '");';
        $worked = $this->db->exec($query);
        if (!$worked) {
            @$this->db->exec('ROLLBACK');
            $error = $this->db->lastErrorMsg();
            throw new Exception('Unable to create Code Coverage SQLite3 database: ' . $error);
        }
        $this->db->exec('COMMIT');
        echo "done\n";
    }

    function retrieveLineLinks($file, $id = null)
    {
        if ($id === null) {
            $id = $this->getFileId($file);
        }

        $query = 'SELECT t.testpath, c.linenumber
            FROM
                coverage c, tests t
            WHERE
                c.files_id = ' . $id . ' AND t.id = c.tests_id';
        $result = $this->db->query($query);
        if (!$result) {
            $error = $this->db->lastErrorMsg();
            throw new Exception('Cannot retrieve line links for ' . $file .
                                ' line #' . $line .  ': ' . $error);
        }

        $ret = array();
        while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
            $ret[$res['linenumber']][] = $res['testpath'];
        }
        return $ret;
    }

    function retrieveTestPaths()
    {
        $query = 'SELECT testpath from tests ORDER BY testpath';
        $result = $this->db->query($query);
        if (!$result) {
            $error = $this->db->lastErrorMsg();
            throw new Exception('Cannot retrieve test paths :' . $error);
        }
        $ret = array();
        while ($res = $result->fetchArray(SQLITE3_NUM)) {
            $ret[] = $res[0];
        }
        return $ret;
    }

    function retrievePathsForTest($test, $all = 0)
    {
        $id = $this->getTestId($test);
        $ret = array();
        if ($all) {
            $query = 'SELECT DISTINCT filepath
                FROM coverage_nonsource c, files
                WHERE c.tests_id = ' . $id . '
                    AND files.id = c.files_id
                GROUP BY c.files_id
                ORDER BY filepath';
            $result = $this->db->query($query);
            if (!$result) {
                $error = $this->db->lastErrorMsg();
                throw new Exception('Cannot retrieve file paths for test ' . $test . ':' . $error);
            }

            while ($res = $result->fetchArray(SQLITE3_NUM)) {
                $ret[] = $res[0];
            }
        }

        $query = 'SELECT DISTINCT filepath
            FROM coverage c, files
            WHERE
                c.tests_id = ' . $id . '
              AND
                files.id = c.files_id
            GROUP BY c.files_id
            ORDER BY filepath';
        $result = $this->db->query($query);
        if (!$result) {
            $error = $this->db->lastErrorMsg();
            throw new Exception('Cannot retrieve file paths for test ' . $test . ':' . $error);
        }

        while ($res = $result->fetchArray(SQLITE3_NUM)) {
            $ret[] = $res[0];
        }

        return $ret;
    }

    function retrievePaths($all = 0)
    {
        if ($all) {
            $query = 'SELECT filepath from files ORDER BY filepath';
        } else {
            $query = 'SELECT filepath from files WHERE issource=1 ORDER BY filepath';
        }

        $result = $this->db->query($query);
        if (!$result) {
            $error = $this->db->lastErrorMsg();
            throw new Exception('Cannot retrieve file paths :' . $error);
        }

        $ret = array();
        while ($res = $result->fetchArray(SQLITE3_NUM)) {
            $ret[] = $res[0];
        }

        return $ret;
    }

    function coveragePercentage($sourcefile, $testfile = null)
    {
        if ($testfile) {
            $coverage = $this->retrievePathCoverageByTest($sourcefile, $testfile);
        } else {
            $coverage = $this->retrievePathCoverage($sourcefile);
        }

        if ($coverage[1]) {
            return round(($coverage[0] / $coverage[1]) * 100, 1);
        }

        return 0;
    }

    function retrieveProjectCoverage($path = null)
    {
        if ($this->totallines) {
            return array($this->coveredlines, $this->totallines, $this->deadlines);
        }

        $query = '
            SELECT covered, total, dead, filepath
            FROM line_info, files
            WHERE files.id = line_info.files_id';
        if ($path !== null) {
            $query .= ' AND files.filepath = "' . $this->db->escapeString($path) . '"';
        }

        $result = $this->db->query($query);
        if (!$result) {
            $error = $this->db->lastErrorMsg();
            throw new Exception('Cannot retrieve coverage for ' . $path.  ': ' . $error);
        }

        while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
            $this->pathTotal[$res['filepath']]   = $res['total'];
            $this->pathCovered[$res['filepath']] = $res['covered'];
            $this->pathDead[$res['filepath']]    = $res['dead'];
            $this->coveredlines += $res['covered'];
            $this->totallines   += $res['total'];
            $this->deadlines    += $res['dead'];
        }

        return array($this->coveredlines, $this->totallines, $this->deadlines);
    }

    function retrievePathCoverage($path)
    {
        if (!$this->totallines) {
            // set up the cache
            $this->retrieveProjectCoverage($path);
        }

        if (!isset($this->pathCovered[$path])) {
            return array(0, 0, 0);
        }

        return array($this->pathCovered[$path], $this->pathTotal[$path], $this->pathDead[$path]);
    }

    function retrievePathCoverageByTest($path, $test)
    {
        $id = $this->getFileId($path);
        $testid = $this->getTestId($test);

        $query = '
            SELECT state, COUNT(linenumber) AS ln
            FROM coverage
            WHERE files_id = ' . $id. ' AND tests_id = ' . $testid . '
            GROUP BY state';
        $result = $this->db->query($query);
        if (!$result) {
            $error = $this->db->lastErrorMsg();
            throw new Exception('Cannot retrieve path coverage for ' . $path .
                                ' in test ' . $test . ': ' . $error);
        }

        $total = $dead = $covered = 0;
        while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
            if ($res['state'] === Sqlite::COVERAGE_COVERED) {
                $covered = $res['ln'];
            }

            if ($res['state'] === Sqlite::COVERAGE_DEAD) {
                $dead = $res['ln'];
            }

            $total += $res['ln'];
        }

        return array($covered, $total, $dead);
    }

    function retrieveCoverageByTest($path, $test)
    {
        $id = $this->getFileId($path);
        $testid = $this->getTestId($test);

        $query = 'SELECT state AS coverage, linenumber FROM coverage
                    WHERE files_id = ' . $id . ' AND tests_id = ' . $testid . '
                    ORDER BY linenumber ASC';
        $result = $this->db->query($query);
        if (!$result) {
            $error = $this->db->lastErrorMsg();
            throw new Exception('Cannot retrieve test ' . $test .
                                ' coverage for ' . $path.  ': ' . $error);
        }

        $ret = array();
        while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
            $ret[$res['linenumber']] = $res['coverage'];
        }

        return $ret;
    }

    function getFileId($path)
    {
        $query = 'SELECT id FROM files WHERE filepath = "' . $this->db->escapeString($path) .'"';
        $id = $this->db->querySingle($query);
        if ($id === false || $id === null) {
            throw new Exception('Unable to retrieve file ' . $path . ' id from database');
        }

        return $id;
    }

    function getTestId($path)
    {
        $query = 'SELECT id FROM tests WHERE testpath = "' . $this->db->escapeString($path) . '"';
        $id = $this->db->querySingle($query);
        if ($id === false || $id === null) {
            throw new Exception('Unable to retrieve test file ' . $path . ' id from database');
        }

        return $id;
    }

    function removeOldTest($testpath, $id = null)
    {
        if ($id === null) {
            $id = $this->getTestId($testpath);
        }

        echo "deleting old test ", $testpath,'.';
        $this->db->exec('DELETE FROM tests WHERE id = ' . $id);
        echo '.';
        $this->db->exec('DELETE FROM coverage WHERE tests_id = ' . $id);
        echo '.';
        $this->db->exec('DELETE FROM coverage_nonsource WHERE tests_id = ' . $id);
        echo '.';
        $this->db->exec('DELETE FROM xdebugs WHERE xdebugpath = "' .
                        $this->db->escapeString(str_replace('.phpt', '.xdebug', $testpath)) . '"');
        echo "done\n";
    }

    function addTest($testpath, $id = null)
    {
        try {
            $id = $this->getTestId($testpath);
            $this->db->exec('UPDATE tests SET testpathmd5 = "' . md5_file($testpath) . '" WHERE id = ' . $id);
        } catch (Exception $e) {
            echo "Adding new test $testpath\n";
            $query = 'INSERT INTO tests (testpath, testpathmd5) VALUES(:testpath, :md5)';
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':testpath', $testpath);
            $stmt->bindValue(':md5', md5_file($testpath));
            $stmt->execute();
            $id = $this->db->lastInsertRowID();
        }

        $file  = str_replace('.phpt', '.xdebug', $testpath);
        $query = 'REPLACE INTO xdebugs (xdebugpath, xdebugpathmd5) VALUES(:testpath, :md5)';
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':testpath', $file);
        $stmt->bindValue(':md5', md5_file($file));
        $stmt->execute();
        return $id;
    }

    function unChangedXdebug($path)
    {
        $query = 'SELECT xdebugpathmd5
                  FROM xdebugs
                  WHERE xdebugpath = "' . $this->db->escapeString($path) . '"';
        $md5 = $this->db->querySingle($query);
        if (!$md5 || $md5 != md5_file($path)) {
            return false;
        }

        return true;
    }

    function retrieveCoverage($path)
    {
        $id = $this->getFileId($path);
        $links = $this->retrieveLineLinks($path, $id);
        $links = array_map(function ($arr) {return count($arr);}, $links);

        $query = '
            SELECT state AS coverage, linenumber
            FROM all_lines
            WHERE files_id = ' . $id . '
            ORDER BY linenumber ASC';
        $result = $this->db->query($query);
        if (!$result) {
            $error = $this->db->lastErrorMsg();
            throw new Exception('Cannot retrieve coverage for ' . $path.  ': ' . $error);
        }

        $return = array();
        while ($res = $result->fetchArray()) {
            if (!isset($return[$res['linenumber']])) {
                $return[$res['linenumber']] = array();
            }

            if (
                !isset($return[$res['linenumber']]['coverage']) ||
                $return[$res['linenumber']]['coverage'] !== Sqlite::COVERAGE_COVERED
            ) {
                // Found a case where a line could be dead and not covered, we still don't know why
                if (
                    isset($return[$res['linenumber']]['coverage']) &&
                    $return[$res['linenumber']]['coverage'] === Sqlite::COVERAGE_NOT_COVERED &&
                    $res['coverage'] === Sqlite::COVERAGE_DEAD
                ) {
                    continue;
                }

                $return[$res['linenumber']]['coverage'] = $res['coverage'];
            }


            if (isset($links[$res['linenumber']])) {
                $return[$res['linenumber']]['link'] = $links[$res['linenumber']];
            } else {
                $return[$res['linenumber']]['link'] = 0;
            }
        }

        return $return;
    }

    function updateTotalCoverage()
    {
        echo "Updating coverage per-file intermediate table\n";

        $query = '
            SELECT COUNT(DISTINCT linenumber) AS ln, state, files_id
            FROM all_lines
            GROUP BY files_id, state';
        $result = $this->db->query($query);

        echo ".";
        if (!$result) {
            $error = $this->db->lastErrorMsg();
            throw new Exception('Cannot retrieve coverage for ' . $path.  ': ' . $error);
        }

        $ret = array();
        while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!isset($ret[$res['files_id']]['covered'])) {
                $ret[$res['files_id']]['covered']     = 0;
                $ret[$res['files_id']]['dead']        = 0;
                $ret[$res['files_id']]['not_covered'] = 0;
            }

            if ($res['state'] === Sqlite::COVERAGE_COVERED) {
                $ret[$res['files_id']]['covered'] = $res['ln'];
            }

            if ($res['state'] === Sqlite::COVERAGE_NOT_COVERED) {
                $ret[$res['files_id']]['not_covered'] = $res['ln'];
            }

            if ($res['state'] === Sqlite::COVERAGE_DEAD) {
                $ret[$res['files_id']]['dead'] = $res['ln'];
            }
        }

        foreach ($ret as $id => $line) {
            if (!isset($line['covered'])) {
                // this file has no coverage any more (was deleted), remove it
                $this->db->exec('DELETE FROM all_lines WHERE files_id = ' . $id);
                $this->db->exec('DELETE FROM files WHERE id = ' . $id);
                continue;
            }

            $covered     = $line['covered'];
            $dead        = $line['dead'];
            $not_covered = $line['not_covered'];
            $this->db->exec('REPLACE INTO line_info (files_id, covered, dead, total)
                            VALUES(' . $id . ',' . $covered . ',' . $dead . ',' . ($covered + $not_covered) . ')');
            echo ".";
        }

        echo "done\n";
    }

    function updateAllLines($id, $results)
    {
        $query = '
            SELECT linenumber, state
            FROM all_lines
            WHERE files_id = ' . $id . '
            ORDER BY linenumber ASC';
        $result = $this->db->query($query);
        $lines = array();
        while ($res = $result->fetchArray(SQLITE3_NUM)) {
            $lines[$res[0]] = $res[1];
        }

        // Figure out which lines have changed.
        $new = array_diff_assoc($results, $lines);

        // See if any lines have been removed
        $old = array_diff(array_keys($lines), array_keys($results));

        if (count($new)) {
            if (!isset($this->statement->all_lines)) {
                $query = 'INSERT INTO all_lines
                          (files_id, linenumber, state)
                          VALUES (:id, :line, :state)';
                $this->statement->all_lines = $this->db->prepare($query);
            }

            if (!isset($this->statement->all_lines_update)) {
                $query = 'UPDATE all_lines SET state = :state
                          WHERE
                            files_id = :id AND linenumber = :line';
                            // AND state != 1 AND state != :state';
                $this->statement->all_lines_update = $this->db->prepare($query);
            }
        }

        foreach ($new as $line => $state) {
            if (!$line) {
                continue; // line 0 does not exist, skip this (xdebug quirk)
            }

            $result = isset($lines[$line]) ? $lines[$line] : null;
            if ($result === 1) {
                continue;
            }

            if ($result === $state) {
                continue;
            }

            if ($result === null) {
                $this->statement->all_lines->bindValue(':id',    $id,    SQLITE3_INTEGER);
                $this->statement->all_lines->bindValue(':line',  $line,  SQLITE3_INTEGER);
                $this->statement->all_lines->bindValue(':state', $state, SQLITE3_INTEGER);
                $this->statement->all_lines->execute();
            } else {
                $this->statement->all_lines_update->bindValue(':id',    $id,    SQLITE3_INTEGER);
                $this->statement->all_lines_update->bindValue(':line',  $line,  SQLITE3_INTEGER);
                $this->statement->all_lines_update->bindValue(':state', $state, SQLITE3_INTEGER);
                $this->statement->all_lines_update->execute();
            }
        }

        if (count($old)) {
            $query = 'DELETE FROM all_lines WHERE files_id = ' . $id .
                ' AND linenumber IN (' . implode(',', array_keys($old)) . ')';
            $this->db->exec($query);

            $query = 'DELETE FROM coverage WHERE files_id = ' . $id .
                ' AND linenumber IN (' . implode(',', array_keys($old)) . ')';
            $this->db->exec($query);
        }
    }

    function addFile($filepath, $issource = 0, $results = array())
    {
        $query = 'SELECT id FROM files WHERE filepath = "' . $this->db->escapeString($filepath) . '"';
        $id = $this->db->querySingle($query);
        if ($id === false) {
            throw new Exception('Unable to add file ' . $filepath . ' to database');
        }

        if ($id !== null) {
            $query = '
                UPDATE files SET
                    filepathmd5 = :md5,
                    issource = :issource
                    WHERE filepath = :filepath';
        } else {
            $query = 'INSERT INTO files
                     (filepath, filepathmd5, issource)
                      VALUES(:filepath, :md5, :issource)';
        }

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':filepath', $filepath);
        $stmt->bindValue(':md5',      md5_file($filepath));
        $stmt->bindValue(':issource', $issource);
        if (!$stmt->execute()) {
            throw new Exception('Problem running this particular SQL: ' . $query);
        }

        if ($id === null) {
            $id = $this->db->lastInsertRowID();
        }

        if ($issource) {
            $this->updateAllLines($id, $results);
        }

        return $id;
    }

    function addCoverage($testpath, $testid, $xdebug)
    {
        $query = 'DELETE FROM coverage WHERE tests_id = ' . $testid . ';
                  DELETE FROM coverage_nonsource WHERE tests_id = ' . $testid;
        $worked = $this->db->exec($query);

        if (!isset($this->statement->coverage)) {
            $query = 'INSERT INTO coverage
                      (files_id, linenumber, tests_id, state)
                      VALUES(:id, :line, :test, :state)';
            $this->statement->coverage = $this->db->prepare($query);
        }

        if (!isset($this->statement->coverageUpdate)) {
            $query = 'UPDATE coverage SET state = :state
                      WHERE
                        files_id = :id AND linenumber = :line AND
                        tests_id = :test AND state != 1 AND state != -2';
            $this->statement->coverageUpdate = $this->db->prepare($query);
        }

        foreach ($xdebug as $path => $results) {
            if (!file_exists($path)) {
                continue;
            }

            if (strpos($path, $this->codepath) !== 0) {
                $issource = 0;
            } else {
                if (strpos($path, $this->testpath) === 0) {
                    $issource = 0;
                } else {
                    $issource = 1;
                }
            }

            echo ".";
            $id = $this->addFile($path, $issource, $results);
            if (!$issource) {
                $query = 'REPLACE INTO coverage_nonsource
                          (files_id, tests_id)
                          VALUES(' . $id . ', ' . $testid . ')';
                $worked = $this->db->exec($query);
                if (!$worked) {
                    $error = $this->db->lastErrorMsg();
                    throw new Exception('Cannot add coverage for test ' . $testpath .
                                        ', covered file ' . $path . ': ' . $error);
                }
                continue;
            }

            foreach ($results as $line => $state) {
                if (!$line) {
                    continue; // line 0 does not exist, skip this (xdebug quirk)
                }

                $this->statement->coverage->bindValue(':id',    $id,     SQLITE3_INTEGER);
                $this->statement->coverage->bindValue(':line',  $line,   SQLITE3_INTEGER);
                $this->statement->coverage->bindValue(':test',  $testid, SQLITE3_INTEGER);
                $this->statement->coverage->bindValue(':state', $state,  SQLITE3_INTEGER);

                // Insert failed, lets try update
                if (!$this->statement->coverage->execute()) {
                    $this->statement->coverageUpdate->bindValue(':id',    $id,     SQLITE3_INTEGER);
                    $this->statement->coverageUpdate->bindValue(':line',  $line,   SQLITE3_INTEGER);
                    $this->statement->coverageUpdate->bindValue(':test',  $testid, SQLITE3_INTEGER);
                    $this->statement->coverageUpdate->bindValue(':state', $state,  SQLITE3_INTEGER);

                    // Update failed, error out
                    if (!$this->statement->coverageUpdate->execute()) {
                        $error = $this->db->lastErrorMsg();
                        throw new Exception('Cannot add coverage for test ' . $testpath .
                                            ', covered file ' . $path . ': ' . $error);
                    }
                }
            }
        }
    }

    function begin()
    {
        $this->db->exec('PRAGMA synchronous=OFF'); // make inserts super fast
        $this->db->exec('BEGIN');
    }

    function commit()
    {
        $this->db->exec('COMMIT');
        $this->db->exec('PRAGMA synchronous=NORMAL'); // make inserts super fast
        $this->db->exec('VACUUM');
    }

    /**
     * Retrieve a list of .phpt tests that either have been modified,
     * or the files they access have been modified
     * @return array
     */
    function getModifiedTests()
    {
        // first scan for new .phpt files
        $tests = array();
        foreach (new \RegexIterator(
                                    new \RecursiveIteratorIterator(
                                        new \RecursiveDirectoryIterator($this->testpath,
                                                                        0|\RecursiveDirectoryIterator::SKIP_DOTS)),
                                    '/\.phpt$/') as $file) {
            if (strpos((string) $file, '.svn')) {
                continue;
            }

            $tests[] = realpath((string) $file);
        }

        $newtests = array();
        foreach ($tests as $path) {
            if ($path == $this->db->querySingle('SELECT testpath FROM tests WHERE testpath = "' .
                                       $this->db->escapeString($path) . '"')) {
                continue;
            }

            $newtests[] = $path;
        }

        $modifiedTests = $modifiedPaths = array();
        $paths = $this->retrievePaths(1);
        echo "Scanning ", count($paths), " source files";
        foreach ($paths as $path) {
            echo '.';
            $query = '
                SELECT id, filepathmd5, issource FROM files WHERE filepath = "' .
                $this->db->escapeString($path) . '"';
            $result = $this->db->query($query);
            while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
                if (!file_exists($path) || md5_file($path) == $res['filepathmd5']) {
                    if ($res['issource'] && !file_exists($path)) {
                        $this->db->exec('
                            DELETE FROM files WHERE id = '. $res['id'] .';
                            DELETE FROM coverage WHERE files_id = '. $res['id'] . ';
                            DELETE FROM all_lines WHERE files_id = '. $res['id'] . ';
                            DELETE FROM line_info WHERE files_id = '. $res['id'] . ';');
                    }
                    break;
                }

                $modifiedPaths[] = $path;
                // file is modified, get a list of tests that execute this file
                if ($res['issource']) {
                    $query = '
                        SELECT t.testpath
                        FROM coverage c, tests t
                        WHERE
                            c.files_id = ' . $res['id'] . '
                          AND
                            t.id = c.tests_id';
                    $result2 = $this->db->query($query);
                    while ($res = $result2->fetchArray(SQLITE3_NUM)) {
                        $modifiedTests[$res[0]] = true;
                    }
                } else {
                    $query = '
                        SELECT t.testpath
                        FROM coverage_nonsource c, tests t
                        WHERE
                            c.files_id = ' . $res['id'] . '
                          AND
                            t.id = c.tests_id';
                    $result2 = $this->db->query($query);
                    while ($res = $result2->fetchArray(SQLITE3_NUM)) {
                        $modifiedTests[$res[0]] = true;
                    }
                }
                break;
            }
        }

        echo "done\n";
        echo count($modifiedPaths), ' modified files resulting in ',
            count($modifiedTests), " modified tests\n";
        $paths = $this->retrieveTestPaths();
        echo "Scanning ", count($paths), " test paths";
        foreach ($paths as $path) {
            echo '.';
            $query = '
                SELECT id, testpathmd5 FROM tests where testpath = "' .
                $this->db->escapeString($path) . '"';
            $result = $this->db->query($query);
            while ($res = $result->fetchArray(SQLITE3_ASSOC)) {
                if (!file_exists($path)) {
                    $this->removeOldTest($path, $res['id']);
                    continue;
                }

                if (md5_file($path) != $res['testpathmd5']) {
                    $modifiedTests[$path] = true;
                }
            }
        }

        echo "done\n";
        echo count($newtests), ' new tests and ', count($modifiedTests), " modified tests should be re-run\n";
        return array_merge($newtests, array_keys($modifiedTests));
    }
}