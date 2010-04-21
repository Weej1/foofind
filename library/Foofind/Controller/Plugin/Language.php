<?php
/**
 * Front Controller Plugin
 *
 * @uses	   Zend_Controller_Plugin_Abstract
 * @subpackage Plugins
 */
class Foofind_Controller_Plugin_Language extends Zend_Controller_Plugin_Abstract {


	public function routeShutdown(Zend_Controller_Request_Abstract $request) {
		$locale = new Zend_Locale ( );
		$options = array ('scan' => Zend_Translate::LOCALE_FILENAME );
		$translate = new Zend_Translate ( 'csv', FOOFIND_PATH . '/application/lang/', 'auto', $options );
		$requestParams = $this->getRequest ()->getParams ();
		$language = (isset ( $requestParams ['language'] )) ? $requestParams ['language'] : false;
		if ($language == false) {
			$language = ($translate->isAvailable ( $locale->getLanguage () )) ? $locale->getLanguage () : $_COOKIE['lang'];
		}
		if (! $translate->isAvailable ( $language )) {

                        header('Location: /en');
                        exit;

		} else {


                        if(!$_COOKIE['lang']){


                               if ($locale->getLanguage() == $translate->isAvailable){

                                    setcookie ( $locale->getLanguage(), $locale->getLanguage() , null, '/' );
                                    $language = $locale->getLanguage();
                                    Zend_Registry::set ( 'Zend_Locale', $locale );

                               } else {
                                     setcookie ( 'lang', 'en' , null, '/' );
                                     $language = $_COOKIE['lang'];
                               }


                              
                               
                        }

                        $locale->setLocale ( $language );
			$translate->setLocale ( $locale );
			Zend_Form::setDefaultTranslator ( $translate );
			setcookie ( 'lang', $locale->getLanguage (), null, '/' );
			Zend_Registry::set ( 'Zend_Locale', $locale );
			Zend_Registry::set ( 'Zend_Translate', $translate );

                         

		}

	}

}
