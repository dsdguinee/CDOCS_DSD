<?php
/**
 * Implementation of TransmittalMgr view
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
 * Class which outputs the html page for TransmittalMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_TransmittalMgr extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		$showtree = $this->params['showtree'];
		$onepage = $this->params['onepage'];

		header('Content-Type: application/javascript; charset=UTF-8');
		parent::jsTranslations(array('cancel', 'splash_move_document', 'confirm_move_document', 'move_document', 'confirm_transfer_link_document', 'transfer_content', 'link_document', 'splash_move_folder', 'confirm_move_folder', 'move_folder'));
		$this->printDeleteDocumentButtonJs();
		$this->printDeleteItemButtonJs();
		$this->printUpdateItemButtonJs();
		if($onepage)
			$this->printClickDocumentJs();
?>
$(document).ready( function() {
	$('body').on('click', '.selecttransmittal', function(ev){
		ev.preventDefault();
		$('div.ajax').trigger('update', {transmittalid: $(ev.currentTarget).data('transmittalid')});
		window.history.pushState({"html":"","pageTitle":""},"", '../out/out.TransmittalMgr.php?transmittalid=' + $(ev.currentTarget).data('transmittalid'));
	});
});
<?php
	} /* }}} */

	/**
	 * Print button for updating the transmittal item to the newest version
	 *
	 * @param object $item
	 * @param string $msg message shown in case of successful update
	 */
	protected function printUpdateItemButton($item, $msg, $return=false){ /* {{{ */
		$itemid = $item->getID();
		$content = '';
    $content .= '<a class="update-transmittalitem-btn" transmittal="'.$item->getTransmittal()->getID().'" rel="'.$itemid.'" msg="'.htmlspecialchars($msg, ENT_QUOTES).'" confirmmsg="'.htmlspecialchars(getMLText("confirm_update_transmittalitem"), ENT_QUOTES).'"><i class="fa fa-refresh"></i></a>';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	protected function printUpdateItemButtonJs(){ /* {{{ */
		echo "
		$(document).ready(function () {
			$('body').on('click', 'a.update-transmittalitem-btn', function(ev){
				id = $(ev.currentTarget).attr('rel');
				transmittalid = $(ev.currentTarget).attr('transmittal');
				confirmmsg = $(ev.currentTarget).attr('confirmmsg');
				msg = $(ev.currentTarget).attr('msg');
				formtoken = '".createFormKey('updatetransmittalitem')."';
				bootbox.dialog(confirmmsg, [{
					\"label\" : \"<i class='fa fa-refresh'></i> ".getMLText("update_transmittalitem")."\",
					\"class\" : \"btn-danger\",
					\"callback\": function() {
						$.ajax('../op/op.TransmittalMgr.php', {
							type:'POST',
							async:true,
							dataType:'json',
							data: {
								action: 'updatetransmittalitem',
								id: id,
								formtoken: formtoken
							},
							success: function(data) {
								if(data.success) {
									noty({
										text: msg,
										type: 'success',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 1500,
									});
									$('div.ajax').trigger('update', {transmittalid: transmittalid});
								} else {
									noty({
										text: data.message,
										type: 'error',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 3500,
									});
								}
							}
						});
					}
				}, {
					\"label\" : \"".getMLText("cancel")."\",
					\"class\" : \"btn-cancel\",
					\"callback\": function() {
					}
				}]);
			});
		});
		";
	} /* }}} */

	/**
	 * Print button with link for deleting a transmittal item
	 *
	 * This button works just like the printDeleteDocumentButton()
	 *
	 * @param object $item transmittal item to be deleted
	 * @param string $msg message shown in case of successful deletion
	 * @param boolean $return return html instead of printing it
	 * @return string html content if $return is true, otherwise an empty string
	 */
	protected function printDeleteItemButton($item, $msg, $return=false){ /* {{{ */
		$itemid = $item->getID();
		$content = '';
    $content .= '<a class="delete-transmittalitem-btn" rel="'.$itemid.'" msg="'.htmlspecialchars($msg, ENT_QUOTES).'" confirmmsg="'.htmlspecialchars(getMLText("confirm_rm_transmittalitem"), ENT_QUOTES).'"><i class="fa fa-remove"></i></a>';
		if($return)
			return $content;
		else
			echo $content;
		return '';
	} /* }}} */

	protected function printDeleteItemButtonJs(){ /* {{{ */
		echo "
		$(document).ready(function () {
			$('body').on('click', 'a.delete-transmittalitem-btn', function(ev){
				id = $(ev.currentTarget).attr('rel');
				confirmmsg = $(ev.currentTarget).attr('confirmmsg');
				msg = $(ev.currentTarget).attr('msg');
				formtoken = '".createFormKey('removetransmittalitem')."';
				bootbox.dialog(confirmmsg, [{
					\"label\" : \"<i class='fa fa-remove'></i> ".getMLText("rm_transmittalitem")."\",
					\"class\" : \"btn-danger\",
					\"callback\": function() {
						$.ajax('../op/op.TransmittalMgr.php', {
							type:'POST',
							async:true,
							dataType:'json',
							data: {
								action: 'removetransmittalitem',
								id: id,
								formtoken: formtoken
							},
							success: function(data) {
								if(data.success) {
									$('#table-row-transmittalitem-'+id).hide('slow');
									noty({
										text: msg,
										type: 'success',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 1500,
									});
								} else {
									noty({
										text: data.message,
										type: 'error',
										dismissQueue: true,
										layout: 'topRight',
										theme: 'defaultTheme',
										timeout: 3500,
									});
								}
							},
						});
					}
				}, {
					\"label\" : \"".getMLText("cancel")."\",
					\"class\" : \"btn-cancel\",
					\"callback\": function() {
					}
				}]);
			});
		});
		";
	} /* }}} */

	protected function showTransmittalForm($transmittal) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessop = $this->params['accessobject'];
