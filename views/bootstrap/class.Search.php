<?php
/**
 * Implementation of Search result view
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Include parent class
 */
require_once("class.Bootstrap.php");

/**
 * Include class to preview documents
 */
require_once("SeedDMS/Preview.php");

/**
 * Class which outputs the html page for Search result view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Search extends SeedDMS_Bootstrap_Style {

	/**
	 * Mark search query sting in a given string
	 *
	 * @param string $str mark this text
	 * @param string $tag wrap the marked text with this html tag
	 * @return string marked text
	 */
	function markQuery($str, $tag = "b") { /* {{{ */
		$querywords = preg_split("/ /", $this->query);
		
		foreach ($querywords as $queryword)
			$str = str_ireplace("($queryword)", "<" . $tag . ">\\1</" . $tag . ">", $str);
		
		return $str;
	} /* }}} */

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');

		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));

?>
$(document).ready( function() {
	$('#export').on('click', function(e) {
		e.preventDefault();
		window.location.href = $(this).attr('href')+'&includecontent='+($('#includecontent').prop('checked') ? '1' : '0');
	});
});
<?php
//		$this->printFolderChooserJs("form1");
		$this->printDeleteFolderButtonJs();
		$this->printDeleteDocumentButtonJs();
		/* Add js for catching click on document in one page mode */
		$this->printClickDocumentJs();
		$this->printClickFolderJs();
	} /* }}} */

	function export() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$entries = $this->params['searchhits'];

		include("../inc/inc.ClassDownloadMgr.php");
		$downmgr = new SeedDMS_Download_Mgr();
		if($extraheader = $this->callHook('extraDownloadHeader'))
			$downmgr->addHeader($extraheader);
		foreach($entries as $entry) {
			if($entry->isType('document')) {
				$extracols = $this->callHook('extraDownloadColumns', $entry);
				if(isset($_GET['includecontent']) && $_GET['includecontent'] && $rawcontent = $this->callHook('rawcontent', $entry->getLatestContent())) {
					$downmgr->addItem($entry->getLatestContent(), $extracols, $rawcontent);
				} else
					$downmgr->addItem($entry->getLatestContent(), $extracols);
			}
		}
		$filename = tempnam('/tmp', '');
		if(isset($_GET['includecontent']) && $_GET['includecontent']) {
			$downmgr->createArchive($filename);
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: " . filesize($filename));
			header("Content-Disposition: attachment; filename=\"export-" .date('Y-m-d') . ".zip\"");
			header("Content-Type: application/zip");
			header("Cache-Control: must-revalidate");
		} else {
			$downmgr->createToc($filename);
			header("Content-Transfer-Encoding: binary");
			header("Content-Length: " . filesize($filename));
			header("Content-Disposition: attachment; filename=\"export-" .date('Y-m-d') . ".xls\"");
			header("Content-Type: application/vnd.ms-excel");
			header("Cache-Control: must-revalidate");
		}

		readfile($filename);
		unlink($filename);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$fullsearch = $this->params['fullsearch'];
		$totaldocs = $this->params['totaldocs'];
		$totalfolders = $this->params['totalfolders'];
		$limit = $this->params['limit'];
		$attrdefs = $this->params['attrdefs'];
		$allCats = $this->params['allcategories'];
		$allUsers = $this->params['allusers'];
		$mode = $this->params['mode'];
		$resultmode = $this->params['resultmode'];
		$workflowmode = $this->params['workflowmode'];
		$enablefullsearch = $this->params['enablefullsearch'];
		$enableclipboard = $this->params['enableclipboard'];
		$attributes = $this->params['attributes'];
		$categories = $this->params['categories'];
		$owner = $this->params['owner'];
		$startfolder = $this->params['startfolder'];
		$startdate = $this->params['startdate'];
		$stopdate = $this->params['stopdate'];
		$expstartdate = $this->params['expstartdate'];
		$expstopdate = $this->params['expstopdate'];
		$creationdate = $this->params['creationdate'];
		$expirationdate = $this->params['expirationdate'];
		$status = $this->params['status'];
		$this->query = $this->params['query'];
		$orderby = $this->params['orderby'];
		$entries = $this->params['searchhits'];
		$totalpages = $this->params['totalpages'];
		$pageNumber = $this->params['pagenumber'];
		$searchTime = $this->params['searchtime'];
		$urlparams = $this->params['urlparams'];
		$searchin = $this->params['searchin'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];
		$reception = $this->params['reception'];
		$showsinglesearchhit = $this->params['showsinglesearchhit'];

		if($showsinglesearchhit && count($entries) == 1) {
			$entry = $entries[0];
			if($entry->isType('document')) {
				header('Location: ../out/out.ViewDocument.php?documentid='.$entry->getID());
				exit;
			} elseif($entry->isType('folder')) {
				header('Location: ../out/out.ViewFolder.php?folderid='.$entry->getID());
				exit;
			}
		}

