<?php

require_once 'volunteerreference.civix.php';
require_once 'volunteerreference.constant.php';

use CRM_Volunteerreference_Utils as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function volunteerreference_civicrm_config(&$config) {
  _volunteerreference_civix_civicrm_config($config);
}

function volunteerreference_civicrm_pageRun(&$page) {
  $pageName = $page->getVar('_name');
  if ($pageName == 'CRM_Contact_Page_View_Summary') {
    $customData = $page->get_template_vars('viewCustomData');
    foreach ($customData as $cusotmGroupId => $custom) {
      if ($cusotmGroupId === 5) {
        foreach ($custom as $recordId => $customDetails) {
          if (!empty($recordId)) {
            foreach ($customDetails['fields'] as $fieldId => $customField) {
              if (!empty($customField['field_value']) && in_array($fieldId, [str_replace('custom_', '', REF_EMAIL1), str_replace('custom_', '', REF_EMAIL2), str_replace('custom_', '', REF_EMAIL3)])) {
                $fieldValue = $customField['field_value'];
                $url = 'civicrm/resend/reference';
                $url_params = 'reset=1&cid=' . $page->get_template_vars('contactId');
                if ($fieldId == str_replace('custom_', '', REF_EMAIL1)) {
                  $url_params .= '&ref=1';
                }
                if ($fieldId == str_replace('custom_', '', REF_EMAIL2)) {
                  $url_params .= '&ref=2';
                }
                if ($fieldId == str_replace('custom_', '', REF_EMAIL3)) {
                  $url_params .= '&ref=3';
                }
                $fieldValue .= ' <a href="' . CRM_Utils_System::url($url, $url_params) . '" class="button" style="float:right;">Resend Reference Request</a>';
                $customData[$cusotmGroupId][$recordId]['fields'][$fieldId]['field_value'] = $fieldValue;
              }
            }
          }
        }
      }
      $page->assign('viewCustomData', $customData);
    }
  }
}

function volunteerreference_civicrm_validateForm($formName, &$fields, &$files, &$form, &$errors) {
  if ($formName == 'CRM_Volunteer_Form_VolunteerSignUp') {
    foreach ([REF_EMAIL1, REF_EMAIL2, REF_EMAIL3] as $key) {
      if (!empty($fields[$key]) && !CRM_Utils_Rule::email($fields[$key])) {
        $errors[$key] = ts('Please enter a valid email.');
      }
    }
  }
}

function volunteerreference_civicrm_preProcess($formName, &$form) {
  if ($formName == 'CRM_Volunteer_Form_VolunteerSignUp') {
    $needs = $form->getVar('_needs');
    foreach ($needs as $need) {
      if ($need['role_id'] == 7) {
        $form->setVar('_primary_volunteer_profile_ids', [28]);
      }
    }
  }
}

