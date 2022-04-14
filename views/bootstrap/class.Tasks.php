<?php
/**
 * Implementation of Tasks view
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
 * Class which outputs the html page for clipboard view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_Tasks extends SeedDMS_Bootstrap_Style {

	private function __myTasks() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$enablereceiptworkflow = $this->params['enablereceiptworkflow'];
		$enablerevisionworkflow = $this->params['enablerevisionworkflow'];
		$workflowmode = $this->params['workflowmode'];
		$tasksinmenu = $this->params['tasksinmenu'];
		$tasks = array();
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval') {
			$tasks['approval'] = array();
			if($workflowmode == 'traditional')
				$tasks['review'] = array();
		} elseif($workflowmode == 'advanced')
			$tasks['workflow'] = array();

		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval')
			if(!$tasksinmenu || in_array('approval', $tasksinmenu)) {
				$tasks['approval'] = array();
				$resArr = $dms->getDocumentList('ApproveByMe', $user);
				if($resArr) {
					$docs = array();
					foreach ($resArr as $res) {
						$document = $dms->getDocument($res["id"]);
						if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
							$docs[] = $document;
						}
					}
					if($this->hasHook('filterApprovalTaskList'))
						$docs = $this->callHook('filterApprovalTaskList', $docs);
					foreach($docs as $doc)
						$tasks['approval'][] = array('id'=>$doc->getId(), 'name'=>$doc->getName());
				}
			}
		if($workflowmode == 'traditional')
			if(!$tasksinmenu || in_array('review', $tasksinmenu)) {
				$tasks['review'] = array();
				$resArr = $dms->getDocumentList('ReviewByMe', $user);
				if($resArr) {
					$docs = array();
					foreach ($resArr as $res) {
						$document = $dms->getDocument($res["id"]);
						if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
							$docs[] = $document;
//							$tasks['review'][] = array('id'=>$res['id'], 'name'=>$res['name']);
						}
					}
					if($this->hasHook('filterReviewTaskList'))
						$docs = $this->callHook('filterReviewTaskList', $docs);
					foreach($docs as $doc)
						$tasks['review'][] = array('id'=>$doc->getId(), 'name'=>$doc->getName());
				}
			}
		if($enablereceiptworkflow)
			if(!$tasksinmenu || in_array('receipt', $tasksinmenu)) {
				$tasks['receipt'] = array();
				$resArr = $dms->getDocumentList('ReceiptByMe', $user);
				if($resArr) {
					$docs = array();
					foreach ($resArr as $res) {
						$document = $dms->getDocument($res["id"]);
						if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
							$docs[] = $document;
						}
					}
					if($this->hasHook('filterReceiptTaskList'))
						$docs = $this->callHook('filterReceiptTaskList', $docs);
					foreach($docs as $doc)
						$tasks['receipt'][] = array('id'=>$doc->getId(), 'name'=>$doc->getName());
				}
			}
		if($enablerevisionworkflow)
			if(!$tasksinmenu || in_array('revision', $tasksinmenu)) {
				$tasks['revision'] = array();
				$resArr = $dms->getDocumentList('ReviseByMe', $user);
				if($resArr) {
					$docs = array();
					foreach ($resArr as $res) {
						$document = $dms->getDocument($res["id"]);
						if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
							$docs[] = $document;
						}
					}
					if($this->hasHook('filterRevisionTaskList'))
						$docs = $this->callHook('filterRevisionTaskList', $docs);
					foreach($docs as $doc)
						$tasks['revision'][] = array('id'=>$doc->getId(), 'name'=>$doc->getName());
				}
			}
		if(!$tasksinmenu || in_array('needscorrection', $tasksinmenu)) {
			$tasks['needscorrection'] = array();
			$resArr = $dms->getDocumentList('NeedsCorrectionOwner', $user);
			if($resArr) {
				$docs = array();
				foreach ($resArr as $res) {
					$document = $dms->getDocument($res["id"]);
					if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
						$docs[] = $document;
					}
				}
				if($this->hasHook('filterNeedsCorrectionTaskList'))
					$docs = $this->callHook('filterNeedsCorrectionTaskList', $docs);
				foreach($docs as $doc)
					$tasks['needscorrection'][] = array('id'=>$doc->getId(), 'name'=>$doc->getName());
			}
		}
		if($workflowmode == 'advanced')
			if(!$tasksinmenu || in_array('workflow', $tasksinmenu)) {
				$tasks['workflow'] = array();
				$resArr = $dms->getDocumentList('WorkflowByMe', $user);
				if($resArr) {
					$docs = array();
					foreach ($resArr as $res) {
						$document = $dms->getDocument($res["id"]);
						if($document->getAccessMode($user) >= M_READ && $document->getLatestContent()) {
							$docs[] = $document;
						}
					}
					if($this->hasHook('filterWorkflowTaskList'))
						$docs = $this->callHook('filterWorkflowTaskList', $docs);
					foreach($docs as $doc)
						$tasks['workflow'][] = array('id'=>$doc->getId(), 'name'=>$doc->getName());
				}
			}
		return $tasks;
	} /* }}} */

	/**
	 * Returns the html needed for the task list in the menu
	 *
	 * This function renders the tasks in a way suitable to be
	 * used as a menu
	 *
	 * @param array $clipboard clipboard containing two arrays for both
	 *        documents and folders.
	 * @return string html code
	 */
	function countTasks() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$enablereceiptworkflow = $this->params['enablereceiptworkflow'];
		$enablerevisionworkflow = $this->params['enablerevisionworkflow'];
		$workflowmode = $this->params['workflowmode'];
		$tasksinmenu = $this->params['tasksinmenu'];

		$startts = microtime(true);
		$tasks = array();
		if($workflowmode == 'traditional' || $workflowmode == 'traditional_only_approval')
			if(!$tasksinmenu || in_array('approval', $tasksinmenu)) {
				$tasks['approval'] = $dms->countTasks('ApproveByMe', $user);
			}
		if($workflowmode == 'traditional')
			if(!$tasksinmenu || in_array('review', $tasksinmenu)) {
				$tasks['review'] = $dms->countTasks('ReviewByMe', $user);
			}
		if($workflowmode == 'advanced')
			if(!$tasksinmenu || in_array('workflow', $tasksinmenu)) {
				$tasks['workflow'] = $dms->countTasks('WorkflowByMe', $user);
			}
		if($enablereceiptworkflow)
			if(!$tasksinmenu || in_array('receipt', $tasksinmenu))
				$tasks['receipt'] = $dms->countTasks('ReceiptByMe', $user);
		if($enablerevisionworkflow)
			if(!$tasksinmenu || in_array('revision', $tasksinmenu))
				$tasks['revision'] = $dms->countTasks('ReviseByMe', $user);
		if(!$tasksinmenu || in_array('needscorrection', $tasksinmenu))
			$tasks['needscorrection'] = $dms->countTasks('NeedsCorrectionOwner', $user);

		header('Content-Type: application/json');
		echo json_encode(array('error'=>0, 'data'=>$tasks, 'processing_time'=>microtime(true)-$startts));
	} /* }}} */

	/**
	 * Returns a json array of tasks to be done
	 *
	 * @return string json string
	 */
	function myTasks() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$startts = microtime(true);

		$tasks = $this->__myTasks();

		header('Content-Type: application/json');
		echo json_encode(array('error'=>0, 'data'=>$tasks, 'processing_time'=>microtime(true)-$startts));
	} /* }}} */

	/**
	 * Returns the html needed for the task list in the menu
	 *
	 * This function renders the tasks in a way suitable to be
	 * used as a menu
	 *
	 * @return string html code
	 */
	function menuTasks() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessobject = $this->params['accessobject'];

		if(!$tasks = $this->__myTasks()) {
			echo '';
			return;
		}

		$content = '';
		$content .= "   <ul id=\"main-menu-tasks\" class=\"nav pull-right\">\n";
		$content .= "    <li class=\"dropdown\">\n";
		$content .= "     <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText('tasks')." (";
		$ct = array();
		if(isset($tasks['review']))
			$ct[] = count($tasks['review']);
		if(isset($tasks['approval']))
			$ct[] = count($tasks['approval']);
		if(isset($tasks['workflow']))
			$ct[] = count($tasks['workflow']);
		if(isset($tasks['receipt']))
			$ct[] = count($tasks['receipt']);
		if(isset($tasks['revision']))
			$ct[] = count($tasks['revision']);
		if(isset($tasks['needscorrection']))
			$ct[] = count($tasks['needscorrection']);
		$content .= implode('/', $ct);
		$content .= ")";
		if(!empty($tasks['review']) || !empty($tasks['approval']) || !empty($tasks['receipt']) || !empty($tasks['revision']) || !empty($tasks['needscorrection']) || !empty($tasks['workflow'])) {
		$content .= " <i class=\"fa fa-caret-down\"></i></a>\n";
		$content .= "     <ul class=\"dropdown-menu\" role=\"menu\">\n";
		if(!empty($tasks['review'])) {
		$content .= "      <li class=\"dropdown-submenu\">\n";
		$content .=	"       <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText("documents_to_review")."</a>\n";
		$content .= "       <ul class=\"dropdown-menu\" role=\"menu\">\n";
		foreach($tasks['review'] as $t) {
			$doc = $dms->getDocument($t['id']);
			$content .= "      <li><a href=\"../out/out.ViewDocument.php?documentid=".$doc->getID()."&currenttab=revapp\" class=\"table-row-document\" rel=\"document_".$doc->getID()."\">".$doc->getName()."</a></li>";
		}
		$content .= "       </ul>\n";
		$content .= "      </li>\n";
		}
		if(!empty($tasks['approval'])) {
		$content .= "      <li class=\"dropdown-submenu\">\n";
		$content .=	"       <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText("documents_to_approve")."</a>\n";
		$content .= "         <ul class=\"dropdown-menu\" role=\"menu\">\n";
		foreach($tasks['approval'] as $t) {
			$doc = $dms->getDocument($t['id']);
			$content .= "       <li><a href=\"../out/out.ViewDocument.php?documentid=".$doc->getID()."&currenttab=revapp\" class=\"table-row-document\" rel=\"document_".$doc->getID()."\">".$doc->getName()."</a></li>";
		}
		$content .= "       </ul>\n";
		$content .= "      </li>\n";
		}
		if(!empty($tasks['workflow'])) {
		$content .= "      <li class=\"dropdown-submenu\">\n";
		$content .=	"       <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText("documents_to_trigger_workflow")."</a>\n";
		$content .= "         <ul class=\"dropdown-menu\" role=\"menu\">\n";
		foreach($tasks['workflow'] as $t) {
			$doc = $dms->getDocument($t['id']);
			$content .= "       <li><a href=\"../out/out.ViewDocument.php?documentid=".$doc->getID()."&currenttab=workflow\" class=\"table-row-document\" rel=\"document_".$doc->getID()."\">".$doc->getName()."</a></li>";
		}
		$content .= "       </ul>\n";
		$content .= "      </li>\n";
		}
		if(!empty($tasks['receipt'])) {
		$content .= "      <li class=\"dropdown-submenu\">\n";
		$content .=	"       <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText("documents_to_receipt")."</a>\n";
		$content .= "         <ul class=\"dropdown-menu\" role=\"menu\">\n";
		foreach($tasks['receipt'] as $t) {
			$doc = $dms->getDocument($t['id']);
			$content .= "       <li><a href=\"../out/out.ViewDocument.php?documentid=".$doc->getID()."&currenttab=recipients\">".$doc->getName()."</a></li>";
		}
		$content .= "       </ul>\n";
		$content .= "      </li>\n";
		}
		if(!empty($tasks['revision'])) {
		$content .= "      <li class=\"dropdown-submenu\">\n";
		$content .=	"       <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText("documents_to_revise")."</a>\n";
		$content .= "         <ul class=\"dropdown-menu\" role=\"menu\">\n";
		foreach($tasks['revision'] as $t) {
			$doc = $dms->getDocument($t['id']);
			$content .= "       <li><a href=\"../out/out.ViewDocument.php?documentid=".$doc->getID()."&currenttab=revision\">".$doc->getName()."</a></li>";
		}
		$content .= "       </ul>\n";
		$content .= "      </li>\n";
		}
		if(!empty($tasks['needscorrection'])) {
		$content .= "      <li class=\"dropdown-submenu\">\n";
		$content .=	"       <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\">".getMLText("documents_to_correct")."</a>\n";
		$content .= "         <ul class=\"dropdown-menu\" role=\"menu\">\n";
		foreach($tasks['needscorrection'] as $t) {
			$doc = $dms->getDocument($t['id']);
			$content .= "       <li><a href=\"../out/out.ViewDocument.php?documentid=".$doc->getID()."&currenttab=docinfo\">".$doc->getName()."</a></li>";
		}
		$content .= "       </ul>\n";
		$content .= "      </li>\n";
		}
		if ($accessobject->check_view_access('MyDocuments')) {
			$content .= "    <li class=\"divider\"></li>\n";
			$content .= "    <li><a href=\"../out/out.MyDocuments.php\">".getMLText("my_documents")."</a></li>\n";
		}
		$content .= "     </ul>\n";
		}
		$content .= "    </li>\n";
		$content .= "   </ul>\n";
		echo $content;
	} /* }}} */

	/**
	 * Returns the html needed for the clipboard list in the menu
	 *
	 * This function renders the clipboard in a way suitable to be
	 * used as a menu
	 *
	 * @param array $clipboard clipboard containing two arrays for both
	 *        documents and folders.
	 * @return string html code
	 */
	public function menuClipboard() { /* {{{ */
		$clipboard = $this->params['session']->getClipboard();
		if ($this->params['user']->isGuest() || (count($clipboard['docs']) + count($clipboard['folders'])) == 0) {
			return '';
		}
		$content = '';
		$content .= "   <ul id=\"main-menu-clipboard\" class=\"nav pull-right\">\n";
		$content .= "    <li class=\"dropdown add-clipboard-area\">\n";
		$content .= "     <a href=\"#\" class=\"dropdown-toggle\" data-toggle=\"dropdown\" class=\"add-clipboard-area\">".getMLText('clipboard')." (".count($clipboard['folders'])."/".count($clipboard['docs']).") <i class=\"fa fa-caret-down\"></i></a>\n";
		$content .= "     <ul class=\"dropdown-menu\" role=\"menu\">\n";
		foreach($clipboard['folders'] as $folderid) {
			if($folder = $this->params['dms']->getFolder($folderid))
				$content .= "    <li><a href=\"../out/out.ViewFolder.php?folderid=".$folder->getID()."\"><i class=\"fa fa-folder-o\"></i> ".htmlspecialchars($folder->getName())."</a></li>\n";
		}
		foreach($clipboard['docs'] as $docid) {
			if($document = $this->params['dms']->getDocument($docid))
				$content .= "    <li><a href=\"../out/out.ViewDocument.php?documentid=".$document->getID()."\"><i class=\"fa fa-file\"></i> ".htmlspecialchars($document->getName())."</a></li>\n";
		}
		$content .= "    <li class=\"divider\"></li>\n";
		if(isset($this->params['folder']) && $this->params['folder']->getAccessMode($this->params['user']) >= M_READWRITE) {
			$content .= "    <li><a href=\"../op/op.MoveClipboard.php?targetid=".$this->params['folder']->getID()."&refferer=".urlencode($this->params['refferer'])."\">".getMLText("move_clipboard")."</a></li>\n";
		}
//		$content .= "    <li><a href=\"../op/op.ClearClipboard.php?refferer=".urlencode($this->params['refferer'])."\">".getMLText("clear_clipboard")."</a><a class=\"ajax-click\" data-href=\"../op/op.Ajax.php\" data-param1=\"command=clearclipboard\">kkk</a> </li>\n";
		$content .= "    <li><a class=\"ajax-click\" data-href=\"../op/op.Ajax.php\" data-param1=\"command=clearclipboard\">".getMLText("clear_clipboard")."</a></li>\n";
		$content .= "     </ul>\n";
		$content .= "    </li>\n";
		$content .= "   </ul>\n";
		echo $content;
	} /* }}} */

	/**
	 * Return clipboard content rendered as html
	 *
	 * @param array clipboard
	 * @return string rendered html content
	 */
	public function mainClipboard() { /* {{{ */
		$dms = $this->params['dms'];
		$clipboard = $this->params['session']->getClipboard();
		$cachedir = $this->params['cachedir'];
		$previewwidth = $this->params['previewWidthList'];
		$timeout = $this->params['timeout'];
		$xsendfile = $this->params['xsendfile'];

		$previewer = new SeedDMS_Preview_Previewer($cachedir, $previewwidth, $timeout, $xsendfile);
		$content = '';
		$foldercount = $doccount = 0;
		if($clipboard['folders']) {
			foreach($clipboard['folders'] as $folderid) {
				/* FIXME: check for access rights, which could have changed after adding the folder to the clipboard */
				if($folder = $dms->getFolder($folderid)) {
					$comment = $folder->getComment();
					if (strlen($comment) > 150) $comment = substr($comment, 0, 147) . "...";
//					$content .= "<tr draggable=\"true\" rel=\"folder_".$folder->getID()."\" class=\"folder table-row-folder\" formtoken=\"".createFormKey('movefolder')."\">";
					$content .= $this->folderListRowStart($folder);
					$content .= "<td><a draggable=\"false\" href=\"out.ViewFolder.php?folderid=".$folder->getID()."&showtree=".showtree()."\"><img draggable=\"false\" src=\"".$this->imgpath."folder.png\" width=\"24\" height=\"24\" border=0></a></td>\n";
					$content .= "<td><a draggable=\"false\" href=\"out.ViewFolder.php?folderid=".$folder->getID()."&showtree=".showtree()."\">" . htmlspecialchars($folder->getName()) . "</a>";
					if($comment) {
						$content .= "<br /><span style=\"font-size: 85%;\">".htmlspecialchars($comment)."</span>";
					}
					$content .= "</td>\n";
					$content .= "<td>\n";
					$content .= "<div class=\"list-action\"><a class=\"removefromclipboard\" rel=\"F".$folderid."\" msg=\"".getMLText('splash_removed_from_clipboard')."\" _href=\"../op/op.RemoveFromClipboard.php?folderid=".(isset($this->params['folder']) ? $this->params['folder']->getID() : '')."&id=".$folderid."&type=folder\" title=\"".getMLText('rm_from_clipboard')."\"><i class=\"fa fa-remove\"></i></a></div>";
					$content .= "</td>\n";
					//$content .= "</tr>\n";
					$content .= $this->folderListRowEnd($folder);
					$foldercount++;
				}
			}
		}
		if($clipboard['docs']) {
			foreach($clipboard['docs'] as $docid) {
				/* FIXME: check for access rights, which could have changed after adding the document to the clipboard */
				if($document = $dms->getDocument($docid)) {
					$comment = $document->getComment();
					if (strlen($comment) > 150) $comment = substr($comment, 0, 147) . "...";
					if($latestContent = $document->getLatestContent()) {
						$previewer->createPreview($latestContent);
						$version = $latestContent->getVersion();
						$status = $latestContent->getStatus();
						
						$content .= "<tr draggable=\"true\" rel=\"document_".$docid."\" class=\"table-row-document\" formtoken=\"".createFormKey('movedocument')."\">";

						if (file_exists($dms->contentDir . $latestContent->getPath())) {
							$content .= "<td><a draggable=\"false\" href=\"../op/op.Download.php?documentid=".$docid."&version=".$version."\">";
							if($previewer->hasPreview($latestContent)) {
								$content .= "<img draggable=\"false\" class=\"mimeicon\" width=\"40\"src=\"../op/op.Preview.php?documentid=".$document->getID()."&version=".$latestContent->getVersion()."&width=40\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
							} else {
								$content .= "<img draggable=\"false\" class=\"mimeicon\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\">";
							}
							$content .= "</a></td>";
						} else
							$content .= "<td><img draggable=\"false\" class=\"mimeicon\" src=\"".$this->getMimeIcon($latestContent->getFileType())."\" title=\"".htmlspecialchars($latestContent->getMimeType())."\"></td>";
						
						$content .= "<td><a draggable=\"false\" href=\"out.ViewDocument.php?documentid=".$docid."&showtree=".showtree()."\">" . htmlspecialchars($document->getName()) . "</a>";
						if($comment) {
							$content .= "<br /><span style=\"font-size: 85%;\">".htmlspecialchars($comment)."</span>";
						}
						$content .= "</td>\n";
						$content .= "<td>\n";
						$content .= "<div class=\"list-action\"><a class=\"removefromclipboard\" rel=\"D".$docid."\" msg=\"".getMLText('splash_removed_from_clipboard')."\" _href=\"../op/op.RemoveFromClipboard.php?folderid=".(isset($this->params['folder']) ? $this->params['folder']->getID() : '')."&id=".$docid."&type=document\" title=\"".getMLText('rm_from_clipboard')."\"><i class=\"fa fa-remove\"></i></a></div>";
						$content .= "</td>\n";
						$content .= "</tr>";
						$doccount++;
					}
				}
			}
		}

		/* $foldercount or $doccount will only count objects which are
		 * actually available
		 */
		if($foldercount || $doccount) {
			$content = "<table class=\"table\">".$content;
			$content .= "</table>";
		} else {
		}
		$content .= "<div class=\"alert add-clipboard-area\">".getMLText("drag_icon_here")."</div>";
		echo $content;
	} /* }}} */

}
