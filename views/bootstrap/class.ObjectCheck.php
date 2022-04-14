<?php
/**
 * Implementation of ObjectCheck view
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
 * Class which outputs the html page for ObjectCheck view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_ObjectCheck extends SeedDMS_Bootstrap_Style {

	protected function printListHeader($resArr, $previewer, $order=false) { /* {{{ */
		print "<table class=\"table table-condensed\">";
		print "<thead>\n<tr>\n";
		print "<th></th>\n";
		if($order) {
			$orderby = ''; //$this->params['orderby'];
			$orderdir = ''; //$this->params['orderdir'];

			print "<th><a data-action=\"".$order."\" data-orderby=\"n\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("name")."</a> ".($orderby == 'n' || $orderby == '' ? ($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')." &middot; <a data-action=\"".$order."\" data-orderby=\"u\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("last_update")."</a> ".($orderby == 'u' ? ($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')." &middot; <a data-action=\"".$order."\" data-orderby=\"e\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("expires")."</a> ".($orderby == 'e' ? ($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')."</th>\n";
		} else
			print "<th>".getMLText("name")."</th>\n";
		if($order)
			print "<th><a data-action=\"".$order."\" data-orderby=\"s\" data-orderdir=\"".($orderdir == 'desc' ? '' : 'desc')."\">".getMLText("status")."</a>".($orderby == 's' ? " ".($orderdir == 'desc' ? '<i class="fa fa-arrow-up"></i>' :  '<i class="fa fa-arrow-down"></i>') : '')."</th>\n";
		else
			print "<th>".getMLText("status")."</th>\n";
		print "<th>".getMLText("action")."</th>\n";
		print "</tr>\n</thead>\n<tbody>\n";
	} /* }}} */

	protected function printListFooter() { /* {{{ */
		echo "</tbody>\n</table>";
	} /* }}} */

	protected function printList($resArr, $previewer, $order=false) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];

		$this->printListHeader($resArr, $previewer, $order);
		$noaccess = 0;
		foreach ($resArr as $document) {
			$document->verifyLastestContentExpriry();

			if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
				$txt = $this->callHook('documentListItem', $document, $previewer, false);
				if(is_string($txt))
					echo $txt;
				else
					echo $this->documentListRow($document, $previewer, false);
			} else {
				$noaccess++;
			}
		}
		$this->printListFooter();

		if($noaccess) {
			$this->warningMsg(getMLText('list_contains_no_access_docs', array('count'=>$noaccess)));
		}
	} /* }}} */

	function listRepair() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$repair = $this->params['repair'];
		$objects = $this->params['repairobjects'];

		$this->contentHeading(getMLText("objectcheck"));

		if($objects) {
			if($repair) {
				echo "<div class=\"alert\">".getMLText('repairing_objects')."</div>";
			}
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th></th>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("owner")."</th>\n";
			print "<th>".getMLText("error")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			$this->needsrepair = false;
			print "</tbody></table>\n";

			if($this->needsrepair && $repair == 0) {
				echo '<p><a href="out.ObjectCheck.php?repair=1">'.getMLText('do_object_repair').'</a></p>';
			}
		}
	} /* }}} */

	function listUnlinkedFolders() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$unlinkedfolders = $this->params['unlinkedfolders'];

		$this->contentHeading(getMLText("unlinked_folders"));
		if($unlinkedfolders) {
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("id")."</th>\n";
			print "<th>".getMLText("parent")."</th>\n";
			print "<th></th>\n";
			print "<th>".getMLText("error")."</th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($unlinkedfolders as $error) {
				echo "<tr>";
				echo "<td>".$error['name']."</td>";
				echo "<td>".$error['id']."</td>";
				echo "<td>".$error['parent']."</td>";
				echo "<td>".$error['msg']."</td>";
				echo "<td><a class=\"btn movefolder\" source=\"".$error['id']."\" dest=\"".$rootfolder->getID()."\" formtoken=\"".createFormKey('movefolder')."\">Move</a> </td>";
				echo "</tr>";
			}
			print "</tbody></table>\n";
		}
	} /* }}} */

	function listUnlinkedDocuments() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$unlinkeddocuments = $this->params['unlinkeddocuments'];

		$this->contentHeading(getMLText("unlinked_documents"));
		if($unlinkeddocuments) {
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("name")."</th>\n";
			print "<th>".getMLText("id")."</th>\n";
			print "<th>".getMLText("parent")."</th>\n";
			print "<th>".getMLText("error")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($unlinkeddocuments as $error) {
				echo "<tr>";
				echo "<td>".$error['name']."</td>";
				echo "<td>".$error['id']."</td>";
				echo "<td>".$error['parent']."</td>";
				echo "<td>".$error['msg']."</td>";
				echo "<td><a class=\"btn movedocument\" source=\"".$error['id']."\" dest=\"".$rootfolder->getID()."\" formtoken=\"".createFormKey('movedocument')."\">Move</a> </td>";
				echo "</tr>";
			}
			print "</tbody></table>\n";
		}
	} /* }}} */

	function listUnlinkedContent() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$unlinkedcontent = $this->params['unlinkedcontent'];
		$unlink = $this->params['unlink'];

		$this->contentHeading(getMLText("unlinked_content"));
		if($unlink) {
			echo "<p>".getMLText('unlinking_objects')."</p>";
		}

		if($unlinkedcontent) {
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($unlinkedcontent as $version) {
				$doc = $version->getDocument();
				print "<tr><td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td>";
				if($unlink) {
					$doc->removeContent($version);
				}
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			if($unlink == 0) {
				echo '<p><a href="out.ObjectCheck.php?unlink=1">'.getMLText('do_object_unlink').'</a></p>';
			}
		}

	} /* }}} */

	function listMissingFileSize() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$nofilesizeversions = $this->params['nofilesizeversions'];
		$repair = $this->params['repair'];

		$this->contentHeading(getMLText("missing_filesize"));
		if($nofilesizeversions) {
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($nofilesizeversions as $version) {
				$doc = $version->getDocument();
				$class = $msg = '';
				if($repair) {
					if($version->setFileSize()) {
						$msg = getMLText('repaired');
						$class = ' class="success"';
					} else {
						$msg = getMLText('not_repaired');
						$class = ' class="error"';
					}
				}
				print "<tr".$class."><td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td>";
				echo "<td>";
				echo $msg;
				echo "</td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			if($repair == 0) {
				echo '<div class="repair"><a class="btn btn-primary" data-action="listMissingFileSize">'.getMLText('do_object_setfilesize').'</a></div>';
			}
		}

	} /* }}} */

	function listMissingChecksum() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$nochecksumversions = $this->params['nochecksumversions'];
		$repair = $this->params['repair'];

		$this->contentHeading(getMLText("missing_checksum"));

		if($nochecksumversions) {
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($nochecksumversions as $version) {
				$doc = $version->getDocument();
				$class = $msg = '';
				if($repair) {
					if($version->setChecksum()) {
						$msg = getMLText('repaired');
						$class = ' class="success"';
					} else {
						$msg = getMLText('not_repaired');
						$class = ' class="error"';
					}
				}
				print "<tr".$class."><td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td>";
				echo "<td>";
				echo $msg;
				echo "</td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			if($repair == 0) {
				echo '<div class="repair"><a class="btn btn-primary" data-action="listMissingChecksum">'.getMLText('do_object_setchecksum').'</a></div>';
			}
		}
	} /* }}} */

	function listWrongFiletype() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$wrongfiletypeversions = $this->params['wrongfiletypeversions'];
		$repair = $this->params['repair'];

		$this->contentHeading(getMLText("wrong_filetype"));

		if($wrongfiletypeversions) {
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th>".getMLText("filetype")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($wrongfiletypeversions as $version) {
				$doc = $version->getDocument();
				$class = $msg = '';
				if($repair) {
					if($version->setFiletype()) {
						$msg = getMLText('repaired');
						$class = ' class="success"';
					} else {
						$msg = getMLText('not_repaired');
						$class = ' class="error"';
					}
				}
				print "<tr".$class."><td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td><td>".$version->getFileType()."</td>";
				echo "<td>";
				echo $msg;
				echo "</td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			if($repair == 0) {
				echo '<div class="repair"><a class="btn btn-primary" data-action="listWrongFiletype">'.getMLText('do_object_setfiletype').'</a></div>';
			}
		}
	} /* }}} */

	function listDuplicateContent() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$duplicateversions = $this->params['duplicateversions'];

		$this->contentHeading(getMLText("duplicate_content"));

		if($duplicateversions) {
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("original_filename")."</th>\n";
			print "<th>".getMLText("mimetype")."</th>\n";
			print "<th>".getMLText("duplicates")."</th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($duplicateversions as $rec) {
				$version = $rec['content'];
				$doc = $version->getDocument();
				print "<tr>";
				print "<td>".$doc->getId()."</td><td>".$version->getVersion()."</td><td>".$version->getOriginalFileName()."</td><td>".$version->getMimeType()."</td>";
				print "<td>";
				foreach($rec['duplicates'] as $duplicate) {
					$dupdoc = $duplicate->getDocument();
					print "<a href=\"../out/out.ViewDocument.php?documentid=".$dupdoc->getID()."\">".$dupdoc->getID()."/".$duplicate->getVersion()."</a>";
					echo "<br />";
				}
				print "</td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
		}
} /* }}} */

	function listDocsInRevisionNoAccess() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$docsinrevision = $this->params['docsinrevision'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
		$previewer->setConverters($previewconverters);

		$this->contentHeading(getMLText("docs_in_revision_no_access"));

		if($docsinrevision) {
			$this->printList($docsinrevision, $previewer);
		}
	} /* }}} */

	function listDocsInReceptionNoAccess() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$docsinreception = $this->params['docsinreception'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
		$previewer->setConverters($previewconverters);

		$this->contentHeading(getMLText("docs_in_revision_no_access"));

		if($docsinreception) {
			$this->printList($docsinreception, $previewer, 'listDocsInReceptionNoAccess');
		}
	} /* }}} */

	function listProcessesWithoutUserGroup($process, $ug) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$processwithoutusergroup = $this->params['processwithoutusergroup'];
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$previewconverters = $this->params['previewConverters'];
		$timeout = $this->params['timeout'];
		$repair = $this->params['repair'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout);
		$previewer->setConverters($previewconverters);

		$this->contentHeading(getMLText($process."s_without_".$ug));

		if($processwithoutusergroup[$process][$ug]) {
			print "<table class=\"table table-condensed\">";
			print "<thead>\n<tr>\n";
			print "<th>".getMLText("process")."</th>\n";
			print "<th>".getMLText("user_group")."</th>\n";
			print "<th>".getMLText("document")."</th>\n";
			print "<th>".getMLText("version")."</th>\n";
			print "<th>".getMLText("userid_groupid")."</th>\n";
			print "<th></th>\n";
			print "</tr>\n</thead>\n<tbody>\n";
			foreach($processwithoutusergroup[$process][$ug] as $rec) {
				print "<tr>";
				print "<td>".$process."</td>";
				print "<td>".$ug."</td>";
				print "<td><a href=\"../out/out.ViewDocument.php?documentid=".$rec['documentID']."\">".$rec['name']."</a></td><td>".$rec['version']."</td>";
				print "<td>".$rec['required']."</td>";
				print "<td><a class=\"repair\" data-action=\"list".ucfirst($process)."Without".ucfirst($ug)."\" data-required=\"".$rec['required']."\">".getMLText('delete')."</a></td>";
				print "</tr>\n";
			}
			print "</tbody></table>\n";
			return count($processwithoutusergroup[$process][$ug]);
		}
		return false;
	} /* }}} */

	function listReviewWithoutUser() { /* {{{ */
		$this->listProcessesWithoutUserGroup('review', 'user');
	} /* }}} */

	function listReviewWithoutGroup() { /* {{{ */
		$this->listProcessesWithoutUserGroup('review', 'group');
	} /* }}} */

	function listApprovalWithoutUser() { /* {{{ */
		$this->listProcessesWithoutUserGroup('approval', 'user');
	} /* }}} */

	function listApprovalWithoutGroup() { /* {{{ */
		$this->listProcessesWithoutUserGroup('approval', 'group');
	} /* }}} */

	function listReceiptWithoutUser() { /* {{{ */
		if($this->listProcessesWithoutUserGroup('receipt', 'user')) {
			echo '<div class="repair"><a data-action="listReceiptWithoutUser">'.getMLText('do_object_repair').'</a>';
		}
	} /* }}} */

	function listReceiptWithoutGroup() { /* {{{ */
		$this->listProcessesWithoutUserGroup('receipt', 'group');
	} /* }}} */

	function listRevisionWithoutUser() { /* {{{ */
		$this->listProcessesWithoutUserGroup('revision', 'user');
	} /* }}} */

	function listRevisionWithoutGroup() { /* {{{ */
		$this->listProcessesWithoutUserGroup('revision', 'group');
	} /* }}} */

	function js() { /* {{{ */
		$user = $this->params['user'];
		$folder = $this->params['folder'];

		header('Content-Type: application/javascript; charset=UTF-8');

		$this->printDeleteFolderButtonJs();
		$this->printDeleteDocumentButtonJs();
		$this->printClickDocumentJs();
?>
$(document).ready( function() {
	$('body').on('click', 'ul.bs-docs-sidenav li a', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {orderby: $(this).data('orderby')});
		window.history.pushState({"html":"","pageTitle":""},"", '../out/out.ObjectCheck.php?list=' + $(this).data('action'));
	});
	$('body').on('click', 'div.repair a', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {repair: 1});
	});
	$('body').on('click', 'a.repair', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {repair: 1, required: $(this).data('required')});
	});
	$('body').on('click', 'table th a', function(ev){
		ev.preventDefault();
		$('#kkkk.ajax').data('action', $(this).data('action'));
		$('#kkkk.ajax').trigger('update', {orderby: $(this).data('orderby'), orderdir: $(this).data('orderdir')});
	});
});
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$folder = $this->params['folder'];
		$listtype = $this->params['listtype'];
		$unlinkedcontent = $this->params['unlinkedcontent'];
		$unlinkedfolders = $this->params['unlinkedfolders'];
		$unlinkeddocuments = $this->params['unlinkeddocuments'];
		$nofilesizeversions = $this->params['nofilesizeversions'];
		$nochecksumversions = $this->params['nochecksumversions'];
		$duplicateversions = $this->params['duplicateversions'];
		$docsinrevision = $this->params['docsinrevision'];
		$docsinreception = $this->params['docsinreception'];
		$processwithoutusergroup = $this->params['processwithoutusergroup'];
		$wrongfiletypeversions = $this->params['wrongfiletypeversions'];
		$repair = $this->params['repair'];
		$unlink = $this->params['unlink'];
		$setfilesize = $this->params['setfilesize'];
		$setchecksum = $this->params['setchecksum'];
		$rootfolder = $this->params['rootfolder'];
		$repairobjects = $this->params['repairobjects'];
		$this->enableClipboard = $this->params['enableclipboard'];

		$this->htmlAddHeader('<script type="text/javascript" src="../styles/'.$this->theme.'/bootbox/bootbox.min.js"></script>'."\n", 'js');

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		echo '<div class="row-fluid">';
		echo '<div class="span3">';
		$this->contentHeading(getMLText("object_check_critical"));
		echo '<ul class="nav nav-list bs-docs-sidenav _affix">';
		echo '<li class=""><a data-href="#all_documents" data-action="listRepair"><span class="badge '.($repairobjects ? 'badge-info ' : '').'badge-right">'.count($repairobjects).'</span>'.getMLText("objectcheck").'</a></li>';
		echo '<li class=""><a data-href="#unlinked_folders" data-action="listUnlinkedFolders"><span class="badge '.($unlinkedfolders ? 'badge-info ' : '').'badge-right">'.count($unlinkedfolders).'</span>'.getMLText("unlinked_folders").'</a></li>';
		echo '<li class=""><a data-href="#unlinked_documents" data-action="listUnlinkedDocuments"><span class="badge '.($unlinkeddocuments ? 'badge-info ' : '').'badge-right">'.count($unlinkeddocuments).'</span>'.getMLText("unlinked_documents").'</a></li>';
		echo '<li class=""><a data-href="#unlinked_content" data-action="listUnlinkedContent"><span class="badge '.($unlinkedcontent ? 'badge-info ' : '').'badge-right">'.count($unlinkedcontent).'</span>'.getMLText("unlinked_content").'</a></li>';
		echo '<li class=""><a data-href="#missing_filesize" data-action="listMissingFileSize"><span class="badge '.($nofilesizeversions ? 'badge-info ' : '').'badge-right">'.count($nofilesizeversions).'</span>'.getMLText("missing_filesize").'</a></li>';
		echo '<li class=""><a data-href="#missing_checksum" data-action="listMissingChecksum"><span class="badge '.($nochecksumversions ? 'badge-info ' : '').'badge-right">'.count($nochecksumversions).'</span>'.getMLText("missing_checksum").'</a></li>';
		echo '<li class=""><a data-href="#wrong_filetype" data-action="listWrongFiletype"><span class="badge '.($wrongfiletypeversions ? 'badge-info ' : '').'badge-right">'.count($wrongfiletypeversions).'</span>'.getMLText("wrong_filetype").'</a></li>';
		echo '</ul>';
		$this->contentHeading(getMLText("object_check_warning"));
		echo '<ul class="nav nav-list bs-docs-sidenav _affix">';
		echo '<li class=""><a data-href="#duplicate_content" data-action="listDuplicateContent"><span class="badge '.($duplicateversions ? 'badge-info ' : '').'badge-right">'.count($duplicateversions).'</span>'.getMLText("duplicate_content").'</a></li>';
		echo '<li class=""><a data-href="#inrevision_no_access" data-action="listDocsInRevisionNoAccess"><span class="badge '.($docsinrevision ? 'badge-info ' : '').'badge-right">'.count($docsinrevision).'</span>'.getMLText("docs_in_revision_no_access").'</a></li>';
		echo '<li class=""><a data-href="#inreception_no_access" data-action="listDocsInReceptionNoAccess"><span class="badge '.($docsinreception ? 'badge-info ' : '').'badge-right">'.count($docsinreception).'</span>'.getMLText("docs_in_reception_no_access").'</a></li>';
		echo '</ul>';
		echo '<ul class="nav nav-list bs-docs-sidenav _affix">';
		foreach(array('review', 'approval', 'receipt', 'revision') as $process) {
			foreach(array('user', 'group') as $ug) {
				echo '<li class=""><a data-href="#'.$process.'_without_'.$ug.'" data-action="list'.ucfirst($process).'Without'.ucfirst($ug).'"><span class="badge '.($processwithoutusergroup[$process][$ug] ? 'badge-info ' : '').'badge-right">'.count($processwithoutusergroup[$process][$ug]).'</span>'.getMLText($process."s_without_".$ug).'</a></li>';
			}
		}
		echo '</ul>';
		echo '</div>';
		echo '<div class="span9">';

		echo '<div id="kkkk" class="ajax" data-view="ObjectCheck" data-action="'.($listtype ? $listtype : 'listRepair').'"></div>';

		echo '</div>';
		echo '</div>';

		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
