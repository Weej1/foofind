<?php
/**
 * This is the UserLogin form.
 */

class Form_UserEdit extends Zend_Form {
	
	public function init() {
		// set the method for the display form to POST
		$this->setMethod ( 'post' );

                $this->addElement ( 'text', 'username', array ('label' => 'Choose a nickname:', 'filters' => array ('StringTrim', 'StringToLower' ),
                    'validators' => array ('alnum', array ('regex', false, array ('/^[a-z]/i' ) ), array ('StringLength', false, array (3, 20 ) ) ), 'required' => true )
		 );

                 $this->addElement ( 'text', 'location', array ('label' => 'Your location:', 'filters' => array ('StringTrim', 'StringToLower' ),
                    'validators' => array ( array ('StringLength', false, array (5, 50 ) ) ), 'required' => false )
		 );
                

		$this->addElement ( 'password', 'password', array ('filters' => array ('StringTrim' ), 'validators' => array (array ('StringLength', false, array (5, 20 ) ) ), 'required' => false,
                    'label' => 'Change your password here: (otherwise leave it blank)' ) );

		// add the submit button
		$this->addElement ( 'submit', 'submit', array ('label' => 'Login',
                                                 'class' => 'large magenta awesome') );
	}
}

