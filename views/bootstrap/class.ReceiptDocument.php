<?php
/**
 * Implementation of ReceiptDocument view
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
 * Class which outputs the html page for ReceiptDocument view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ReceiptDocument extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		header('Content-Type: application/javascript; charset=UTF-8');
?>
function checkIndForm()
{
	msg = new Array();
	if (document.formind.reviewStatus.value == "") msg.push("<?php printMLText("js_no_receipt_status");?>");
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
	if (document.formgrp.reviewGroup.value == "") msg.push("<?php printMLText("js_no_receipt_group");?>");
	if (document.formgrp.reviewStatus.value == "") msg.push("<?php printMLText("js_no_receipt_status");?>");
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
		$receiptreject = $this->params['receiptreject'];

		$receipts = $content->getReceiptStatus();
		foreach($receipts as $receipt) {
			if($receipt['receiptID'] == $_GET['receiptid']) {
				$receiptStatus = $receipt;
				break;
			}
		}

		$this->htmlStartPage(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))));
		$this->globalNavigation($folder);
		$this->contentStart();
		$this->pageNavigation($this->getFolderPathHTML($folder, true, $document), "view_document", $document);
		$this->contentHeading(getMLText("submit_receipt"));
		$this->contentContainerStart();

		// Display the Receipt form.
		$receipttype = ($receiptStatus['type'] == 0) ? 'ind' : 'grp';
		if($receiptStatus["status"]!=0) {

			print "<table class=\"folderView\"><thead><tr>";
			print "<th>".getMLText("status")."</th>";
			print "<th>".getMLText("comment")."</th>";
			print "<th>".getMLText("last_update")."</th>";
			print "</tr></thead><tbody><tr>";
			print "<td>";
			printReceiptStatusText($receiptStatus["status"]);
			print "</td>";
			print "<td>".htmlspecialchars($receiptStatus["comment"])."</td>";
			$indUser = $dms->getUser($receiptStatus["userID"]);
			print "<td>".$receiptStatus["date"]." - ". htmlspecialchars($indUser->getFullname()) ."</td>";
			print "</tr></tbody></table><br>\n";
		}
?>
	<form class="form-horizontal" method="post" action="../op/op.ReceiptDocument.php" id="form<?= $receipttype ?>" name="form<?= $receipttype ?>">
	<?php echo createHiddenFieldWithKey('receiptdocument'); ?>
		<div class="control-group">
			<label class="control-label"><?php printMLText("comment");?>:</label>
			<div class="controls">
				<textarea name="comment" cols="80" rows="4"></textarea>
			</div>
		</div>
<?php if($receiptreject) { ?>
		<div class="control-group">
			<label class="control-label"><?php printMLText("receipt_status");?>:</label>
			<div class="controls">
				<select name="receiptStatus">
<?php if($receiptStatus['status'] != 1) { ?>
					<option value='1'><?php printMLText("status_receipted")?></option>
<?php } ?>
<?php if($receiptStatus['status'] != -1) { ?>
					<option value='-1'><?php printMLText("rejected")?></option>
<?php } ?>
				</select>
			</div>
		</div>
<?php } else { ?>
		<input type="hidden" name="receiptStatus" value="1" />
<?php } ?>
		<div class="controls">
			<input type='submit' class="btn btn-primary" name='<?= $receipttype ?>Receipt' value='<?php printMLText("submit_receipt")?>'/>
		<div>
		<input type='hidden' name='receiptType' value='<?= $receipttype ?>'/>
		<?php if($receipttype == 'grp'): ?>
		<input type='hidden' name='receiptGroup' value='<?php echo $receiptStatus['required']; ?>'/>
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
