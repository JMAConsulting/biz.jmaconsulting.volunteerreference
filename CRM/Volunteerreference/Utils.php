<?php

class CRM_Volunteerreference_Utils {

  public static function createWPUser($contactID, $sendResetLink = FALSE) {
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
    if ($sendResetLink) {
      self::sendResetLink($u);
    }
    $u->set_role('volunteer');
  }

  public static function sendResetLink($user) {
    $firstname = $user->first_name;
    $email = $user->user_email;
    $adt_rp_key = get_password_reset_key( $user );
    $user_login = $user->user_login;
    $rp_link = '<a href="' . wp_login_url()."/resetpass/?key=$adt_rp_key&login=" . rawurlencode($user_login) . '">' . wp_login_url()."/resetpass/?key=$adt_rp_key&login=" . rawurlencode($user_login) . '</a>';

    $message = "Hi ".$firstname.",<br>";
    $message .= "An account has been created on ".get_bloginfo( 'name' )." for email address ".$email."<br>";
    $message .= "Click here to set the password for your account: <br>";
    $message .= $rp_link.'<br>';

    //deze functie moet je zelf nog toevoegen.
    $subject = __("Your account on ".get_bloginfo( 'name'));
    $headers = array();

    add_filter( 'wp_mail_content_type', function( $content_type ) {return 'text/html';});
    $headers[] = 'From: Your company name <cagis@uwo.ca>'."\r\n";
    wp_mail( $email, $subject, $message, $headers);

    // Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
    remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
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
