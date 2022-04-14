<?php
/**
 * Implementation of RoleMgr view
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
 * Class which outputs the html page for RoleMgr view
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Markus Westphal, Malcolm Cowe, Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2002-2005 Markus Westphal,
 *             2006-2008 Malcolm Cowe, 2010 Matteo Lucarelli,
 *             2010-2012 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_View_RoleMgr extends SeedDMS_Bootstrap_Style {

	function js() { /* {{{ */
		$selrole = $this->params['selrole'];

		header('Content-Type: application/javascript');
?>
function checkForm()
{
	msg = new Array();

	if($("#name").val() == "") msg.push("<?php printMLText("js_no_name");?>");
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

$(document).ready( function() {
	$('body').on('submit', '#form', function(ev){
		if(checkForm()) return;
		event.preventDefault();
	});
	$( "#selector" ).change(function() {
		$('div.ajax').trigger('update', {roleid: $(this).val()});
		window.history.pushState({"html":"","pageTitle":""},"", '../out/out.RoleMgr.php?roleid=' + $(this).val());
	});
});
<?php
	} /* }}} */

	function info() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selrole = $this->params['selrole'];
		$settings = $this->params['settings'];
		$accessobject = $this->params['accessobject'];

		if($selrole) {
			$this->contentHeading(getMLText("role_info"));
			$users = $selrole->getUsers();
			if($users) {
				echo "<table class=\"table table-condensed\"><thead><tr><th>".getMLText('name')."</th><th></th></tr></thead><tbody>\n";
				foreach($users as $currUser) {
					echo "<tr>";
					echo "<td>";
					echo htmlspecialchars($currUser->getFullName())." (".htmlspecialchars($currUser->getLogin()).")";
					echo "<br /><a href=\"mailto:".$currUser->getEmail()."\">".htmlspecialchars($currUser->getEmail())."</a>";
					if($currUser->getComment())
						echo "<br /><small>".htmlspecialchars($currUser->getComment())."</small>";
					echo "</td>";
					echo "<td>";
					if($accessobject->check_view_access(array('UsrMgr', 'RemoveUser'))) {
						echo "<div class=\"list-action\">";
						echo $this->html_link('UsrMgr', array('userid'=>$currUser->getID()), array(), '<i class="fa fa-edit"></i>', false);
						echo $this->html_link('RemoveUser', array('userid'=>$currUser->getID()), array(), '<i class="fa fa-remove"></i>', false);
						echo "</div>";
					}
					echo "</td>";
					echo "</tr>";
				}
				echo "</tbody></table>";
			}
		}
	} /* }}} */

	function actionmenu() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$selrole = $this->params['selrole'];
		$accessop = $this->params['accessobject'];

		if($selrole) {
			if(!$selrole->isUsed() && $accessop->check_controller_access('RoleMgr', array('action'=>'removerole'))) {
?>
			<form style="display: inline-block;" method="post" action="../op/op.RoleMgr.php" >
				<?php echo createHiddenFieldWithKey('removerole'); ?>
				<input type="hidden" name="roleid" value="<?php echo $selrole->getID()?>">
				<input type="hidden" name="action" value="removerole">
				<button type="submit" class="btn"><i class="fa fa-remove"></i> <?php echo getMLText("rm_role")?></button>
			</form>
<?php
			}
		}
	} /* }}} */

	function form() { /* {{{ */
		$selrole = $this->params['selrole'];

		$this->showRoleForm($selrole);
	} /* }}} */

	function showRoleForm($currRole) { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessop = $this->params['accessobject'];
?>
	<form action="../op/op.RoleMgr.php" method="post" enctype="multipart/form-data" name="form" id="form">
<?php
		if($currRole) {
			echo createHiddenFieldWithKey('editrole');
?>
	<input type="hidden" name="roleid" id="roleid" value="<?php print $currRole->getID();?>">
	<input type="hidden" name="action" value="editrole">
<?php
		} else {
			echo createHiddenFieldWithKey('addrole');
?>
	<input type="hidden" id="roleid" value="0">
	<input type="hidden" name="action" value="addrole">
<?php
		}
?>
	<table class="table-condensed">
		<tr>
			<td><?php printMLText("role_name");?>:</td>
			<td><input type="text" name="name" id="name" value="<?php print $currRole ? htmlspecialchars($currRole->getName()) : "";?>"></td>
		</tr>
		<tr>
			<td><?php printMLText("role_type");?>:</td>
			<td><select name="role"><option value="<?php echo SeedDMS_Core_Role::role_user ?>"><?php printMLText("role_user"); ?></option><option value="<?php echo SeedDMS_Core_Role::role_admin ?>" <?php if($currRole && $currRole->getRole() == SeedDMS_Core_Role::role_admin) echo "selected"; ?>><?php printMLText("role_admin"); ?></option><option value="<?php echo SeedDMS_Core_Role::role_guest ?>" <?php if($currRole && $currRole->getRole() == SeedDMS_Core_Role::role_guest) echo "selected"; ?>><?php printMLText("role_guest"); ?></option></select></td>
		</tr>
<?php
		if($currRole && $currRole->getRole() != SeedDMS_Core_Role::role_admin) {
			echo "<tr>";
			echo "<td>".getMLText('restrict_access')."</td>";
			echo "<td>";
			foreach(array(S_DRAFT_REV, S_DRAFT_APP, S_IN_WORKFLOW, S_REJECTED, S_RELEASED, S_IN_REVISION, S_DRAFT, S_EXPIRED, S_OBSOLETE, S_NEEDS_CORRECTION) as $status) {
				echo "<input type=\"checkbox\" name=\"noaccess[]\" value=\"".$status."\" ".(in_array($status, $currRole->getNoAccess()) ? "checked" : "")."> ".getOverallStatusText($status)."<br />";
			}
			echo "</td>";
			echo "</tr>";
		}
		if($currRole && $accessop->check_controller_access('RoleMgr', array('action'=>'editrole')) || !$currRole && $accessop->check_controller_access('RoleMgr', array('action'=>'addrole'))) {
?>
		<tr>
			<td></td>
			<td><button type="submit" class="btn"><i class="fa fa-save"></i> <?php printMLText($currRole ? "save" : "add_role")?></button></td>
		</tr>
<?php
		}
?>
	</table>
	</form>
<?php
	} /* }}} */

	function show() { /* {{{ */
		$dms = $this->params['dms'];
		$user = $this->params['user'];
		$accessop = $this->params['accessobject'];
		$selrole = $this->params['selrole'];
		$roles = $this->params['allroles'];

		$this->htmlStartPage(getMLText("admin_tools"));
		$this->globalNavigation();
		$this->contentStart();
		$this->pageNavigation(getMLText("admin_tools"), "admin_tools");

		$this->contentHeading(getMLText("role_management"));
?>
<div class="row-fluid">
<div class="span4">
<form class="form-horizontal">
<?php
		$options = array();
		$options[] = array("-1", getMLText("choose_role"));
		if($accessop->check_controller_access('RoleMgr', array('action'=>'addrole'))) {
			$options[] = array("0", getMLText("add_role"));
		}
		foreach ($roles as $currRole) {
			$options[] = array($currRole->getID(), htmlspecialchars($currRole->getName()), $selrole && $currRole->getID()==$selrole->getID());
		}
		$this->formField(
			null, //getMLText("selection"),
			array(
				'element'=>'select',
				'id'=>'selector',
				'class'=>'chzn-select',
				'options'=>$options
			)
		);
?>
</form>
	<div class="ajax" style="margin-bottom: 15px;" data-view="RoleMgr" data-action="actionmenu" <?php echo ($selrole ? "data-query=\"roleid=".$selrole->getID()."\"" : "") ?>></div>
<?php if($accessop->check_view_access($this, array('action'=>'info'))) { ?>
	<div class="ajax" data-view="RoleMgr" data-action="info" <?php echo ($selrole ? "data-query=\"roleid=".$selrole->getID()."\"" : "") ?>></div>
<?php } ?>
</div>

<div class="span8">
<?php if($accessop->check_view_access($this, array('action'=>'form'))) { ?>
	<div class="well">
		<div class="ajax" data-view="RoleMgr" data-action="form" <?php echo ($selrole ? "data-query=\"roleid=".$selrole->getID()."\"" : "") ?>></div>
	</div>
<?php } else {
	$this->errorMsg(getMLText('access_denied'));
} ?>
</div>
</div>

<?php
		$this->contentEnd();
		$this->htmlEndPage();
	} /* }}} */
}
