<?php
//    SeedDMS. Document Management System
//    Copyright (C) 2015 Uwe Steinmann
//
//    This program is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program; if not, write to the Free Software
//    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.

include("../inc/inc.Settings.php");
include("../inc/inc.LogInit.php");
include("../inc/inc.Language.php");
include("../inc/inc.Init.php");
include("../inc/inc.Extension.php");
include("../inc/inc.DBInit.php");
include("../inc/inc.ClassUI.php");
include("../inc/inc.Authentication.php");

if (!isset($_POST["documentid"]) || !is_numeric($_POST["documentid"]) || intval($_POST["documentid"])<1) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

$documentid = $_POST["documentid"];
$document = $dms->getDocument($documentid);
$folder = $document->getFolder();

if (!is_object($document)) {
	UI::exitError(getMLText("document_title", array("documentname" => getMLText("invalid_doc_id"))),getMLText("invalid_doc_id"));
}

if ($document->getAccessMode($user) < M_READWRITE) {
	UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("access_denied"));
}

if($settings->_quota > 0) {
	$remain = checkQuota($user);
	if ($remain < 0) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("quota_exceeded", array('bytes'=>SeedDMS_Core_File::format_filesize(abs($remain)))));
	}
}

if ($document->isLocked()) {
	$lockingUser = $document->getLockingUser();
	if (($lockingUser->getID() != $user->getID()) && ($document->getAccessMode($user) != M_ALL)) {
		UI::exitError(getMLText("document_title", array("documentname" => htmlspecialchars($document->getName()))),getMLText("no_update_cause_locked"));
	}
	else $document->setLocked(false);
}

if(isset($_POST["comment"]))
	$comment  = $_POST["comment"];