//		if ($pageNumber != 'all')
//			$entries = array_slice($entries, ($pageNumber-1)*$limit, $limit);

		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("search_results"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("search_results"), "");

		echo "<div class=\"row-fluid\">\n";
		echo "<div class=\"span4\">\n";
//echo "<pre>";print_r($_GET);echo "</pre>";
?>
  <ul class="nav nav-tabs" id="searchtab">
	  <li <?php echo ($fullsearch == false) ? 'class="active"' : ''; ?>><a data-target="#database" data-toggle="tab"><?php printMLText('databasesearch'); ?></a></li>
<?php
		if($enablefullsearch) {
?>
	  <li <?php echo ($fullsearch == true) ? 'class="active"' : ''; ?>><a data-target="#fulltext" data-toggle="tab"><?php printMLText('fullsearch'); ?></a></li>
<?php
		}
?>
	</ul>
	<div class="tab-content">
	  <div class="tab-pane <?php echo ($fullsearch == false) ? 'active' : ''; ?>" id="database">
<form action="../out/out.Search.php" name="form1">
<?php
// Database search Form {{{
		$this->contentContainerStart();
?>
<table class="table-condensed">
<tr>
<td><?php printMLText("search_query");?>:</td>
<td>
<input type="text" name="query" value="<?php echo htmlspecialchars($this->query); ?>" />
<select name="mode">
<option value="1" <?php echo ($mode=='AND') ? "selected" : ""; ?>><?php printMLText("search_mode_and");?>
<option value="0"<?php echo ($mode=='OR') ? "selected" : ""; ?>><?php printMLText("search_mode_or");?>
</select>
</td>
</tr>
<tr>
<td><?php printMLText("search_in");?>:</td>
<td>
<label class="checkbox" for="keywords"><input type="checkbox" id="keywords" name="searchin[]" value="1" <?php if(in_array('1', $searchin)) echo " checked"; ?>><?php printMLText("keywords");?> (<?php printMLText('documents_only'); ?>)</label>
<label class="checkbox" for="searchName"><input type="checkbox" name="searchin[]" id="searchName" value="2" <?php if(in_array('2', $searchin)) echo " checked"; ?>><?php printMLText("name");?></label>
<label class="checkbox" for="comment"><input type="checkbox" name="searchin[]" id="comment" value="3" <?php if(in_array('3', $searchin)) echo " checked"; ?>><?php printMLText("comment");?></label>
<label class="checkbox" for="attributes"><input type="checkbox" name="searchin[]" id="attributes" value="4" <?php if(in_array('4', $searchin)) echo " checked"; ?>><?php printMLText("attributes");?></label>
<label class="checkbox" for="id"><input type="checkbox" name="searchin[]" id="id" value="5" <?php if(in_array('5', $searchin)) echo " checked"; ?>><?php printMLText("id");?></label>
</td>
</tr>
<tr>
<td><?php printMLText("owner");?>:</td>
<td>
<select class="chzn-select" name="ownerid" data-allow-clear="true" data-placeholder="<?php printMLText('select_users'); ?>" data-no_results_text="<?php printMLText('unknown_owner'); ?>">
<option value=""></option>
<?php
		foreach ($allUsers as $userObj) {
			if ($userObj->isGuest() || ($userObj->isHidden() && $userObj->getID() != $user->getID() && !$user->isAdmin()))
				continue;
			print "<option value=\"".$userObj->getID()."\" ".(($owner && $userObj->getID() == $owner->getID()) ? "selected" : "").">" . htmlspecialchars($userObj->getLogin()." - ".$userObj->getFullName()) . "</option>\n";
		}
