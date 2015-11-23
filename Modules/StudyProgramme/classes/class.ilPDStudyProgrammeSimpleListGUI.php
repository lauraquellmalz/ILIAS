<?php

/* Copyright (c) 2015 Richard Klees <richard.klees@concepts-and-training.de> Extended GPL, see docs/LICENSE */

require_once("Services/Block/classes/class.ilBlockGUI.php");
require_once('./Modules/StudyProgramme/classes/class.ilObjStudyProgrammeAdmin.php');
require_once 'Services/PersonalDesktop/classes/class.ilPDSelectedItemsBlockGUI.php';

/**
 * Personal Desktop-Presentation for the Study Programme
 *
 * @author : Richard Klees <richard.klees@concepts-and-training.de>
 * @author : Stefan Hecken <stefan.hecken@concepts-and-training.de>
 * @ilCtrl_IsCalledBy ilPDStudyProgrammeSimpleListGUI: ilColumnGUI
 */
class ilPDStudyProgrammeSimpleListGUI extends ilBlockGUI {
	const BLOCK_TYPE = "prgsimplelist";
	
	/**
	 * @var ilLanguage
	 */
	protected $il_lng;
	
	/**
	 * @var ilUser
	 */
	protected $il_user;
	
	/**
	 * @var ilAccessHandler
	 */
	protected $il_access;
	
	/**
	 * @var ilSetting
	 */
	protected $il_setting;

	/**
	 * @var ilStudyProgrammeUserAssignment[]
	 */
	protected $users_assignments;

	/**
	* @var visible_on_pd_mode
	*/
	protected $visible_on_pd_mode;
	
	/**
	* @var show_info_message
	*/
	protected $show_info_message;

	public function __construct() {
		global $lng, $ilUser, $ilAccess, $ilSetting;
		$this->il_lng = $lng;
		$this->il_user = $ilUser;
		$this->il_access = $ilAccess;
		$this->il_setting = $ilSetting;
		$this->il_logger = ilLoggerFactory::getLogger('prg');

		// No need to load data, as we won't display this. 
		if (!$this->shouldShowThisList()) {
			return;
		}

		$this->loadUsersAssignments();
		
		// As this won't be visible we don't have to initialize this.
		if (!$this->userHasReadableStudyProgrammes()) {
			return;
		}

		//check which kind of option is selected in settings
		$this->setVisibleOnPDMode();
		//check to display info message if option "read" is selected
		$this->checkToShowInfoMessage();

		$this->setTitle($this->il_lng->txt("objs_prg"));
	}
	
	public function getHTML() {
		// TODO: This should be determined from somewhere up in the hierarchy, as
		// this will lead to problems, when e.g. a command changes. But i don't see
		// how atm...
		if (!$this->shouldShowThisList()) {
			return "";
		}
		
		if (!$this->userHasReadableStudyProgrammes()) {
			return "";
		}
		return parent::getHTML();
	}
	
	public function getDataSectionContent() {
		$content = "";
		foreach ($this->users_assignments as $assignment) {
			if(!$this->isReadable($assignment)) {
				continue;
			}

			try {
				$list_item = $this->new_ilStudyProgrammeAssignmentListGUI($assignment);
				$content .= $list_item->getHTML();
			}
			catch (ilStudyProgrammeNoProgressForAssignmentException $e) {
				$this->il_logger->alert("$e");
			}
		}
		return $content;
	}
	
	static public function getBlockType() {
		return self::BLOCK_TYPE;
	}
	
	static public function isRepositoryObject() {
		return false;
	}
	
	public function fillDataSection() {
		assert($this->userHasReadableStudyProgrammes()); // We should not get here.
		$this->tpl->setVariable("BLOCK_ROW", $this->getDataSectionContent());
	}
	
	
	protected function userHasVisibleStudyProgrammes() {
		if (count($this->users_assignments) == 0) {
			return false;
		}
		foreach ($this->users_assignments as $assignment) {
			if ($this->isVisible($assignment)) {
				return true;
			}
		}
		return false;
	}

	protected function userHasReadableStudyProgrammes() {
		if (count($this->users_assignments) == 0) {
			return false;
		}
		foreach ($this->users_assignments as $assignment) {
			if ($this->isReadable($assignment)) {
				return true;
			}
		}
		return false;
	}
	
	protected function setVisibleOnPDMode() {
		$this->visible_on_pd_mode = $this->il_setting->get(ilObjStudyProgrammeAdmin::SETTING_VISIBLE_ON_PD);
	}

	protected function hasPermission(ilStudyProgrammeUserAssignment $assignment, $permission) {
		$prg = $assignment->getStudyProgramme();
		return $this->il_access->checkAccess($permission, "", $prg->getRefId(), "prg", $prg->getId());
	}

	protected function checkToShowInfoMessage() {
		$this->show_info_message = ($_GET['view'] == ilPDSelectedItemsBlockGUI::VIEW_MY_STUDYPROGRAMME);
	}

	protected function isVisible(ilStudyProgrammeUserAssignment $assignment) {
		return $this->hasPermission($assignment,"visible");
	}

	protected function isReadable(ilStudyProgrammeUserAssignment $assignment) {
		if($this->visible_on_pd_mode == ilObjStudyProgrammeAdmin::SETTING_VISIBLE_ON_PD_ALLWAYS){
			return true;
		}

		return $this->hasPermission($assignment,"read");
	}
	
	protected function shouldShowThisList() {
		return $_GET["cmd"] == "jumpToSelectedItems";
	}
	
	protected function loadUsersAssignments() {
		require_once("Modules/StudyProgramme/classes/class.ilStudyProgrammeUserAssignment.php");
		$this->users_assignments = ilStudyProgrammeUserAssignment::getInstancesOfUser($this->il_user->getId());
	}
	
	protected function new_ilStudyProgrammeAssignmentListGUI(ilStudyProgrammeUserAssignment $a_assignment) {
		require_once("Modules/StudyProgramme/classes/class.ilStudyProgrammeProgressListGUI.php");
		$progress = $a_assignment->getStudyProgramme()->getProgressForAssignment($a_assignment->getId());
		return new ilStudyProgrammeProgressListGUI($progress);
	}
}