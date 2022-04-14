<?php
if(isset($_SERVER['SEEDDMS_HOME'])) {
	ini_set('include_path', $_SERVER['SEEDDMS_HOME'].'/utils'. PATH_SEPARATOR .ini_get('include_path'));
	$myincpath = $_SERVER['SEEDDMS_HOME'];
} else {
	ini_set('include_path', dirname($argv[0]). PATH_SEPARATOR .ini_get('include_path'));
	$myincpath = dirname($argv[0]);
}

function usage() { /* {{{ */
	echo "Usage:".PHP_EOL;
	echo "  seeddms-indexer [-h] [-v] [-c] [--config <file>]".PHP_EOL;
	echo PHP_EOL;
	echo "Description:".PHP_EOL;
	echo "  This program recreates the full text index of SeedDMS.".PHP_EOL;
	echo PHP_EOL;
	echo "Options:".PHP_EOL;
	echo "  -h, --help: print usage information and exit.".PHP_EOL;
	echo "  -v, --version: print version and exit.".PHP_EOL;
	echo "  -c: recreate index.".PHP_EOL;
	echo "  --config: set alternative config file.".PHP_EOL;
} /* }}} */

$version = "0.0.2";
$shortoptions = "hvc";
$longoptions = array('help', 'version', 'config:');
if(false === ($options = getopt($shortoptions, $longoptions))) {
	usage();
	exit(0);
}

/* Print help and exit */
if(isset($options['h']) || isset($options['help'])) {
	usage();
	exit(0);
}

/* Print version and exit */
if(isset($options['v']) || isset($options['verѕion'])) {
	echo $version.PHP_EOL;
	exit(0);
}

/* Set alternative config file */
if(isset($options['config'])) {
	define('SEEDDMS_CONFIG_FILE', $options['config']);
} elseif(isset($_SERVER['SEEDDMS_CONFIG_FILE'])) {
	define('SEEDDMS_CONFIG_FILE', $_SERVER['SEEDDMS_CONFIG_FILE']);
}

/* recreate index */
$recreate = false;
if(isset($options['c'])) {
	$recreate = true;
}

include($myincpath."/inc/inc.Settings.php");
include($myincpath."/inc/inc.Init.php");
include($myincpath."/inc/inc.Extension.php");
include($myincpath."/inc/inc.DBInit.php");

if($settings->_fullSearchEngine == 'sqlitefts') {
	$indexconf = array(
		'Indexer' => 'SeedDMS_SQLiteFTS_Indexer',
		'Search' => 'SeedDMS_SQLiteFTS_Search',
		'IndexedDocument' => 'SeedDMS_SQLiteFTS_IndexedDocument'
	);

	require_once('SeedDMS/SQLiteFTS.php');
} else {
	$indexconf = array(
		'Indexer' => 'SeedDMS_Lucene_Indexer',
		'Search' => 'SeedDMS_Lucene_Search',
		'IndexedDocument' => 'SeedDMS_Lucene_IndexedDocument'
	);

	require_once('SeedDMS/Lucene.php');
}

function tree($dms, $index, $indexconf, $folder, $indent='') { /* {{{ */
	global $settings, $themes;
	echo $themes->black($indent."D ".$folder->getName()).PHP_EOL;
	$subfolders = $folder->getSubFolders();
	foreach($subfolders as $subfolder) {
		tree($dms, $index, $indexconf, $subfolder, $indent.'  ');
	}
	$documents = $folder->getDocuments();
	foreach($documents as $document) {
		echo $themes->black($indent."  ".$document->getId().":".$document->getName()." ");
		$lucenesearch = new $indexconf['Search']($index);
		if(!($hit = $lucenesearch->getDocument($document->getId()))) {
			try {
				$idoc = new $indexconf['IndexedDocument']($dms, $document, isset($settings->_converters['fulltext']) ? $settings->_converters['fulltext'] : null, false, $settings->_cmdTimeout);
				if(isset($GLOBALS['SEEDDMS_HOOKS']['indexDocument'])) {
					foreach($GLOBALS['SEEDDMS_HOOKS']['indexDocument'] as $hookObj) {
						if (method_exists($hookObj, 'preIndexDocument')) {
							$hookObj->preIndexDocument(null, $document, $idoc);
						}
					}
				}
				$index->addDocument($idoc);
				echo $themes->green(" (Document added)").PHP_EOL;
			} catch(Exception $e) {
				echo $themes->error(" (Timeout)").PHP_EOL;
			}
		} else {
			try {
				$created = (int) $hit->getDocument()->getFieldValue('created');
			} catch (Exception $e) {
				$created = 0;
			}
			$content = $document->getLatestContent();
			if($created >= $content->getDate()) {
				echo $themes->italic(" (Document unchanged)").PHP_EOL;
			} else {
				$index->delete($hit->id);
				try {
					$idoc = new $indexconf['IndexedDocument']($dms, $document, isset($settings->_converters['fulltext']) ? $settings->_converters['fulltext'] : null, false, $settings->_cmdTimeout);
					if(isset($GLOBALS['SEEDDMS_HOOKS']['indexDocument'])) {
						foreach($GLOBALS['SEEDDMS_HOOKS']['indexDocument'] as $hookObj) {
							if (method_exists($hookObj, 'preIndexDocument')) {
								$hookObj->preIndexDocument(null, $document, $idoc);
							}
						}
					}
					$index->addDocument($idoc);
					echo $themes->green(" (Document updated)").PHP_EOL;
				} catch(Exception $e) {
					echo $themes->error(" (Timeout)").PHP_EOL;
				}
			}
		}
	}
} /* }}} */

$themes = new \AlecRabbit\ConsoleColour\Themes();

if($recreate)
	$index = $indexconf['Indexer']::create($settings->_luceneDir);
else
	$index = $indexconf['Indexer']::open($settings->_luceneDir);
if(!$index) {
	echo $themes->error("Could not create index.").PHP_EOL;
	exit(1);
}

$indexconf['Indexer']::init($settings->_stopWordsFile);

$folder = $dms->getFolder($settings->_rootFolderID);
tree($dms, $index, $indexconf, $folder);

$index->commit();
$index->optimize();