?>
	<form action="../op/op.TransmittalMgr.php" method="post" enctype="multipart/form-data" name="form<?php print $transmittal ? $transmittal->getID() : '0';?>">
<?php
		if($transmittal) {
			echo createHiddenFieldWithKey('edittransmittal');
?>
	<input type="hidden" name="transmittalid" value="<?php print $transmittal->getID();?>">
	<input type="hidden" name="action" value="edittransmittal">
<?php
		} else {
			echo createHiddenFieldWithKey('addtransmittal');
?>
	<input type="hidden" name="action" value="addtransmittal">
<?php
		}
?>
	<table class="table-condensed">
<?php
	if($transmittal && $accessop->check_controller_access('TransmittalMgr', array('action'=>'removetransmittal'))) {
?>
		<tr>
			<td></td>
			<td><a class="standardText btn" href="../out/out.RemoveTransmittal.php?transmittalid=<?php print $transmittal->getID();?>"><i class="fa fa-remove"></i> <?php printMLText("rm_transmittal");?></a></td>
		</tr>
<?php
	}
?>
		<tr>
			<td><?php printMLText("transmittal_name");?>:</td>
			<td><input type="text" name="name" value="<?php print $transmittal ? htmlspecialchars($transmittal->getName()) : "";?>"></td>
		</tr>
		<tr>
			<td><?php printMLText("transmittal_comment");?>:</td>
			<td><input type="text" name="comment" value="<?php print $transmittal ? htmlspecialchars($transmittal->getComment()) : "";?>"></td>
		</tr>
<?php
		if($transmittal && $accessop->check_controller_access('TransmittalMgr', array('action'=>'edittransmittal')) || !$transmittal && $accessop->check_controller_access('TransmittalMgr', array('action'=>'addtransmittal'))) {
?>
		<tr>
			<td></td>
			<td><button type="submit" class="btn"><i class="fa fa-save"></i> <?php printMLText($transmittal ? "save" : "add_transmittal")?></button></td>
		</tr>
<?php
		}
?>
	</table>
	</form>
