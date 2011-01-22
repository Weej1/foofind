<?php

class Zend_Controller_Action_Helper_Checklang extends Zend_Controller_Action_Helper_Abstract {

    static $langcodes = array('en'=>1, 'es'=>2, 'fr'=>3, 'it'=>4, 'tr'=>5);

    function init()
    {
        Zend_Registry::set('languages', array('en'=>'English', 'es'=>'Español', 'fr'=>'Français', 'it'=>'Italiano', 'tr'=>'Türkçe' ));
        $testlangs = array('fr'=>1, 'it'=>1, 'tr'=>1);
        Zend_Registry::set('testlangs', $testlangs);

        $this->lang = $this->getRequest()->getParam("language");
        if ($this->lang == null)
        {
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) $this->lang = $auth->getIdentity()->lang;
        }
        if ($this->lang == null && array_key_exists('lang', $_COOKIE))
            $this->lang = $_COOKIE['lang'];

        $locale = new Zend_Locale ($this->lang);

        $this->langtest = $testlangs[$locale->getLanguage()]==1;

        if (!$this->langtest && !in_array($locale->getLanguage(), array('en', 'es'))) {
            $locale->setLocale ('en');
        }
        $this->lang = $locale->getLanguage ();
        $options = array ('scan' => Zend_Translate::LOCALE_FILENAME );
        $translate = new Zend_Translate ( 'csv', FOOFIND_PATH . '/application/lang/', $this->lang, $options );

        if ($translate->isAvailable ( $this->lang )) {
            $translate->setLocale ( $locale );
            Zend_Form::setDefaultTranslator ( $translate );
            Zend_Registry::set ( 'Zend_Translate', $translate );
        } else {
            header('Location: /en');
            exit;
        }

        if ($this->langtest && (!isset($_COOKIE['langtest']) || $_COOKIE['langtest']!="0")) {
            $controller = $this->getActionController();
            if (!$controller->view->advices) $controller->view->advices = array();
            $controller->view->advices["langtest"] = $controller->view->translate("BetaTranslation", "/{$this->lang}/page/translate?lang={$this->lang}");
            $controller->view->headScript()->appendFile( STATIC_PATH . '/js/jquery.advice.js');
        }
    }

    function check(){
        return $this->lang;
    }
    
    function getcode($lang = null)
    {
        if (!isset($lang)) $lang = $this->lang;
        return Zend_Controller_Action_Helper_Checklang::$langcodes[$lang];
    }
}

