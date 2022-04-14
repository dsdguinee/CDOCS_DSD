<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2018 Uwe Steinmann <uwe@steinmann.cx>
*  All rights reserved
*
*  This script is part of the SeedDMS project. The SeedDMS project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Base class for scheduler task
 *
 * @author  Uwe Steinmann <uwe@steinmann.cx>
 * @package SeedDMS
 */
class SeedDMS_SchedulerTaskBase {
	var $dms;

	var $user;

	var $settings;

	var $logger;

	public function __construct($dms=null, $user=null, $settings=null, $logger=null) { /* {{{ */
		$this->dms = $dms;
		$this->user = $user;
		$this->settings = $settings;
		$this->logger = $logger;
	} /* }}} */

	public function execute($task) { /* {{{ */
		return true;
	} /* }}} */

	public function getDescription() { /* {{{ */
		return '';
	} /* }}} */

	public function getAdditionalParams() { /* {{{ */
		return array();
	} /* }}} */

	public function getAdditionalParamByName($name) { /* {{{ */
		foreach($this->getAdditionalParams() as $param) {
			if($param['name'] == $name)
				return $param;
		}
		return null;
	} /* }}} */
}

?>
