<?php

require_once __DIR__."/globalprofile.php";

class InstituteprofileController extends GlobalprofileController
{

    public $profile_type = "institute";

    public function change_institute_action()
    {
        if (Request::submitted('institute')) {
            $GLOBALS['user']->cfg->store('MY_INSTITUTES_DEFAULT', Request::option('institute'));
        }

        $this->redirect('instituteprofile');
    }

}
