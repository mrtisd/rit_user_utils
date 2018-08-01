<?php
/**
 * @file
 * Contains \Drupal\example\Form\RITForm.
 */

namespace Drupal\rit_user_utils\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;

/**
 * Implements an example form.
 */
class RITForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'rit_user_utils_form';
  }

  /**
   * {@inheritdoc}
  */
   public function buildForm(array $form, FormStateInterface $form_state) {
	
   	$num = 0;
   	
    $enable_auth = \Drupal::state()->get('rit_user_utils_auth_enable');
    $enable_auth_user = \Drupal::state()->get('rit_user_utils_auth_enable_user');
    $custom_config = \Drupal::state()->get('rit_user_utils_custom_config');
    $shib_variables = \Drupal::state()->get('shib_role_assign_shib_variable', 'ritEduAffiliation');
	
	
	if(!isset($enable_auth)){
		$enable_auth = 1;
	}
	if(!isset($enable_auth_user)){
		$enable_auth_user = 1;
	}
	
	
    //if the variable from the database is an array
	if(is_array($shib_variables)){
		//loop through the array
		foreach($shib_variables as $header){
	  		//set the header variable to the array
	  		$default_value[] = $header;
	  		
	  		//increment our counter
			$num++;
	  	}
	  	//if we're not rebuilding the form currently
	  	if(!$form_state->isRebuilding()){
	  		//set the num_headers to our counter value
	  		$form_state->set('num_headers', $num);
	  	}
	}else{
		//set the default_value to our single shib variable
	  	$default_value[] = $shib_variables;
	}	
	
	$header_field = $form_state->get('num_headers');
	
	$form['rit_user_utils_auth_enable'] = [
      '#type' =>  'checkbox',
      '#title' => $this->t('Enable /user to redirect to Single Sign On (SSO) for all authentication'),
      '#default_value' => $enable_auth,
    ];
    
    $form['rit_user_utils_auth_enable_user'] = [
      '#type' =>  'checkbox',
      '#title' => $this->t('Enable /user/login to redirect to SSO for all authentication'),
      '#default_value' => $enable_auth_user,
    ];
    
    $form['rit_user_utils_custom_config'] = [
      '#type' =>  'checkbox',
      '#title' => $this->t('Enable custom configuration for SSO headers'),
      '#default_value' => $custom_config,
    ];
	
   
   	$form['description'] = array(
        '#markup' => '<br /><div>'. t('Add SSO Header Variables below to have Drupal automagically assign roles based on the header values.<br />
    	All variables start with "REDIRECT_", but that does not need to be added as the module will append it when processing logins.</div><br />
    	NOTE: While the variables may be selectable here, you may need a custom SSO configuration on your site to utilize them.<br />Maximum allowed headers is currently ' . $header_field).'</div>',
    );

    
    $form['#tree'] = TRUE;
    
    $form['shib_header_fieldset'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('SSO Header Variables'),
        '#prefix' => '<div id="shib-header-fieldset-wrapper">',
        '#suffix' => '</div>',
    ];
    if (empty($header_field)) {
        $header_field = $form_state->set('num_headers', 1);
    }

    if ($form_state->get('num_headers')>0) {
        $value = $form_state->get('num_headers');
    }
    else {
        $value = 3;
    }

	for ($i = 0; $i < $value; $i++) {
		if(isset($default_value[$i])){
			$dv = $default_value[$i];
		}else{
			$dv = '';
		}	
		
		
		$form['shib_header_fieldset']['variable'][$i] = [
			'#type' => 'textfield',
			'#title' => t('Variable #' . ($i+1)),
			'#default_value' => $dv,
		  	'#prefix' => '<div class="col1">',
		  	'#suffix' => '</div>'
		];
	}

    
    $form_state->setCached(FALSE);
    $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
    ];

    return $form;
  }

	/**
	* {@inheritdoc}
	*/
	public function validateForm(array &$form, FormStateInterface $form_state) {

	}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  	$shib_variables = array();
  	
  	$enable_auth = $form_state->getValue('rit_user_utils_auth_enable');
  	$enable_auth_user = $form_state->getValue('rit_user_utils_auth_enable_user');
  	$custom_config = $form_state->getValue('rit_user_utils_custom_config');
  	$values = $form_state->getValue(array('shib_header_fieldset', 'variable'));
  	
	//save the variable(s) to the database
	\Drupal::state()->set('shib_role_assign_shib_variable', $values);
  	\Drupal::state()->set('rit_user_utils_auth_enable', $enable_auth);
  	\Drupal::state()->set('rit_user_utils_auth_enable_user', $enable_auth_user);
  	\Drupal::state()->set('rit_user_utils_custom_config', $custom_config);
  	
    drupal_set_message($this->t('Configuration settings saved.'));
  }

}
