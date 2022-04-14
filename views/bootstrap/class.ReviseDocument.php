<?php
/**
 * Implementation of ReviseDocument view
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
 * Class which outputs the html page for ReviseDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ReviseDocument extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
?>
function checkIndForm()
{
	msg = new Array();
	if (document.formind.revisionStatus.value == "") msg.push("<?php printMLText("js_no_revision_status");?>");
	if (document.formind.comment.value == "") msg.push("<?php printMLText("js_no_comment");?>");
	if (msg != "") {
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	}
	else
		return true;
}
function checkGrpForm()
{
	msg = new Array();
	if (document.formgrp.revisionGroup.value == "") msg.push("<?php printMLText("js_no_revision_group");?>");
	if (document.formgrp.revisionSatus.value == "") msg.push("<?php printMLText("js_no_revision_status");?>");
	if (document.formgrp.comment.value == "") msg.push("<?php printMLText("js_no_comment");?>");
	if (msg != "")
	{
  	noty({
  		text: msg.join('<br />'),
  		type: 'error',
      dismissQueue: true,
  		layout: 'topRight',
  		theme: 'defaultTheme',
			_timeout: 1500,
  	});
		return false;
	}
	else
		return true;
}
$(document).ready(function() {
	$('body').on('submit', '#formind', function(ev){
		if(checkIndForm()) return;
		event.preventDefault();
	});
	$('body').on('submit', '#formgrp', function(ev){
		if(checkGrpForm()) return;
		event.preventDefault();
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$document = $this->params['document'];
		$content = $this->params['version'];
		$revisionid = $this->params['revisionid'];

		$reviews = $content->getRevisionStatus();
		foreach($reviews as $review) {
			if($review['revisionID'] == $revisionid) {
				$revisionStatus = $review;
				break;
			}
		}

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("submit_revision"));
		$this->contentContainerStart();

		// Display the Revision form.
		$revisiontype = ($revisionStatus['type'] == 0) ? 'ind' : 'grp';
		if($revisionStatus["status"]!=0) {

			print "<table class=\"folderView\"><thead><tr>";
			print "<th>".getMLText("status")."</th>";
			print "<th>".getMLText("comment")."</th>";
			print "<th>".getMLText("last_update")."</th>";
			print "</tr></thead><tbody><tr>";
			print "<td>";
			printRevisionStatusText($revisionStatus["status"]);
			print "</td>";
			print "<td>".htmlspecialchars($revisionStatus["comment"])."</td>";
			$indUser = $dms->getUser($revisionStatus["userID"]);
			print "<td>".$revisionStatus["date"]." - ". htmlspecialchars($indUser->getFullname()) ."</td>";
			print "</tr></tbody></table><br>\n";
		}
?>
	<form method="post" action="../op/op.ReviseDocument.php" id="form<?= $revisiontype ?>" name="form<?= $revisiontype ?>">
	<?php echo createHiddenFieldWithKey('revisedocument'); ?>
	<table class="table-condensed">
		<tr>
			<td><?php printMLText("comment")?>:</td>
			<td><textarea name="comment" cols="80" rows="4"></textarea></td>
		</tr>
		<tr>
			<td><?php printMLText("revision_status")?>:</td>
			<td>
				<select name="revisionStatus">
<?php if($revisionStatus['status'] != 1) { ?>
					<option value='1'><?php printMLText("status_revised")?></option>
<?php } ?>
<?php if($revisionStatus['status'] != -1) { ?>
					<option value='-1'><?php printMLText("status_needs_correction")?></option>
<?php } ?>
				</select>
			</td>
		</tr>
		<tr>
			<td></td>
			<td><input type='submit' class="btn" name='<?= $revisiontype ?>Revision' value='<?php printMLText("submit_revision")?>'/></td>
		</tr>
	</table>
	<input type='hidden' name='revisionType' value='<?= $revisiontype ?>'/>
	<?php if($revisiontype == 'grp'): ?>
	<input type='hidden' name='revisionGroup' value='<?php echo $revisionStatus['required']; ?>'/>
	<?php endif; ?>
	<input type='hidden' name='documentid' value='<?php echo $document->getID() ?>'/>
	<input type='hidden' name='version' value='<?php echo $content->getVersion() ?>'/>
	</form>
<?php
		$this->contentContainerEnd();
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
