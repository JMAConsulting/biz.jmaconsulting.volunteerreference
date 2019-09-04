<?php

require_once 'volunteerreference.civix.php';
require_once 'volunteerreference.constant.php';

use CRM_Volunteerreference_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function volunteerreference_civicrm_config(&$config) {
  _volunteerreference_civix_civicrm_config($config);
}

function volunteerreference_civicrm_postProcess($formName, &$form) {
  if ($formName == 'CRM_Volunteer_Form_VolunteerSignUp') {
    if ($contactID = $form->getVar('_primary_volunteer_id')) {
      $values = civicrm_api3('Contact', 'get', [
        'id' => $contactID,
        'return.' . REF_EMAIL => 1,
        'return.' . REF_NAME => 1,
      ])['values'][$contactID];

      $refEmail = $values[REF_EMAIL];
      $refName = $values[REF_NAME];

      list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
      list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

      if ($refEmail) {
        $params = [
          'ref_email' => $refEmail
          'ref_name' => $refName,
          'volunteer_cid' => $contactID,
        ];
        $tplParams = _prepareTplParams($params);
        $sendTemplateParams = array(
          'contactId' => $contactID,
          'from' => "$domainEmailName <" . $domainEmailAddress . ">",
          'groupName' => 'msg_tpl_workflow_volunteer',
          'isTest' => FALSE,
          'toName' => $refName,
          'toEmail' => $refEmail,
          'tplParams' => array("volunteer_projects" => $tplParams),
          'valueName' => 'volunteer_reference_notification',
        );

        CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
      }
    }
  }
}

function _prepareTplParams($params) {

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
