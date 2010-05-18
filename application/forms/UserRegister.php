<?php
/**
 * This is the UserRegister form.
 */

class Form_UserRegister extends Zend_Form {
	
	public function init() {
		
		$this->setMethod ( 'post' );

		$this->addElement ( 'text', 'email', array ('label' => 'Your email:', 'required' => true, 'filters' => array ('StringTrim' ), 'validators' => array ('EmailAddress' ) ) );


		$this->addElement ( 'text', 'username', array ('label' => 'Choose a nickname:', 'filters' => array ('StringTrim', 'StringToLower' ),
                    'validators' => array ('alnum', array ('regex', false, array ('/^[a-z]/i' ) ), array ('StringLength', false, array (3, 20 ) ) ), 'required' => true )

		 );

                $this->addElement ( 'password', 'password1', array ('filters' => array ('StringTrim' ), 'validators' => array (array ('StringLength', false, array (5, 20 ) ) ), 'required' => true,
                    'label' => 'Choose your password:' ) );

                $this->addElement ( 'password', 'password2', array ('filters' => array ('StringTrim' ), 'validators' => array (array ('StringLength', false, array (5, 20 ) ) ), 'required' => true,
                    'label' => 'Insert your password (yep, again):' ) );

		$this->addElement ( 'captcha', 'captcha', array ('label' => 'Please, insert the 5 characters shown:', 'required' => true,
                    'captcha' => array ('captcha' => 'Image', 'wordLen' => 5, 'height' => 50, 'width' => 160, 'gcfreq' => 50, 'timeout' => 300,
                     'font' => APPLICATION_PATH . '/configs/antigonimed.ttf',
                     'imgdir' => FOOFIND_PATH . '/public/images/captcha' ) ) );


                $checkboxDecorator = array(
                                'ViewHelper',
                                'Errors',
                                array(array('data' => 'HtmlTag'), array('tag' => 'span', 'class' => 'element')),
                                array('Label', array('tag' => 'dt'),
                                array(array('row' => 'HtmlTag'), array('tag' => 'span')),
                            ));

                $this->addElement('checkbox', 'agree', array(
                    'decorators' => $checkboxDecorator,
                    'required' => true,
                    'checked' =>false
                    ));

                $this->removeDecorator('DtDdWrapper');
                $this->removeDecorator('dt');
                $this->removeDecorator('dd');
  
                $this->addElement ( 'submit', 'submit',
                        array ('label' => 'Register',
                             'class' => 'large magenta awesome') );
               
	}
}

