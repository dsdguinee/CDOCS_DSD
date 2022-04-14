<?php
/**
 * Implementation of Cron controller
 *
 * @category   DMS
 * @package    SeedDMS
 * @license    GPL 2
 * @version    @version@
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2020 Uwe Steinmann
 * @version    Release: @package_version@
 */

/**
 * Class which does the busines logic for the regular cron job
 *
 * @category   DMS
 * @package    SeedDMS
 * @author     Uwe Steinmann <uwe@steinmann.cx>
 * @copyright  Copyright (C) 2010-2020 Uwe Steinmann
 * @version    Release: @package_version@
 */
class SeedDMS_Controller_Cron extends SeedDMS_Controller_Common {

	public function run() { /* {{{ */
		$dms = $this->params['dms'];
		$settings = $this->params['settings'];
		$mode = 'run'; //$this->params['mode'];
		$db = $dms->getDb();

		$scheduler = new SeedDMS_Scheduler($db);
		$tasks = $scheduler->getTasks();

		foreach($tasks as $task) {
			if(isset($GLOBALS['SEEDDMS_SCHEDULER']['tasks'][$task->getExtension()]) && is_object($taskobj = resolveTask($GLOBALS['SEEDDMS_SCHEDULER']['tasks'][$task->getExtension()][$task->getTask()]))) {
				switch($mode) {
				case "run":
					if(method_exists($taskobj, 'execute')) {
            if(!$task->getDisabled() && $task->isDue()) {
							if($user = $dms->getUserByLogin('cli_scheduler')) {
								if($taskobj->execute($task)) {
									add_log_line("Execution of task ".$task->getExtension()."::".$task->getTask()." successful.");
									$task->updateLastNextRun();
								} else {
									add_log_line("Execution of task ".$task->getExtension()."::".$task->getTask()." failed, task has been disabled.", PEAR_LOG_ERR);
									$task->setDisabled(1);
								}
							} else {
								add_log_line("Execution of task ".$task->getExtension()."::".$task->getTask()." failed because of missing user 'cli_scheduler'. Task has been disabled.", PEAR_LOG_ERR);
								$task->setDisabled(1);
							}
            }
					}
					break;
				}
			}
		}

	return true;
	} /* }}} */
}