function volunteerreference_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Volunteer_Form_VolunteerSignUp') {
    if ($contactID = $form->getVar('_primary_volunteer_id')) {
      $profileIDs = $form->getVar('_primary_volunteer_profile_ids');
      if (in_array(28, $profileIDs)) {
        civicrm_api3('Contact', 'create', ['id' => $contactID, 'custom_71' => 2]);
        E::createWPUser($contactID, TRUE);
        CRM_Utils_System::redirect('https://girlsinscience.ca/thankyou-current-volunteer');
        return;
      }

      $values = $form->controller->exportValues();

      $refEmail1 = $values[REF_EMAIL1];
      $refName1 = $values[REF_NAME1];
      $refEmail2 = $values[REF_EMAIL2];
      $refName2 = $values[REF_NAME2];
      $refEmail3 = $values[REF_NAME3];
      $refName3 = $values[REF_NAME3];

      list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

      $params = [
        'volunteer_cid' => $contactID,
        'project_ids' => array_keys($form->getVar('_projects')),
      ];
      $tplParams = _prepareTplParams($params);

      $sendTemplateParams = array(
        'contactId' => $contactID,
        'from' => "$domainEmailName <" . $domainEmailAddress . ">",
        'messageTemplateID' => 70,
        'isTest' => FALSE,
        'tplParams' => array("volunteer" => $tplParams),
      );

      if (!empty($refName1) && !empty($refEmail1)) {
        $params = [
          'ref_email' => $refEmail1,
          'ref_name' => $refName1,
          'volunteer_cid' => $contactID,
        ];
        $sendTemplateParams['tplParams']['volunteer']['url'] = CRM_Utils_System::url('civicrm/reference/request', sprintf("vid=%d&customid=%s", $params['volunteer_cid'], str_replace('custom_', '', REF_NAME1)), TRUE, NULL, TRUE);

        E::sendMail($params, $sendTemplateParams);
      }

      if (!empty($refName2) && !empty($refEmail2)) {
        $params = [
          'ref_email' => $refEmail2,
          'ref_name' => $refName2,
          'volunteer_cid' => $contactID,
        ];
        $sendTemplateParams['tplParams']['volunteer']['url'] = CRM_Utils_System::url('civicrm/reference/request', sprintf("vid=%d&customid=%s", $params['volunteer_cid'], str_replace('custom_', '', REF_NAME2)), TRUE, NULL, TRUE);
        E::sendMail($params, $sendTemplateParams);
      }

      if (!empty($refName3) && !empty($refEmail3)) {
        $params = [
          'ref_email' => $refEmail3,
          'ref_name' => $refName3,
          'volunteer_cid' => $contactID,
        ];
        $sendTemplateParams['tplParams']['volunteer']['url'] = CRM_Utils_System::url('civicrm/reference/request', sprintf("vid=%d&customid=%s", $params['volunteer_cid'], str_replace('custom_', '', REF_NAME3)), TRUE, NULL, TRUE);
        E::sendMail($params, $sendTemplateParams);
      }

      E::createWPUser($contactID);
      civicrm_api3('Contact', 'create', ['id' => $contactID, 'custom_71' => 1]);

      CRM_Utils_System::redirect('https://girlsinscience.ca/thankyou-volunteer');
    }
  }
}

function volunteerreference_civicrm_alterMailParams(&$params, $context) {
  if ($params['groupName'] == 'msg_tpl_workflow_volunteer') {
    $cid = $params['contactId'];
    $params['cc'] = E::sendChapterEmail($cid);
  }
}


function _prepareTplParams($params) {
  $tplParams = [];
  foreach ($params['project_ids'] as $projectId) {
    $result = civicrm_api3('VolunteerProject', 'get', array(
      'return' => "title,description",
      'sequential' => 1,
      'api.VolunteerProjectContact.get' => array(
        'sequential' => 1,
        'contact_id' => $params['volunteer_cid'],
        'return' => ["contact_id","relationship_type_id"],
      ),
      'id' => $projectId
    ));

    $contact = civicrm_api3('Contact', 'getsingle', ['id' => $params['volunteer_cid']]);

    if ($result['count'] > 0) {
      $tplParams['project'] = $result['values'][0]['title'];
      $tplParams['display_name'] = $contact['display_name'];
      $tplParams['role'] = $result['values'][0]['api.VolunteerProjectContact.get']['values'][0]['relationship_type_label'];
    }
  }

  return $tplParams;
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function volunteerreference_civicrm_xmlMenu(&$files) {
  _volunteerreference_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function volunteerreference_civicrm_install() {
  _volunteerreference_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function volunteerreference_civicrm_postInstall() {
  _volunteerreference_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function volunteerreference_civicrm_uninstall() {
  _volunteerreference_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function volunteerreference_civicrm_enable() {
  _volunteerreference_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function volunteerreference_civicrm_disable() {
  _volunteerreference_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function volunteerreference_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _volunteerreference_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function volunteerreference_civicrm_managed(&$entities) {
  _volunteerreference_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function volunteerreference_civicrm_caseTypes(&$caseTypes) {
  _volunteerreference_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function volunteerreference_civicrm_angularModules(&$angularModules) {
  _volunteerreference_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function volunteerreference_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _volunteerreference_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function volunteerreference_civicrm_entityTypes(&$entityTypes) {
  _volunteerreference_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function volunteerreference_civicrm_preProcess($formName, &$form) {

} // */

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 *
function volunteerreference_civicrm_navigationMenu(&$menu) {
  _volunteerreference_civix_insert_navigation_menu($menu, 'Mailings', array(
    'label' => E::ts('New subliminal message'),
    'name' => 'mailing_subliminal_message',
    'url' => 'civicrm/mailing/subliminal',
    'permission' => 'access CiviMail',
    'operator' => 'OR',
    'separator' => 0,
  ));
  _volunteerreference_civix_navigationMenu($menu);
} // */
