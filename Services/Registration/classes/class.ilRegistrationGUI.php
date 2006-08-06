<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

/** @defgroup ServicesRegistration Services/Registration
 */

/**
* Class ilRegistrationGUI
*
* @author Stefan Meyer <smeyer@databay.de> 
* @version $Id$
*
* @ilCtrl_Calls ilRegistrationGUI:
* 
* @ingroup ServicesRegistration
*/

require_once './Services/Registration/classes/class.ilRegistrationSettings.php';
require_once "classes/class.ilUserAgreement.php";

class ilRegistrationGUI
{
	var $ctrl;
	var $tpl;

	function ilRegistrationGUI()
	{
		global $ilCtrl,$tpl,$lng;

		$this->tpl =& $tpl;

		$this->ctrl =& $ilCtrl;
		$this->ctrl->saveParameter($this,'lang');
		
		$this->lng =& $lng;
		$this->lng->loadLanguageModule('registration');

		$this->registration_settings = new ilRegistrationSettings();
	}

	function executeCommand()
	{
		global $ilErr, $tpl;
		
		if($this->registration_settings->getRegistrationType() == IL_REG_DISABLED)
		{
			$ilErr->raiseError($this->lng->txt('reg_disabled'),$ilErr->FATAL);
		}

		$next_class = $this->ctrl->getNextClass($this);
		$cmd = $this->ctrl->getCmd();

		switch($next_class)
		{
			default:
				if($cmd)
				{
					$this->$cmd();
				}
				else
				{
					$this->displayForm();
				}
				break;
		}
		$tpl->show();
		return true;
	}

	function login()
	{
		global $ilias,$lng,$ilLog;

		$ilLog->write("Entered login");

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.usr_registered.html");

		$this->tpl->setVariable("IMG_USER",
			ilUtil::getImagePath("icon_usr_b.gif"));
		$this->tpl->setVariable("TXT_PAGEHEADLINE", $lng->txt("registration"));
		$this->tpl->setVariable("TXT_WELCOME", $lng->txt("welcome").", ".$this->userObj->getTitle()."!");
		
		if ($this->registration_settings->getRegistrationType() == IL_REG_DIRECT and
			!$this->registration_settings->passwordGenerationEnabled())
		{
			$this->tpl->setCurrentBlock("activation");
			$this->tpl->setVariable("TXT_REGISTERED", $lng->txt("txt_registered"));
			$this->tpl->setVariable("FORMACTION", "login.php?cmd=force_login&target=".$_GET["target"]);
			$this->tpl->setVariable("TARGET","target=\"_parent\"");
			$this->tpl->setVariable("TXT_LOGIN", $lng->txt("login_to_ilias"));
			$this->tpl->setVariable("USERNAME",$this->userObj->getLogin());
			$this->tpl->setVariable("PASSWORD",$_POST["user"]['passwd']);
			$this->tpl->parseCurrentBlock();
		}
		else
		{
			$this->tpl->setVariable("TXT_REGISTERED", $lng->txt("txt_submitted"));
		}
	}		

