<?php
 class DEPLOY_CONFIG {

        // Default deployment options for the current project
        // these options could be modified during the deployment process in standard mode
        // in fast mode these options will be used
        var $options = array(
                'export'                => array(),
                'synchronize'   => array(
                        'runBeforeScript'               =>      true,           //enable custom script before deployement
                        'backup'                                =>      false           //enable backup functionality
                ),
                'finalize'              => array(
                        'renamePrdFile'                 =>      false,           //enable renaming .prd.xxx files
                        'changeFileMode'                =>      false,           //enable updating file mode
                        'giveWriteMode'                 =>      false,           //enable updating write mode on directories defined in $writable (in this file)
                        'runAfterScript'                =>      true            //enable custom script at the end of the deployement process
                )
        );

        // path of yours custom scripts to execute at the beginning/end of the deployment process
        // if yours scripts are located in a directory named ".fredistrano" at the root of your project enter only the name of your script to execute
        var $scripts = array(
                'before'        =>              'beforeScript',
                'after'         =>              'afterScript'
        );

        // List of directories and files to exclude during the deployemnt process on the production server
        var $exclude = array (
                '/public/.htaccess',
		'/public/sas.php',
		'/public/images/captcha',
		'/application/configs/application.ini',
		'/nbproject/',
		'/library/Zend/',
		'/library/ZendX/',
		'/tests/'
        );

        // Directories list on which the write permission will applied during the finalization step of the deployment process
        // log, cache, upload directories, etc...
        var $writable = array (

		'/public/images/captcha/'

        );

 }// DEPLOY_CONFIG
?>