?>
</select>
</td>
</tr>
<tr>
<td><?php printMLText("search_resultmode");?>:</td>
<td>
<select name="resultmode">
<option value="3" <?php echo ($resultmode=='3') ? "selected" : ""; ?>><?php printMLText("search_resultmode_both");?>
<option value="2"<?php echo ($resultmode=='2') ? "selected" : ""; ?>><?php printMLText("search_mode_folders");?>
<option value="1"<?php echo ($resultmode=='1') ? "selected" : ""; ?>><?php printMLText("search_mode_documents");?>
</select>
</td>
</tr>
<tr>
<td><?php printMLText("under_folder")?>:</td>
<td><?php $this->printFolderChooserHtml("form1", M_READ, -1, $startfolder);?></td>
</tr>
<tr>
<td><?php printMLText("creation_date");?>:</td>
<td>
        <label class="checkbox inline">
				  <input type="checkbox" name="creationdate" value="true" <?php if($creationdate) echo "checked"; ?>/><?php printMLText("between");?>
        </label><br />
        <span class="input-append date" style="display: inline;" id="createstartdate" data-date="<?php echo date('Y-m-d'); ?>" data-date-format="yyyy-mm-dd" data-date-language="<?php echo str_replace('_', '-', $this->params['session']->getLanguage()); ?>">
          <input class="span4" size="16" name="createstart" type="text" value="<?php if($startdate) printf("%04d-%02d-%02d", $startdate['year'], $startdate['month'], $startdate['day']); else echo date('Y-m-d'); ?>">
          <span class="add-on"><i class="fa fa-calendar"></i></span>
        </span>&nbsp;
				<?php printMLText("and"); ?>
        <span class="input-append date" style="display: inline;" id="createenddate" data-date="<?php echo date('Y-m-d'); ?>" data-date-format="yyyy-mm-dd" data-date-language="<?php echo str_replace('_', '-', $this->params['session']->getLanguage()); ?>">
          <input class="span4" size="16" name="createend" type="text" value="<?php if($stopdate) printf("%04d-%02d-%02d", $stopdate['year'], $stopdate['month'], $stopdate['day']); else echo date('Y-m-d'); ?>">
          <span class="add-on"><i class="fa fa-calendar"></i></span>
        </span>
</td>
</tr>

<?php
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				$attricon = '';
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_all) {
?>
<tr>
	<td><?php echo htmlspecialchars($attrdef->getName()); ?>:</td>
	<td><?php $this->printAttributeEditField($attrdef, isset($attributes[$attrdef->getID()]) ? $attributes[$attrdef->getID()] : null, 'attributes', true) ?></td>
</tr>

<?php
				}
			}
		}
?>

<tr>
<td></td><td><button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?php printMLText("search"); ?></button></td>
</tr>

</table>
<?php
		$this->contentContainerEnd();
		// }}}

		// Seach options for documents {{{
		/* First check if any of the folder filters are set. If it is,
		 * open the accordion.
		 */
		$openfilterdlg = false;
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				$attricon = '';
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_document || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_documentcontent) {
					if(!empty($attributes[$attrdef->getID()]))
						$openfilterdlg = true;
				}
			}
		}
		if($categories)
			$openfilterdlg = true;
		if($status)
			$openfilterdlg = true;
		if($expirationdate)
			$openfilterdlg = true;
		if($reception)
			$openfilterdlg = true;
