<?php

use CRM_Volunteerreference_ExtensionUtil as E;
use CRM_Volunteerreference_Utils as Utils;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Volunteerreference_Form_ResendReference extends CRM_Core_Form {
  public function buildQuickForm() {

    // add form elements
    $contact_id = CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $this->addElement('text', 'contact_id', 'Contact ID')->freeze();
    $this->addElement('text', 'custom_field_id', 'custom_field_id')->freeze();
    $this->addElement('text', 'referee_name', 'referee_name')->freeze();
    $this->addElement('text', 'referee_email', 'referee_email')->freeze();
    $ref = CRM_Utils_Request::retrieve('ref', 'Positive', $this);
    $defaults = ['contact_id' => $contact_id];
    switch ($ref) {
      case 1:
        $referenceDetails = civicrm_api3('Contact', 'getsingle', ['id' => $contact_id, 'return' => [REF_NAME1, REF_EMAIL1]]);
        $this->assign('Referee', $referenceDetails[REF_NAME1] . ' &lt;' . $referenceDetails[REF_EMAIL1] . '&gt;');
        $defaults['custom_field_id'] = str_replace('custom_', '', REF_NAME1);
        $defaults['referee_name'] = $referenceDetails[REF_NAME1];
        $defaults['referee_email'] = $referenceDetails[REF_EMAIL1];
        break;

      case 2:
        $referenceDetails = civicrm_api3('Contact', 'getsingle', ['id' => $contact_id, 'return' => [REF_NAME2, REF_EMAIL2]]);
        $this->assign('Referee', $referenceDetails[REF_NAME2] . ' &lt;' . $referenceDetails[REF_EMAIL2] . '&gt;');
        $defaults['custom_field_id'] = str_replace('custom_', '', REF_NAME2);
        $defaults['referee_name'] = $referenceDetails[REF_NAME2];
        $defaults['referee_email'] = $referenceDetails[REF_EMAIL2];
        break;

      case 3:
        $referenceDetails = civicrm_api3('Contact', 'getsingle', ['id' => $contact_id, 'return' => [REF_NAME3, REF_EMAIL3]]);
        $this->assign('Referee', $referenceDetails[REF_NAME3] . ' &lt;' . $referenceDetails[REF_EMAIL3] . '&gt;');
        $defaults['custom_field_id'] = str_replace('custom_', '', REF_NAME3);
        $defaults['referee_name'] = $referenceDetails[REF_NAME3];
        $defaults['referee_email'] = $referenceDetails[REF_EMAIL3];
        break;

      default:
        CRM_Core_Error::statusBounce(E::ts('Wrong Reference id value passed in'), CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $contact_id));

    }
    $this->setDefaults($defaults);
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    parent::buildQuickForm();
  }

  public function postProcess() {
    $values = $this->exportValues();
    $contactID = $values['contact_id'];
    list($displayName, $email) = CRM_Contact_BAO_Contact_Location::getEmailDetails($contactID);
    list($domainEmailName, $domainEmailAddress) = CRM_Core_BAO_Domain::getNameAndEmail();

    $params = [
      'volunteer_cid' => $contactID,
    ];
    $tplParams = self::prepareTplParams($params);
    $sendTemplateParams = array(
      'contactId' => $contactID,
      'from' => "$domainEmailName <" . $domainEmailAddress . ">",
      'messageTemplateID' => 70,
      'isTest' => FALSE,
      'tplParams' => array("volunteer" => $tplParams),
    );

    $params = [
      'ref_email' => $values['referee_email'],
      'ref_name' => $values['referee_name'],
      'volunteer_cid' => $contactID,
    ];
    $sendTemplateParams['tplParams']['volunteer']['url'] = CRM_Utils_System::url('civicrm/reference/request', sprintf("vid=%d&customid=%s", $params['volunteer_cid'], $values['custom_field_id']), TRUE, NULL, TRUE, TRUE);

    Utils::sendMail($params, $sendTemplateParams);
    CRM_Core_Session::setStatus(E::ts('Email has been sent to %1', [1 => $values['referee_name']]), E::ts('Email Sent'), 'success');
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/contact/view', 'reset=1&cid=' . $contactID));
  }

  public static function prepareTplParams($params) {
    $tplParams = [];
    $contact = civicrm_api3('Contact', 'getsingle', ['id' => $params['volunteer_cid']]);
    $tplParams['display_name'] = $contact['display_name'];
    return $tplParams;
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
