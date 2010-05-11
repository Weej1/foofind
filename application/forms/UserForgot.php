<?php
/**
 * This is the UserForgot form.
 */

class Form_UserForgot extends Zend_Form {
	public function init() {
		// set the method for the display form to POST
		$this->setMethod ( 'post' );

		$this->addElement ( 'text', 'email', array ('label' => 'Your email:', 'required' => true, 'filters' => array ('StringTrim' ), 'validators' => array ('EmailAddress' ) ) );

               $this->addElement ( 'captcha', 'captcha', array ('label' => 'Please, insert the 5 characters shown:', 'required' => true,
                    'captcha' => array ('captcha' => 'Image', 'wordLen' => 5, 'height' => 50, 'width' => 160, 'gcfreq' => 50, 'timeout' => 300,
                     'font' => APPLICATION_PATH . '/configs/antigonimed.ttf',
                     'imgdir' => FOOFIND_PATH . '/public/images/captcha' ) ) );

               
		// add the submit button
		$this->addElement ( 'submit', 'submit', 
                        array ('label' => 'Send',
                                   'class' => 'large magenta awesome') );
	}
}

