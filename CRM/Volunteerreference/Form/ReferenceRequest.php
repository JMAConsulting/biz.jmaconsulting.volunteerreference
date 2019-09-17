<?php

use CRM_Volunteerreference_ExtensionUtil as E;

require_once __DIR__ . '/../../../volunteerreference.constant.php';

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Volunteerreference_Form_ReferenceRequest extends CRM_Core_Form {
  public $_contactID = NULL;
  public $_referenceName = NULL;

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Reference Request Form'));
    $this->_contactID = CRM_Utils_Request::retrieve('vid', 'Positive', $this, FALSE);
    $customID = CRM_Utils_Request::retrieve('customid', 'Positive', $this, FALSE);
    if ($this->_contactID) {
      $contact = civicrm_api3('Contact', 'getsingle', [
        'id' => $this->_contactID,
        'return' => ['custom_' . $customID, 'display_name'],
      ]);
      $this->assign('volunteer', $contact['display_name']);
      $this->assign('reference', $contact['custom_' . $customID]);
      $this->_referenceName = $contact['custom_' . $customID];
    }
    $this->buildCustom(REFERENCE_PROFILE, 'reference');

    $this->addFormRule(array('CRM_Volunteerreference_Form_ReferenceRequest', 'formRule'), $this);
    parent::buildQuickForm();
  }

  public function formRule($fields, $files, $self) {
    return TRUE;
  }

  public function postProcess() {
    parent::postProcess();
    $fields = CRM_Core_BAO_UFGroup::getFields(REFERENCE_PROFILE, FALSE, CRM_Core_Action::VIEW);
    $values = $this->controller->exportValues($this->_name);
    $values['skip_greeting_processing'] = TRUE;
    $contactID = CRM_Contact_BAO_Contact::createProfileContact($values, $fields, $this->_contactID, NULL, REFERENCE_PROFILE);
    civicrm_api3('Activity', 'create', [
      'source_contact_id' => $this->_contactID,
      'target_contact_id' => $this->_contactID,
      'subject' => 'Refernce form submitted by ' . $this->_referenceName,
      'activity_type_id' => 56,
      'status_id' => 'Completed',
    ]);
  }

  public function buildCustom($id, $name, $viewOnly = FALSE, $ignoreContact = FALSE) {
    if ($id) {
      $button = substr($this->controller->getButtonName(), -4);
      $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
      $session = CRM_Core_Session::singleton();
      $contactID = $session->get('userID');

      // we don't allow conflicting fields to be
      // configured via profile
      $fieldsToIgnore = array(
        'participant_fee_amount' => 1,
        'participant_fee_level' => 1,
      );
      if ($contactID && !$ignoreContact) {
        //FIX CRM-9653
        if (is_array($id)) {
          $fields = array();
          foreach ($id as $profileID) {
            $field = CRM_Core_BAO_UFGroup::getFields($profileID, FALSE, CRM_Core_Action::ADD,
              NULL, NULL, FALSE, NULL,
              FALSE, NULL, CRM_Core_Permission::CREATE,
              'field_name', TRUE
            );
            $fields = array_merge($fields, $field);
          }
        }
        else {
          if (CRM_Core_BAO_UFGroup::filterUFGroups($id, $contactID)) {
            $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD,
              NULL, NULL, FALSE, NULL,
              FALSE, NULL, CRM_Core_Permission::CREATE,
              'field_name', TRUE
            );
          }
        }
      }
      else {
        $fields = CRM_Core_BAO_UFGroup::getFields($id, FALSE, CRM_Core_Action::ADD,
          NULL, NULL, FALSE, NULL,
          FALSE, NULL, CRM_Core_Permission::CREATE,
          'field_name', TRUE
        );
      }

      if (array_intersect_key($fields, $fieldsToIgnore)) {
        $fields = array_diff_key($fields, $fieldsToIgnore);
        CRM_Core_Session::setStatus(ts('Some of the profile fields cannot be configured for this page.'));
      }
      $addCaptcha = FALSE;

      if (!empty($this->_fields)) {
        $fields = @array_diff_assoc($fields, $this->_fields);
      }

      $this->assign($name, $fields);
      if (is_array($fields)) {
        foreach ($fields as $key => $field) {
          if ($viewOnly &&
            isset($field['data_type']) &&
            $field['data_type'] == 'File' || ($viewOnly && $field['name'] == 'image_URL')
          ) {
            // ignore file upload fields
            //continue;
          }
          //make the field optional if primary participant
          //have been skip the additional participant.
          if ($button == 'skip') {
            $field['is_required'] = FALSE;
          }
          // CRM-11316 Is ReCAPTCHA enabled for this profile AND is this an anonymous visitor
          elseif ($field['add_captcha'] && !$contactID) {
            // only add captcha for first page
            $addCaptcha = TRUE;
          }
          list($prefixName, $index) = CRM_Utils_System::explode('-', $key, 2);
          if ($viewOnly) {
            $field['is_view'] = $viewOnly;
            if ($field['data_type'] == 'File' || $field['name'] == 'image_URL') {
              $this->add('text', $field['name'], $field['title'], []);
              $this->freeze($field['name']);
              continue;
            }
          }
          CRM_Core_BAO_UFGroup::buildProfile($this, $field, CRM_Profile_Form::MODE_CREATE, $contactID, TRUE);

          $this->_fields[$key] = $field;
        }
      }

      if ($addCaptcha && !$viewOnly) {
        $captcha = CRM_Utils_ReCAPTCHA::singleton();
        $captcha->add($this);
        $this->assign('isCaptcha', TRUE);
      }
    }
  }

}