	function displayForm()
	{
		global $ilias,$lng,$ObjDefinition;

		$this->tpl->addBlockFile("CONTENT", "content", "tpl.usr_registration.html");
		$this->tpl->addBlockFile("STATUSLINE", "statusline", "tpl.statusline.html");

		//load ILIAS settings
		$settings = $ilias->getAllSettings();


		$this->__showRoleSelection();

		$data = array();
		$data["fields"] = array();
		$data["fields"]["login"] = "";

		if (!$this->registration_settings->passwordGenerationEnabled())
		{
			$data["fields"]["passwd"] = "";
			$data["fields"]["passwd2"] = "";
		}
    
		$data["fields"]["title"] = "";
		$data["fields"]["gender"] = "";
		$data["fields"]["firstname"] = "";
		$data["fields"]["lastname"] = "";
		$data["fields"]["institution"] = "";
		$data["fields"]["department"] = "";
		$data["fields"]["street"] = "";
		$data["fields"]["city"] = "";
		$data["fields"]["zipcode"] = "";
		$data["fields"]["country"] = "";
		$data["fields"]["phone_office"] = "";
		$data["fields"]["phone_home"] = "";
		$data["fields"]["phone_mobile"] = "";
		$data["fields"]["fax"] = "";
		$data["fields"]["email"] = "";
		$data["fields"]["hobby"] = "";
		$data["fields"]["referral_comment"] = "";
		$data["fields"]["matriculation"] = "";

		// fill presets
		foreach ($data["fields"] as $key => $val)
		{
			$str = $lng->txt($key);
			if ($key == "title")
			{
				$str = $lng->txt("person_title");
			}
		
			if (!in_array($key, array("login", "passwd", "passwd2",
									  "firstname", "lastname", "gender")))
			{
				if ($settings["usr_settings_hide_".$key] != 1)
				{
					$this->tpl->setCurrentBlock($key."_section");
				}
				else
				{
					continue;
				}
			}

			// check to see if dynamically required
			if (isset($settings["require_" . $key]) && $settings["require_" . $key])
			{
				$str = $str . '<span class="asterisk">*</span>';
			}

			$this->tpl->setVariable("TXT_".strtoupper($key), $str);
			$this->tpl->setVariable(strtoupper($key), ilUtil::prepareFormOutput($_POST['user'][$key],true));
		
			if (!in_array($key, array("login", "passwd", "passwd2",
									  "firstname", "lastname", "gender")))
			{
				$this->tpl->parseCurrentBlock();
			}
		}


		if (!$this->registration_settings->passwordGenerationEnabled())
		{
			// text label for passwd2 is nonstandard
			$str = $lng->txt("retype_password");
			if (isset($settings["require_passwd2"]) && $settings["require_passwd2"])
			{
				$str = $str . '<span class="asterisk">*</span>';
			}

			$this->tpl->setVariable("TXT_PASSWD2", $str);
		}
		else
		{
			$this->tpl->setVariable("TXT_PASSWD_SELECT", $lng->txt("passwd"));
			$this->tpl->setVariable("TXT_PASSWD_VIA_MAIL", $lng->txt("reg_passwd_via_mail"));
		}

		$this->tpl->setVariable("FORMACTION",$this->ctrl->getFormAction($this));
		$this->tpl->setVariable("TXT_SAVE", $lng->txt("save"));
		$this->tpl->setVariable("TXT_REQUIRED_FIELDS", $lng->txt("required_field"));
		$this->tpl->setVariable("TXT_LOGIN_DATA", $lng->txt("login_data"));
		$this->tpl->setVariable("TXT_PERSONAL_DATA", $lng->txt("personal_data"));
		$this->tpl->setVariable("TXT_CONTACT_DATA", $lng->txt("contact_data"));
		$this->tpl->setVariable("TXT_SETTINGS", $lng->txt("settings"));
		$this->tpl->setVariable("TXT_OTHER", $lng->txt("user_profile_other"));
		$this->tpl->setVariable("TXT_LANGUAGE",$lng->txt("language"));
		$this->tpl->setVariable("TXT_GENDER_F",$lng->txt("gender_f"));
		$this->tpl->setVariable("TXT_GENDER_M",$lng->txt("gender_m"));
		$this->tpl->setVariable("TXT_OK",$lng->txt("ok"));
		$this->tpl->setVariable("TXT_CHOOSE_LANGUAGE", $lng->txt("choose_language"));
		$this->tpl->setVariable("REG_LANG_FORMACTION",
			$this->ctrl->getFormAction($this));

		// language selection
		$languages = $lng->getInstalledLanguages();
	
		$count = (int) round(count($languages) / 2);
		$num = 1;
		
		foreach ($languages as $lang_key)
		{
			/*
			 if ($num === $count)
			 {
			 $this->tpl->touchBlock("lng_new_row");
			 }
			*/

			$this->tpl->setCurrentBlock("languages");
			$this->tpl->setVariable("LINK_LANG",$this->ctrl->getLinkTarget($this,'displayForm'));
			$this->tpl->setVariable("LANG_NAME",
							  ilLanguage::_lookupEntry($lang_key, "meta", "meta_l_".$lang_key));
			$this->tpl->setVariable("LANG_ICON", $lang_key);
			$this->tpl->setVariable("BORDER", 0);
			$this->tpl->setVariable("VSPACE", 0);
			$this->tpl->parseCurrentBlock();

			$num++;
		}
		
		// preselect previous chosen language otherwise default language
		$selected_lang = (isset($_POST["user"]["language"])) ? 
			$_POST["user"]["language"] : $lng->lang_key;

		foreach ($languages as $lang_key)
		{
			$this->tpl->setCurrentBlock("language_selection");
			$this->tpl->setVariable("LANG", $lng->txt("lang_".$lang_key));
			$this->tpl->setVariable("LANGSHORT", $lang_key);

			if ($selected_lang == $lang_key)
			{
				$this->tpl->setVariable("SELECTED_LANG", "selected=\"selected\"");
			}

			$this->tpl->parseCurrentBlock();
		} // END language selection

		// FILL SAVED VALUES IN CASE OF ERROR
		if (isset($_POST["user"]))
		{
			// gender selection
			$gender = strtoupper($_POST["user"]["gender"]);

			if (!empty($gender))
			{
				$this->tpl->setVariable("BTN_GENDER_".$gender,"checked=\"checked\"");
			}
		}

		$this->tpl->setVariable("IMG_USER",
			ilUtil::getImagePath("icon_usr_b.gif"));
		$this->tpl->setVariable("TXT_PAGEHEADLINE", $lng->txt("registration"));
		$this->tpl->setVariable("TXT_PAGETITLE", "ILIAS3 - ".$lng->txt("registration"));
		$this->tpl->setVariable("TXT_REGISTER_INFO", $lng->txt("register_info"));
		$this->tpl->setVariable("AGREEMENT", ilUserAgreement::_getText());
		$this->tpl->setVariable("ACCEPT_CHECKBOX", ilUtil::formCheckbox(0, "status", "accepted"));
		$this->tpl->setVariable("ACCEPT_AGREEMENT", $lng->txt("accept_usr_agreement") . '<span class="asterisk">*</span>');

		$this->showUserDefinedFields();
	}