?>
<?php if($totaldocs): ?>
<div class="accordion" id="accordion1">
  <div class="accordion-group">
    <div class="accordion-heading">
      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion1" href="#collapseExport">
        <?php printMLText('export'); ?>
      </a>
    </div>
    <div id="collapseExport" class="accordion-body">
      <div class="accordion-inner">
<table class="table-condensed">
<tr>
<td><?= getMLText('content') ?></td><td><label class="checkbox inline"><input id="includecontent" type="checkbox" name="includecontent" value="1"> <?php printMLText("include_content"); ?></label></td>
</tr>
<tr>
<td></td><td><a id="export" class="btn" href="<?= $_SERVER['REQUEST_URI']."&action=export" ?>"><i class="fa fa-download"></i> Export</a></td>
</tr>
</table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
<div class="accordion" id="accordion2">
  <div class="accordion-group">
    <div class="accordion-heading">
      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion2" href="#collapseOne">
        <?php printMLText('filter_for_documents'); ?>
      </a>
    </div>
    <div id="collapseOne" class="accordion-body <?php if(!$openfilterdlg) echo "collapse";?>">
      <div class="accordion-inner">
<table class="table-condensed">
<tr>
<td><?php printMLText("category");?>:</td>
<td>
<select class="chzn-select" name="categoryids[]" multiple="multiple" data-placeholder="<?php printMLText('select_category'); ?>" data-no_results_text="<?php printMLText('unknown_document_category'); ?>">
<!--
<option value="-1"><?php printMLText("all_categories");?>
-->
<?php
		$tmpcatids = array();
		foreach($categories as $tmpcat)
			$tmpcatids[] = $tmpcat->getID();
		foreach ($allCats as $catObj) {
			print "<option value=\"".$catObj->getID()."\" ".(in_array($catObj->getID(), $tmpcatids) ? "selected" : "").">" . htmlspecialchars($catObj->getName()) . "\n";
		}