else
	$comment = "";


	// Get the list of reviewers and approvers for this document.
	$reviewers = array();
	$approvers = array();
	$recipients = array();
	$reviewers["i"] = array();
	$reviewers["g"] = array();
	$approvers["i"] = array();
	$approvers["g"] = array();
	$recipients["i"] = array();
	$recipients["g"] = array();
	$workflow = null;

	if($settings->_workflowMode == 'traditional' || $settings->_workflowMode == 'traditional_only_approval') {
		if($settings->_workflowMode == 'traditional') {
			// Retrieve the list of individual reviewers from the form.
			$reviewers["i"] = array();
			if (isset($_POST["indReviewers"])) {
				foreach ($_POST["indReviewers"] as $ind) {
					$reviewers["i"][] = $ind;
				}
			}
			// Retrieve the list of reviewer groups from the form.
			$reviewers["g"] = array();
			if (isset($_POST["grpReviewers"])) {
				foreach ($_POST["grpReviewers"] as $grp) {
					$reviewers["g"][] = $grp;
				}
			}
			// Retrieve the list of reviewer groups whose members become individual reviewers
			if (isset($_POST["grpIndReviewers"])) {
				foreach ($_POST["grpIndReviewers"] as $grp) {
					if($group = $dms->getGroup($grp)) {
						$members = $group->getUsers();
						foreach($members as $member)
							$reviewers["i"][] = $member->getID();
					}
				}
			}
		}

		// Retrieve the list of individual approvers from the form.
		$approvers["i"] = array();
		if (isset($_POST["indApprovers"])) {
			foreach ($_POST["indApprovers"] as $ind) {
				$approvers["i"][] = $ind;
			}
		}
		// Retrieve the list of approver groups from the form.
		$approvers["g"] = array();
		if (isset($_POST["grpApprovers"])) {
			foreach ($_POST["grpApprovers"] as $grp) {
				$approvers["g"][] = $grp;
			}
		}
		// Retrieve the list of reviewer groups whose members become individual approvers
		if (isset($_POST["grpIndApprovers"])) {
			foreach ($_POST["grpIndApprovers"] as $grp) {
				if($group = $dms->getGroup($grp)) {
					$members = $group->getUsers();
					foreach($members as $member)
						$approvers["i"][] = $member->getID();
				}
			}
		}

		// add mandatory reviewers/approvers
		$docAccess = $folder->getReadAccessList($settings->_enableAdminRevApp, $settings->_enableOwnerRevApp);
		if($settings->_workflowMode == 'traditional') {
			$res=$user->getMandatoryReviewers();
			foreach ($res as $r){

				if ($r['reviewerUserID']!=0){
					foreach ($docAccess["users"] as $usr)
						if ($usr->getID()==$r['reviewerUserID']){
							$reviewers["i"][] = $r['reviewerUserID'];
							break;
						}
				}
				else if ($r['reviewerGroupID']!=0){
					foreach ($docAccess["groups"] as $grp)
						if ($grp->getID()==$r['reviewerGroupID']){
							$reviewers["g"][] = $r['reviewerGroupID'];
							break;
						}
				}
			}
		}
		$res=$user->getMandatoryApprovers();
		foreach ($res as $r){

			if ($r['approverUserID']!=0){
				foreach ($docAccess["users"] as $usr)
					if ($usr->getID()==$r['approverUserID']){
						$approvers["i"][] = $r['approverUserID'];
						break;
					}
			}
			else if ($r['approverGroupID']!=0){
				foreach ($docAccess["groups"] as $grp)
					if ($grp->getID()==$r['approverGroupID']){
						$approvers["g"][] = $r['approverGroupID'];
						break;
					}
			}
		}
		if($settings->_workflowMode == 'traditional' && !$settings->_allowReviewerOnly) {
			/* Check if reviewers are send but no approvers */
			if(($reviewers["i"] || $reviewers["g"]) && !$approvers["i"] && !$approvers["g"]) {
				UI::exitError(getMLText("folder_title", array("foldername" => $folder->getName())),getMLText("error_uploading_reviewer_only"));
			}
		}
	} elseif($settings->_workflowMode == 'advanced') {
		if(!$workflow = $user->getMandatoryWorkflow()) {
			if(isset($_POST["workflow"]))
				$workflow = $dms->getWorkflow($_POST["workflow"]);
			else
				$workflow = null;
		}
	}

	// Retrieve the list of individual recipients from the form.
	$recipients["i"] = array();
	if (isset($_POST["indRecipients"])) {
		foreach ($_POST["indRecipients"] as $ind) {
			$recipients["i"][] = $ind;
		}
	}
	// Retrieve the list of recipient groups from the form.
	$recipients["g"] = array();
	if (isset($_POST["grpRecipients"])) {
		foreach ($_POST["grpRecipients"] as $grp) {
			$recipients["g"][] = $grp;
		}
	}
	// Retrieve the list of recipient groups whose members become individual recipients
	if (isset($_POST["grpIndRecipients"])) {
		foreach ($_POST["grpIndRecipients"] as $grp) {
			if($group = $dms->getGroup($grp)) {
				$members = $group->getUsers();
				foreach($members as $member) {
					/* Do not add the uploader itself and reviewers */
					if(!$settings->_enableFilterReceipt || ($member->getID() != $user->getID() && !in_array($member->getID(), $reviewers['i'])))
						if(!in_array($member->getID(), $recipients["i"]))
							$recipients["i"][] = $member->getID();
				}
			}
		}
	}

	if(isset($_POST["attributes"]) && $_POST["attributes"]) {
		$attributes = $_POST["attributes"];
		foreach($attributes as $attrdefid=>$attribute) {
			$attrdef = $dms->getAttributeDefinition($attrdefid);
			if($attribute) {
				if(!$attrdef->validate($attribute)) {
					switch($attrdef->getValidationError()) {
					case 5:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("attr_malformed_email", array("attrname"=>$attrdef->getName(), "value"=>$attribute)));
						break;
					case 4:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("attr_malformed_url", array("attrname"=>$attrdef->getName(), "value"=>$attribute)));
						break;
					case 3:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("attr_no_regex_match", array("attrname"=>$attrdef->getName(), "value"=>$attribute, "regex"=>$attrdef->getRegex())));
						break;
					case 2:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("attr_max_values", array("attrname"=>$attrdef->getName())));
						break;
					case 1:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("attr_min_values", array("attrname"=>$attrdef->getName())));
						break;
					default:
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
					}
				}
				/*
				if($attrdef->getRegex()) {
					if(!preg_match($attrdef->getRegex(), $attribute)) {
						UI::exitError(getMLText("document_title", array("documentname" => $folder->getName())),getMLText("attr_no_regex_match"));
					}
				}
				if(is_array($attribute)) {
					if($attrdef->getMinValues() > count($attribute)) {
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("attr_min_values", array("attrname"=>$attrdef->getName())));
					}
					if($attrdef->getMaxValues() && $attrdef->getMaxValues() < count($attribute)) {
						UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("attr_max_values", array("attrname"=>$attrdef->getName())));
					}
				}
				 */
			}
		}
	} else {
		$attributes = array();
	}

	$contentResult=$document->checkIn($comment, $user, $reviewers, $approvers, $version=0, $attributes, $workflow, $settings->_initialDocumentStatus);
	if (is_bool($contentResult) && !$contentResult) {
		UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
	} elseif (is_bool($contentResult) && $contentResult) {
	} else {
		// Send notification to subscribers.
		if ($notifier){
			$notifyList = $document->getNotifyList();
			$folder = $document->getFolder();

			$subject = "document_updated_email_subject";
			$message = "document_updated_email_body";
			$params = array();
			$params['name'] = $document->getName();
			$params['folder_path'] = $folder->getFolderPathPlain();
			$params['username'] = $user->getFullName();
			$params['comment'] = $document->getComment();
			$params['version_comment'] = $contentResult->getContent()->getComment();
			$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
			$params['sitename'] = $settings->_siteName;
			$params['http_root'] = $settings->_httpRoot;
			$notifier->toList($user, $notifyList["users"], $subject, $message, $params);
			foreach ($notifyList["groups"] as $grp) {
				$notifier->toGroup($user, $grp, $subject, $message, $params);
			}

			if($workflow && $settings->_enableNotificationWorkflow) {
				$subject = "request_workflow_action_email_subject";
				$message = "request_workflow_action_email_body";
				$params = array();
				$params['name'] = $document->getName();
				$params['version'] = $contentResult->getContent()->getVersion();
				$params['workflow'] = $workflow->getName();
				$params['folder_path'] = $folder->getFolderPathPlain();
				$params['current_state'] = $workflow->getInitState()->getName();
				$params['username'] = $user->getFullName();
				$params['sitename'] = $settings->_siteName;
				$params['http_root'] = $settings->_httpRoot;
				$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();

				foreach($workflow->getNextTransitions($workflow->getInitState()) as $ntransition) {
					foreach($ntransition->getUsers() as $tuser) {
						$notifier->toIndividual($user, $tuser->getUser(), $subject, $message, $params);
					}
					foreach($ntransition->getGroups() as $tuser) {
						$notifier->toGroup($user, $tuser->getGroup(), $subject, $message, $params);
					}
				}
			}

			if($settings->_enableNotificationAppRev) {
				/* Reviewers and approvers will be informed about the new document */
				if($reviewers['i'] || $reviewers['g']) {
					$subject = "review_request_email_subject";
					$message = "review_request_email_body";
					$params = array();
					$params['name'] = $document->getName();
					$params['folder_path'] = $folder->getFolderPathPlain();
					$params['version'] = $contentResult->getContent()->getVersion();
					$params['comment'] = $comment;
					$params['username'] = $user->getFullName();
					$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
					$params['sitename'] = $settings->_siteName;
					$params['http_root'] = $settings->_httpRoot;

					foreach($reviewers['i'] as $reviewerid) {
						$notifier->toIndividual($user, $dms->getUser($reviewerid), $subject, $message, $params);
					}
					foreach($reviewers['g'] as $reviewergrpid) {
						$notifier->toGroup($user, $dms->getGroup($reviewergrpid), $subject, $message, $params);
					}
				}

				elseif($approvers['i'] || $approvers['g']) {
					$subject = "approval_request_email_subject";
					$message = "approval_request_email_body";
					$params = array();
					$params['name'] = $document->getName();
					$params['folder_path'] = $folder->getFolderPathPlain();
					$params['version'] = $contentResult->getContent()->getVersion();
					$params['comment'] = $comment;
					$params['username'] = $user->getFullName();
					$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
					$params['sitename'] = $settings->_siteName;
					$params['http_root'] = $settings->_httpRoot;

					foreach($approvers['i'] as $approverid) {
						$notifier->toIndividual($user, $dms->getUser($approverid), $subject, $message, $params);
					}
					foreach($approvers['g'] as $approvergrpid) {
						$notifier->toGroup($user, $dms->getGroup($approvergrpid), $subject, $message, $params);
					}
				}
			}
		}

		if($recipients['i']) {
			foreach($recipients['i'] as $uid) {
				if($u = $dms->getUser($uid)) {
					$res = $contentResult->getContent()->addIndRecipient($u, $user);
				}
			}
		}
		if($recipients['g']) {
			foreach($recipients['g'] as $gid) {
				if($g = $dms->getGroup($gid)) {
					$res = $contentResult->getContent()->addGrpRecipient($g, $user);
				}
			}
		}

		$oldexpires = $document->getExpires();
		switch($_POST["presetexpdate"]) {
		case "date":
			$tmp = explode('-', $_POST["expdate"]);
			$expires = mktime(0,0,0, $tmp[1], $tmp[2], $tmp[0]);
			break;
		case "1w":
			$tmp = explode('-', date('Y-m-d'));
			$expires = mktime(0,0,0, $tmp[1], $tmp[2]+7, $tmp[0]);
			break;
		case "1m":
			$tmp = explode('-', date('Y-m-d'));
			$expires = mktime(0,0,0, $tmp[1]+1, $tmp[2], $tmp[0]);
			break;
		case "1y":
			$tmp = explode('-', date('Y-m-d'));
			$expires = mktime(0,0,0, $tmp[1], $tmp[2], $tmp[0]+1);
			break;
		case "2y":
			$tmp = explode('-', date('Y-m-d'));
			$expires = mktime(0,0,0, $tmp[1], $tmp[2], $tmp[0]+2);
			break;
		case "never":
		default:
			$expires = null;
			break;
		}

		if($oldexpires != $expires) {
			if($document->setExpires($expires)) {
				if($notifier) {
					$notifyList = $document->getNotifyList();
					$folder = $document->getFolder();

					// Send notification to subscribers.
					$subject = "expiry_changed_email_subject";
					$message = "expiry_changed_email_body";
					$params = array();
					$params['name'] = $document->getName();
					$params['folder_path'] = $folder->getFolderPathPlain();
					$params['username'] = $user->getFullName();
					$params['url'] = getBaseUrl().$settings->_httpRoot."out/out.ViewDocument.php?documentid=".$document->getID();
					$params['sitename'] = $settings->_siteName;
					$params['http_root'] = $settings->_httpRoot;
					$notifier->toList($user, $notifyList["users"], $subject, $message, $params);
					foreach ($notifyList["groups"] as $grp) {
						$notifier->toGroup($user, $grp, $subject, $message, $params);
					}
				}
			} else {
				UI::exitError(getMLText("document_title", array("documentname" => $document->getName())),getMLText("error_occured"));
			}
		}
	}

add_log_line("?documentid=".$documentid);
header("Location:../out/out.ViewDocument.php?documentid=".$documentid);

?>

