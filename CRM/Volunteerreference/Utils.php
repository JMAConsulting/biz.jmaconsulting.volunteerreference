<?php

class CRM_Volunteerreference_Utils {

  public static function createWPUser($contactID) {
    $contact = civicrm_api3('Contact', 'getsingle', ['id' => $contactID]);
    $ufID = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_UFMatch', $contactID, 'uf_id', 'contact_id');
    $ufID = $ufID ?:
      (!empty($contact['email'])) ?
        CRM_Core_DAO::singleValueQuery("SELECT MAX(uf_id) FROM civicrm_uf_match WHERE uf_name =  '" . $contact['email'] . "' ") :
          NULL;

    if (!$ufID) {
      $cmsName = strtolower($contact['first_name'] . '.' . $contact['last_name'] . '.' . $contact['id']);
      $params = [
        'contactID' => $contact['id'],
        'cms_pass' => 'changeme',
        'cms_name' => $cmsName,
        'email' => $contact['email'],
      ];
      $ufID = CRM_Core_BAO_CMSUser::create($params, 'email');
    }
    $u = new WP_User($ufID);
    $u->set_role('volunteer');
  }

  public static function sendMail($params, $sendTemplateParams) {
    $sendTemplateParams = array_merge(
      $sendTemplateParams,
      array(
        'toName' => $params['ref_name'],
        'toEmail' => $params['ref_email'],
      )
    );
    CRM_Core_BAO_MessageTemplate::sendTemplate($sendTemplateParams);
  }



}