	function showUserDefinedFields()
	{
		include_once './classes/class.ilUserDefinedFields.php';
		$user_defined_fields = new ilUserDefinedFields();

		#$user_defined_data = $ilUser->getUserDefinedData();
		foreach($user_defined_fields->getVisibleDefinitions() as $field_id => $definition)
		{
			if($definition['field_type'] == UDF_TYPE_TEXT)
			{
				$old = isset($_POST["udf"][$field_id]) ?
					$_POST["udf"][$field_id] : '';


				$this->tpl->setCurrentBlock("field_text");
				$this->tpl->setVariable("FIELD_NAME",'udf['.$definition['field_id'].']');
				$this->tpl->setVariable("FIELD_VALUE",ilUtil::prepareFormOutput($old));
				if(!$definition['changeable'])
				{
					$this->tpl->setVariable("DISABLED_FIELD",'disabled=\"disabled\"');
				}
				$this->tpl->parseCurrentBlock();
			}
			else
			{
				$this->tpl->setCurrentBlock("field_select");
				$this->tpl->setVariable("SELECT_BOX",ilUtil::formSelect($_POST['udf']["$definition[field_id]"],
																  'udf['.$definition['field_id'].']',
																  $user_defined_fields->fieldValuesToSelectArray(
																	  $definition['field_values']),
																  false,
																  true));
				$this->tpl->parseCurrentBlock();
			}
			$this->tpl->setCurrentBlock("user_defined");

			if($definition['required'])
			{
				$name = $definition['field_name']."<span class=\"asterisk\">*</span>";
			}
			else
			{
				$name = $definition['field_name'];
			}
			$this->tpl->setVariable("TXT_FIELD_NAME",$name);
			$this->tpl->parseCurrentBlock();
		}
		return true;
	}


	function checkUserDefinedRequiredFields()
	{
		include_once './classes/class.ilUserDefinedFields.php';
		$user_defined_fields = new ilUserDefinedFields();

		foreach($user_defined_fields->getVisibleDefinitions() as $field_id => $definition)
		{
			if($definition['required'] and !strlen($_POST['udf'][$field_id]))
			{
				return false;
			}
		}
		return true;
	}