<?php
	} /* }}} */

	function form() { /* {{{ */
		$seltransmittal = $this->params['seltransmittal'];

		$this->showTransmittalForm($seltransmittal);
	} /* }}} */

	protected function showTransmittalItems($seltransmittal) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessop = $this->params['accessobject'];
		$cachedir = $this->params['cachedir'];
		$timeout = $this->params['timeout'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
		$previewer->setConverters($previewconverters);

		if($seltransmittal) {
			$items = $seltransmittal->getItems();
			if($items) {
				print "<table class=\"table table-condensed\">";
				print "<thead>\n<tr>\n";
				print "<th></th>\n";
				print "<th>".getMLText("name")."</th>\n";
				print "<th>".getMLText("status")."</th>\n";
				print "<th>".getMLText("document")."</th>\n";
				print "<th>".getMLText("action")."</th>\n";
				print "</tr>\n</thead>\n<tbody>\n";
				foreach($items as $item) {
					if($content = $item->getContent()) {
						$document = $content->getDocument();
						$latestcontent = $document->getLatestContent();
						if ($document->getAccessMode($user) >= M_READ) {
//							echo "<tr id=\"table-row-transmittalitem-".$item->getID()."\">";
							echo $this->documentListRowStart($document);
							echo $this->documentListRow($document, $previewer, true, $content->getVersion());
							echo "<td><div class=\"list-action\">";
							$this->printDeleteItemButton($item, getMLText('transmittalitem_removed'));
							if($latestcontent->getVersion() != $content->getVersion())
								$this->printUpdateItemButton($item, getMLText('transmittalitem_updated', array('prevversion'=>$content->getVersion(), 'newversion'=>$latestcontent->getVersion())));
							echo "</div></td>";
							echo $this->documentListRowEnd($document);
						}
					} else {
						echo "<tr id=\"table-row-transmittalitem-".$item->getID()."\">";
						echo "<td colspan=\"5\">content ist weg</td>";
						echo "</tr>";
					}
				}
				print "</tbody>\n</table>\n";
				print "<a class=\"btn btn-primary\" href=\"../op/op.TransmittalDownload.php?transmittalid=".$seltransmittal->getID()."\">".getMLText('download')."</a>";
			}
		}
	} /* }}} */

	function items() { /* {{{ */
		$seltransmittal = $this->params['seltransmittal'];

		$this->showTransmittalItems($seltransmittal);
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessop = $this->params['accessobject'];
		$seltransmittal = $this->params['seltransmittal'];

		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("my_transmittals"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("my_transmittals"), "my_documents");
		$this->contentHeading(getMLText("my_transmittals"));
?>
<div class="row-fluid">
<div class="span4">
<?php
		$this->contentContainerStart();

		$transmittals = $dms->getAllTransmittals($user);

		if ($transmittals){
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("comment")."</th>\n";
			print "<th>".getMLText("transmittal_size")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($transmittals as $transmittal) {
				print "<tr>\n";
				print "<td>".$transmittal->getName()."</td>";
				print "<td>".$transmittal->getComment()."</td>";
				$items = $transmittal->getItems();
				print "<td>".count($items)." <em>(".SeedDMS_Core_File::format_filesize($transmittal->getSize()).")</em></td>";
				print "<td>";
				print "<div class=\"list-action\">";
				print "<a class=\"selecttransmittal\" data-transmittalid=\"".$transmittal->getID()."\" href=\"../out/out.TransmittalMgr.php?transmittalid=".$transmittal->getID()."\" title=\"".getMLText("edit_transmittal_props")."\"><i class=\"fa fa-edit\"></i></a>";
				print "</div>";
				print "</td>";
				print "</tr>\n";
			}
			print "</tbody>\n</table>\n";
		}

		$this->contentContainerEnd();
?>
</div>
<div class="span8">
<?php
		if($accessop->check_view_access($this, array('action'=>'form'))) {
		$this->contentContainerStart();
?>
		<div class="ajax" data-view="TransmittalMgr" data-action="form" <?php echo ($seltransmittal ? "data-query=\"transmittalid=".$seltransmittal->getID()."\"" : "") ?>></div>
<?php
		$this->contentContainerEnd();
		}
		if($accessop->check_view_access($this, array('action'=>'items'))) {
?>
		<div class="ajax" data-view="TransmittalMgr" data-action="items" <?php echo ($seltransmittal ? "data-query=\"transmittalid=".$seltransmittal->getID()."\"" : "") ?>></div>
<?php
		}
?>
</div>
</div>
<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
?>