?>
</select>
</td>
</tr>
<tr>
<td><?php printMLText("status");?>:</td>
<td>
<?php if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') { ?>
<label class="checkbox" for='draft'><input type="checkbox" id="draft" name="draft" value="1" <?php echo in_array(S_DRAFT, $status) ? "checked" : ""; ?>><?php printOverallStatusText(S_DRAFT);?></label>
<?php if($workflowmode == 'traditional') { ?>
<label class="checkbox" for='pendingReview'><input type="checkbox" id="pendingReview" name="pendingReview" value="1" <?php echo in_array(S_DRAFT_REV, $status) ? "checked" : ""; ?>><?php printOverallStatusText(S_DRAFT_REV);?></label>
<?php } ?>
<label class="checkbox" for='pendingApproval'><input type="checkbox" id="pendingApproval" name="pendingApproval" value="1" <?php echo in_array(S_DRAFT_APP, $status) ? "checked" : ""; ?>><?php printOverallStatusText(S_DRAFT_APP);?></label>
<?php } elseif($workflowmode == 'advanced') { ?>
<label class="checkbox" for='inWorkflow'><input type="checkbox" id="inWorkflow" name="inWorkflow" value="1" <?php echo in_array(S_IN_WORKFLOW, $status) ? "checked" : ""; ?>><?php printOverallStatusText(S_IN_WORKFLOW);?></label>
<?php } ?>
<label class="checkbox" for='released'><input type="checkbox" id="released" name="released" value="1" <?php echo in_array(S_RELEASED, $status) ? "checked" : ""; ?>><?php printOverallStatusText(S_RELEASED);?></label>
<label class="checkbox" for='rejected'><input type="checkbox" id="rejected" name="rejected" value="1" <?php echo in_array(S_REJECTED, $status) ? "checked" : ""; ?>><?php printOverallStatusText(S_REJECTED);?></label>
<label class="checkbox" for='inrevision'><input type="checkbox" id="inrevision" name="inrevision" value="1" <?php echo in_array(S_IN_REVISION, $status) ? "checked" : ""; ?>><?php printOverallStatusText(S_IN_REVISION);?></label>
<label class="checkbox" for='obsolete'><input type="checkbox" id="obsolete" name="obsolete" value="1" <?php echo in_array(S_OBSOLETE, $status) ? "checked" : ""; ?>><?php printOverallStatusText(S_OBSOLETE);?></label>
<label class="checkbox" for='expired'><input type="checkbox" id="expired" name="expired" value="1" <?php echo in_array(S_EXPIRED, $status) ? "checked" : ""; ?>><?php printOverallStatusText(S_EXPIRED);?></label>
<label class="checkbox" for='needs_correction'><input type="checkbox" id="needs_correction" name="needs_correction" value="1" <?php echo in_array(S_NEEDS_CORRECTION, $status) ? "checked" : ""; ?>><?php printOverallStatusText(S_NEEDS_CORRECTION);?></label>
</td>
</tr>
<tr>
<td><?php printMLText("reception");?>:</td>
<td>
<label class="checkbox" for='reception'><input type="checkbox" id="reception" name="reception[]" value="missingaction" <?php echo in_array('missingaction', $reception) ? "checked" : ""; ?>><?php printMLText('reception_noaction'); ?></label>
<label class="checkbox" for='reception'><input type="checkbox" id="reception" name="reception[]" value="hasrejection" <?php echo in_array('hasrejection', $reception) ? "checked" : ""; ?>><?php printMLText('reception_rejected'); ?></label>
<label class="checkbox" for='reception'><input type="checkbox" id="reception" name="reception[]" value="hasacknowledge" <?php echo in_array('hasacknowledge', $reception) ? "checked" : ""; ?>><?php printMLText('reception_acknowleged'); ?></label>
</td>
</tr>
<tr>
<td><?php printMLText("expires");?>:</td>
<td>
        <label class="checkbox inline">
				  <input type="checkbox" name="expirationdate" value="true" <?php if($expirationdate) echo "checked"; ?>/><?php printMLText("between");?>
        </label><br />
        <span class="input-append date" style="display: inline;" id="expirationstartdate" data-date="<?php echo date('Y-m-d'); ?>" data-date-format="yyyy-mm-dd" data-date-language="<?php echo str_replace('_', '-', $this->params['session']->getLanguage()); ?>">
          <input class="span4" size="16" name="expirationstart" type="text" value="<?php if($expstartdate) printf("%04d-%02d-%02d", $expstartdate['year'], $expstartdate['month'], $expstartdate['day']); else echo date('Y-m-d'); ?>">
          <span class="add-on"><i class="fa fa-calendar"></i></span>
        </span>&nbsp;
				<?php printMLText("and"); ?>
        <span class="input-append date" style="display: inline;" id="expirationenddate" data-date="<?php echo date('Y-m-d'); ?>" data-date-format="yyyy-mm-dd" data-date-language="<?php echo str_replace('_', '-', $this->params['session']->getLanguage()); ?>">
          <input class="span4" size="16" name="expirationend" type="text" value="<?php if($expstopdate) printf("%04d-%02d-%02d", $expstopdate['year'], $expstopdate['month'], $expstopdate['day']); else echo date('Y-m-d'); ?>">
          <span class="add-on"><i class="fa fa-calendar"></i></span>
        </span>
</td>
</tr>
<?php
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				$attricon = '';
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_document || $attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_documentcontent) {
?>
<tr>
	<td><?php echo htmlspecialchars($attrdef->getName()); ?>:</td>
	<td><?php $this->printAttributeEditField($attrdef, isset($attributes[$attrdef->getID()]) ? $attributes[$attrdef->getID()] : null, 'attributes', true) ?></td>
</tr>

<?php
				}
			}
		}