	function saveForm()
	{		
		global $ilias, $lng, $rbacadmin, $ilDB, $ilErr;

		//load ILIAS settings
		$settings = $ilias->getAllSettings();

		//check, whether user-agreement has been accepted
		if ($_POST["status"] != "accepted")
		{
			sendInfo($lng->txt("force_accept_usr_agreement"),true);
			$this->displayForm();
			return false;
		}

		// check dynamically required fields
		foreach ($settings as $key => $val)
		{
			if (substr($key,0,8) == "require_")
			{
				if ($this->registration_settings->passwordGenerationEnabled() and 
					($key == "require_passwd" or $key == "require_passwd2"))
				{
					continue;
				}
				if($key == 'require_default_role')
				{
					continue;
				}
            
				$require_keys[] = substr($key,8);
			}
		}

		foreach ($require_keys as $key => $val)
		{
			if (isset($settings["require_" . $val]) && $settings["require_" . $val])
			{
				if (empty($_POST["user"][$val]))
				{
					sendInfo($lng->txt("fill_out_all_required_fields") . ": " . $lng->txt($val),true);
					$this->displayForm();
					return false;
				}
			}
		}

		if(!$this->checkUserDefinedRequiredFields())
		{
			sendInfo($lng->txt("fill_out_all_required_fields"),true);
			$this->displayForm();
			return false;
		}
		
		// validate username
		if (!ilUtil::isLogin($_POST["user"]["login"]))
		{
			sendInfo($lng->txt("login_invalid"),true);
			$this->displayForm();
			return false;
		}

		// check loginname
		if (loginExists($_POST["user"]["login"]))
		{
			sendInfo($lng->txt("login_exists"),true);
			$this->displayForm();
			return false;
		}

		if (!$this->registration_settings->passwordGenerationEnabled())
		{
			// check passwords
			if ($_POST["user"]["passwd"] != $_POST["user"]["passwd2"])
			{
				sendInfo($lng->txt("passwd_not_match"),true);
				$this->displayForm();
				return false;
			}

			// validate password
			if (!ilUtil::isPassword($_POST["user"]["passwd"]))
			{
				sendInfo($lng->txt("passwd_invalid"),true);
				$this->displayForm();
				return false;
			}
		}
		else
		{    
			$passwd = ilUtil::generatePasswords(1);
			$_POST["user"]["passwd"] = $passwd[0];
		}
		// The password type is not passed in the post data. Therefore we
		// append it here manually.
		require_once "classes/class.ilObjUser.php";
		$_POST["user"]["passwd_type"] = IL_PASSWD_PLAIN;

		// validate email
		if (!ilUtil::is_email($_POST["user"]["email"]))
		{
			sendInfo($lng->txt("email_not_valid"),true);
			$this->displayForm();
			return false;
		}


		// Do some Radius checks
		$this->__validateRole();

		// TODO: check if login or passwd already exists
		// TODO: check length of login and passwd

		// checks passed. save user

		$this->userObj = new ilObjUser();
		$this->userObj->assignData($_POST["user"]);
		$this->userObj->setTitle($this->userObj->getFullname());
		$this->userObj->setDescription($this->userObj->getEmail());

		// Time limit
		$this->userObj->setTimeLimitOwner(USER_FOLDER_ID);
		$this->userObj->setTimeLimitUnlimited(1);
		$this->userObj->setTimeLimitFrom(time());
		$this->userObj->setTimeLimitUntil(time());

		$this->userObj->setUserDefinedData($_POST['udf']);
		$this->userObj->create();

		if($this->registration_settings->getRegistrationType() == IL_REG_DIRECT)
		{
			$this->userObj->setActive(1);
		}
		else
		{
			$this->userObj->setActive(0,0);
		}

		$this->userObj->updateOwner();

		//insert user data in table user_data
		$this->userObj->saveAsNew();
	
		// store acceptance of user agreement
		$this->userObj->writeAccepted();

		// setup user preferences
		$this->userObj->setLanguage($_POST["user"]["language"]);
		$this->userObj->setPref("hits_per_page", $ilias->getSetting("hits_per_page"));
		$this->userObj->setPref("show_users_online", $ilias->getSetting("show_users_online"));
		$this->userObj->writePrefs();

		// Assign role (depends on settings in administration)
		$this->__assignRole();

		// Distribute mails
		$this->__distributeMails();

		$this->login();
		return true;
	}


	function __validateRole()
	{
		global $ilDB,$ilias,$ilErr;

		// validate role
		include_once("classes/class.ilObjRole.php");
		if ($this->registration_settings->roleSelectionEnabled() and 
			!ilObjRole::_lookupAllowRegister($_POST["user"]["default_role"]))
		{
			$ilias->raiseError("Invalid role selection in registration: ".
							   ilObject::_lookupTitle($_POST["user"]["default_role"])." [".$_POST["user"]["default_role"]."]".
							   ", IP: ".$_SERVER["REMOTE_ADDR"],$ilias->error_obj->FATAL);
		}

		// get auth mode of role
		$auth_mode = ilObjRole::_getAuthMode($_POST["user"]["default_role"]);
		$_POST["user"]['auth_mode'] = $auth_mode;
	
		// validate authentication, if mode != LOCAL
		// to do: if auth is taken out of ilias class, this should be
		// implemented better
		// to do: also needed for ldap
		if ($auth_mode == "radius")
		{
			$_POST['username'] = $_POST["user"]["login"];
			$_POST['password'] = $_POST["user"]["passwd"];

			include_once('classes/class.ilRADIUSAuthentication.php');
			$radius_servers = ilRADIUSAuthentication::_getServers($ilDB);
			$settings = $ilias->getAllSettings();
		
			foreach ($radius_servers as $radius_server)
			{
				$rad_params['servers'][] = array($radius_server,$settings["radius_port"],$settings["radius_shared_secret"]);
			}
			$auth = new Auth("RADIUS", $rad_params,"",false);
			$auth->start();
			$err = $ilErr->getLastError();
			if (!$auth->getAuth())
			{
				$add = (!is_object($err))
					? ""
					: "<br>".$err->getMessage();
				$ilias->raiseError($lng->txt("could_not_verify_account").
								   $add, $ilErr->MESSAGE);
			}
		}
		return true;
	}

	function __assignRole()
	{
		global $rbacadmin;

		// Assign chosen role
		if($this->registration_settings->roleSelectionEnabled())
		{
			return $rbacadmin->assignUser((int) $_POST['user']['default_role'],
										  $this->userObj->getId(),true);
		}
		
		// Assign by email
		include_once 'Services/Registration/classes/class.ilRegistrationEmailRoleAssignments.php';

		$registration_role_assignments = new ilRegistrationRoleAssignments();

		return $rbacadmin->assignUser((int) $registration_role_assignments->getRoleByEmail($this->userObj->getEmail()),
									  $this->userObj->getId(),
									  true);
			
	}

	function __showRoleSelection()
	{
		if(!$this->registration_settings->roleSelectionEnabled())
		{
			return true;
		}
		
		// TODO put query in a function
		include_once("classes/class.ilObjRole.php");
		$reg_roles = ilObjRole::_lookupRegisterAllowed();

		$rol = array();
		foreach ($reg_roles as $role)
		{
			$rol[$role["id"]] = $role["title"];
		}

		$this->tpl->setCurrentBlock("role");
		$this->tpl->setVariable("TXT_DEFAULT_ROLE",$this->lng->txt('default_role'));
		$this->tpl->setVariable("DEFAULT_ROLE",ilUtil::formSelect($_POST["user"]["default_role"],
																  "user[default_role]",
																  $rol,false,true));
		$this->tpl->parseCurrentBlock();

		return true;
	}

	function __distributeMails()
	{
		global $ilias;
		
		include_once 'classes/class.ilLanguage.php';
		include_once 'classes/class.ilObjUser.php';
        include_once "classes/class.ilFormatMail.php";


		$settings = $ilias->getAllSettings();
		
		// Always send mail to approvers
        #if($this->registration_settings->getRegistrationType() == IL_REG_APPROVE)
		{
			// Send mail to approvers
			foreach($this->registration_settings->getApproveRecipients() as $recipient)
			{
				$lng = new ilLanguage(ilObjUser::_lookupLanguage($recipient));
				$lng->loadLanguageModule('registration');

				$umail = new ilFormatMail(6); // Send as system administrator
				$umail->enableSoap(false);

				$subject = $lng->txt("client_id") . " " . $ilias->client_id . ": " . $lng->txt("usr_new");

				// build body
				$body = $lng->txt('reg_mail_new_user_body')."\n\n";
				$body .= $lng->txt('reg_mail_body_profile')."\n\n";

				$body .= $this->userObj->getProfileAsString($lng);
				$umail->sendMail(ilObjUser::_lookupLogin($recipient),"","",$subject,$body,array(),array("normal"));
			}
		}
		// Send mail to new user

		include_once "classes/class.ilMimeMail.php";

		$mmail = new ilMimeMail();
		$mmail->autoCheck(false);
		$mmail->From($settings["admin_email"]);
		$mmail->To($this->userObj->getEmail());

		// mail subject
		$subject = $this->lng->txt("reg_mail_subject");

		// mail body
		$body = $this->lng->txt("reg_mail_body_salutation")." ".$this->userObj->getFullname().",\n\n".
			$this->lng->txt("reg_mail_body_text1")."\n\n".
			$this->lng->txt("reg_mail_body_text2")."\n".
			ILIAS_HTTP_PATH."/login.php?client_id=".$ilias->client_id."\n".
			$this->lng->txt("login").": ".$this->userObj->getLogin()."\n".
			$this->lng->txt("passwd").": ".$_POST["user"]["passwd"]."\n\n";

		// Info about necessary approvement
		if($this->registration_settings->getRegistrationType() == IL_REG_APPROVE)
		{
			$body .= ($this->lng->txt('reg_mail_body_pwd_generation')."\n\n");
		}
		$body .= ($this->lng->txt("reg_mail_body_text3")."\n\r");
		$body .= $this->userObj->getProfileAsString($this->lng);

		$mmail->Subject($subject);
		$mmail->Body($body);
		$mmail->Send();
	}
}
?>