?>
</table>
      </div>
    </div>
  </div>
</div>
<?php
		// }}}

		// Seach options for folders {{{
		/* First check if any of the folder filters are set. If it is,
		 * open the accordion.
		 */
		$openfilterdlg = false;
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				$attricon = '';
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_folder) {
					if(!empty($attributes[$attrdef->getID()]))
						$openfilterdlg = true;
				}
			}
		}
?>
<div class="accordion" id="accordion3">
  <div class="accordion-group">
    <div class="accordion-heading">
      <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion3" href="#collapseFolder">
        <?php printMLText('filter_for_folders'); ?>
      </a>
    </div>
    <div id="collapseFolder" class="accordion-body <?php if(!$openfilterdlg) echo "collapse";?>">
      <div class="accordion-inner">
<table class="table-condensed">
<?php
		if($attrdefs) {
			foreach($attrdefs as $attrdef) {
				$attricon = '';
				if($attrdef->getObjType() == SeedDMS_Core_AttributeDefinition::objtype_folder) {
?>
<tr>
	<td><?php echo htmlspecialchars($attrdef->getName()); ?>:</td>
	<td><?php $this->printAttributeEditField($attrdef, isset($attributes[$attrdef->getID()]) ? $attributes[$attrdef->getID()] : null, 'attributes', true) ?></td>
</tr>
<?php
				}
			}
		}
?>
</table>
      </div>
    </div>
  </div>
</div>
<?php
		// }}}
?>
</form>
		</div>
<?php
		// }}}
		// }}}

		// Fulltext search Form {{{
		if($enablefullsearch) {
	  	echo "<div class=\"tab-pane ".(($fullsearch == true) ? 'active' : '')."\" id=\"fulltext\">\n";
	$this->contentContainerStart();
?>
<form action="../out/out.Search.php" name="form2" style="min-height: 330px;">
<input type="hidden" name="fullsearch" value="1" />
<table class="table-condensed">
<tr>
<td><?php printMLText("search_query");?>:</td>
<td>
<input type="text" name="query" value="<?php echo htmlspecialchars($this->query); ?>" />
<!--
<select name="mode">
<option value="1" selected><?php printMLText("search_mode_and");?>
<option value="0"><?php printMLText("search_mode_or");?>
</select>
-->
</td>
</tr>
<tr>
<td><?php printMLText("owner");?>:</td>
<td>
<select class="chzn-select" name="ownerid" data-allow-clear="true" data-placeholder="<?php printMLText('select_users'); ?>" data-no_results_text="<?php printMLText('unknown_owner'); ?>">
<option value=""></option>
<?php
			foreach ($allUsers as $userObj) {
				if ($userObj->isGuest() || ($userObj->isHidden() && $userObj->getID() != $user->getID() && !$user->isAdmin()))
					continue;
				print "<option value=\"".$userObj->getID()."\" ".(($owner && $userObj->getID() == $owner->getID()) ? "selected" : "").">" . htmlspecialchars($userObj->getLogin()." - ".$userObj->getFullName()) . "</option>\n";
			}
?>
</select>
</td>
</tr>
<tr>
<td><?php printMLText("category_filter");?>:</td>
<td>
<select class="chzn-select" name="categoryids[]" multiple="multiple" data-placeholder="<?php printMLText('select_category'); ?>" data-no_results_text="<?php printMLText('unknown_document_category'); ?>">
<!--
<option value="-1"><?php printMLText("all_categories");?>
-->
<?php
		$tmpcatids = array();
		foreach($categories as $tmpcat)
			$tmpcatids[] = $tmpcat->getID();
		foreach ($allCats as $catObj) {
			print "<option value=\"".$catObj->getID()."\" ".(in_array($catObj->getID(), $tmpcatids) ? "selected" : "").">" . htmlspecialchars($catObj->getName()) . "\n";
		}
?>
</select>
</td>
</tr>
<tr>
<td></td><td><button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?php printMLText("search"); ?></button></td>
</tr>
</table>

</form>
<?php
			$this->contentContainerEnd();
			echo "</div>\n";
		}
		// }}}
?>
	</div>
<?php
		echo "</div>\n";
		echo "<div class=\"span8\">\n";
// Search Result {{{
		$foldercount = $doccount = 0;
		if($entries) {
			/*
			foreach ($entries as $entry) {
				if($entry->isType('document')) {
					$doccount++;
				} elseif($entry->isType('document')) {
					$foldercount++;
				}
			}
			 */
			print "<div class=\"alert\">".getMLText("search_report", array("doccount" => $totaldocs, "foldercount" => $totalfolders, 'searchtime'=>$searchTime))."</div>";
			$this->pageList($pageNumber, $totalpages, "../out/out.Search.php", $urlparams);
//			$this->contentContainerStart();

			$txt = $this->callHook('searchListHeader');
			if(is_string($txt))
				echo $txt;
			else {
				parse_str($_SERVER['QUERY_STRING'], $tmp);
				$tmp['orderby'] = $orderby=="n"||$orderby=="na)"?"nd":"n";
				print "<table class=\"table table-hover\">";
				print "<thead>\n<tr>\n";
				print "<th></th>\n";
				print "<th>".getMLText("name");
				if(!$fullsearch) {
					print " <a href=\"../out/out.Search.php?".http_build_query($tmp)."\" title=\"".getMLText("sort_by_name")."\">".($orderby=="n"||$orderby=="na"?' <i class="icon-sort-by-alphabet selected"></i>':($orderby=="nd"?' <i class="icon-sort-by-alphabet-alt selected"></i>':' <i class="icon-sort-by-alphabet"></i>'))."</a>";
					$tmp['orderby'] = $orderby=="d"||$orderby=="da)"?"dd":"d";
					print " <a href=\"../out/out.Search.php?".http_build_query($tmp)."\" title=\"".getMLText("sort_by_date")."\">".($orderby=="d"||$orderby=="da"?' <i class="icon-sort-by-attributes selected"></i>':($orderby=="dd"?' <i class="icon-sort-by-attributes-alt selected"></i>':' <i class="icon-sort-by-attributes"></i>'))."</a>";
				}
				print "</th>\n";
				//print "<th>".getMLText("attributes")."</th>\n";
				print "<th>".getMLText("status")."</th>\n";
				print "<th>".getMLText("action")."</th>\n";
				print "</tr>\n</thead>\n<tbody>\n";
			}

			$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
			$previewer->setConverters($previewconverters);
			foreach ($entries as $entry) {
				if($entry->isType('document')) {
					$txt = $this->callHook('documentListItem', $entry, $previewer, false, 'search');
					if(is_string($txt))
						echo $txt;
					else {
						$document = $entry;
						$owner = $document->getOwner();
						if($lc = $document->getLatestContent())
							$previewer->createPreview($lc);

						if (in_array(3, $searchin))
							$comment = $this->markQuery(htmlspecialchars($document->getComment()));
						else
							$comment = htmlspecialchars($document->getComment());
						if (strlen($comment) > 150) $comment = substr($comment, 0, 147) . "...";

						$belowtitle = "<br /><span style=\"font-size: 85%;\">".getMLText('in_folder').": /";
						$folder = $document->getFolder();
						$path = $folder->getPath();
						for ($i = 1; $i  < count($path); $i++) {
							$belowtitle .= htmlspecialchars($path[$i]->getName())."/";
						}
						$belowtitle .= "</span>";
						$lcattributes = $lc ? $lc->getAttributes() : null;
						$attrstr = '';
						if($lcattributes) {
							$attrstr .= "<table class=\"table table-condensed\">\n";
							$attrstr .= "<tr><th>".getMLText('name')."</th><th>".getMLText('attribute_value')."</th></tr>";
							foreach($lcattributes as $lcattribute) {
								$arr = $this->callHook('showDocumentContentAttribute', $lc, $lcattribute);
								if(is_array($arr)) {
									$attrstr .= "<tr>";
									$attrstr .= "<td>".$arr[0].":</td>";
									$attrstr .= "<td>".$arr[1]."</td>";
									$attrstr .= "</tr>";
								} elseif(is_string($arr)) {
									$attrstr .= $arr;
								} else {
									$attrdef = $lcattribute->getAttributeDefinition();
									$attrstr .= "<tr><td>".htmlspecialchars($attrdef->getName())."</td><td>".htmlspecialchars(implode(', ', $lcattribute->getValueAsArray()))."</td></tr>\n";
									// TODO: better use printAttribute()
									// $this->printAttribute($lcattribute);
								}
							}
							$attrstr .= "</table>\n";
						}
						$docattributes = $document->getAttributes();
						if($docattributes) {
							$attrstr .= "<table class=\"table table-condensed\">\n";
							$attrstr .= "<tr><th>".getMLText('name')."</th><th>".getMLText('attribute_value')."</th></tr>";
							foreach($docattributes as $docattribute) {
								$arr = $this->callHook('showDocumentAttribute', $document, $docattribute);
								if(is_array($arr)) {
									$attrstr .= "<tr>";
									$attrstr .= "<td>".$arr[0].":</td>";
									$attrstr .= "<td>".$arr[1]."</td>";
									$attrstr .= "</tr>";
								} elseif(is_string($arr)) {
									$attrstr .= $arr;
								} else {
									$attrdef = $docattribute->getAttributeDefinition();
									$attrstr .= "<tr><td>".htmlspecialchars($attrdef->getName())."</td><td>".htmlspecialchars(implode(', ', $docattribute->getValueAsArray()))."</td></tr>\n";
								}
							}
							$attrstr .= "</table>\n";
						}
						$extracontent = array();
						$extracontent['below_title'] = $belowtitle;
						if($attrstr)
							$extracontent['bottom_title'] = '<br />'.$this->printPopupBox('<span class="btn btn-mini btn-default">'.getMLText('attributes').'</span>', $attrstr, true);
						print $this->documentListRow($document, $previewer, false, 0, $extracontent);
					}
				} elseif($entry->isType('folder')) {
					$folder = $entry;
					$owner = $folder->getOwner();
					if (in_array(2, $searchin)) {
						$folderName = $this->markQuery(htmlspecialchars($folder->getName()), "i");
					} else {
						$folderName = htmlspecialchars($folder->getName());
					}

					$attrstr = '';
					$folderattributes = $folder->getAttributes();
					if($folderattributes) {
						$attrstr .= "<table class=\"table table-condensed\">\n";
						$attrstr .= "<tr><th>".getMLText('name')."</th><th>".getMLText('attribute_value')."</th></tr>";
						foreach($folderattributes as $folderattribute) {
							$attrdef = $folderattribute->getAttributeDefinition();
							$attrstr .= "<tr><td>".htmlspecialchars($attrdef->getName())."</td><td>".htmlspecialchars(implode(', ', $folderattribute->getValueAsArray()))."</td></tr>\n";
						}
						$attrstr .= "</table>";
					}
					$extracontent = array();
					if($attrstr)
						$extracontent['bottom_title'] = '<br />'.$this->printPopupBox('<span class="btn btn-mini btn-default">'.getMLText('attributes').'</span>', $attrstr, true);
					print $this->folderListRow($folder, false, $extracontent);
				}
			}
			print "</tbody></table>\n";
//			$this->contentContainerEnd();
			$this->pageList($pageNumber, $totalpages, "../out/out.Search.php", $_GET);
		} else {
			$numResults = $totaldocs + $totalfolders;
			if ($numResults == 0) {
				print "<div class=\"alert alert-error\">".getMLText("search_no_results")."</div>";
			}
		}
// }}}
		echo "</div>";
		echo "</div>";
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
