<?php


class PassiveaccountController extends PluginController
{
    public function index_action()
    {
        Navigation::activateItem("/profile/evasyspassiveaccount");
        PageLayout::setTitle(_("EvaSys-Zugang"));

        $soap = EvasysSoap::get();

        if (!$GLOBALS['user']->cfg->ADMIN_COURSES_SEARCHTEXT) {

            $user_info = $soap->__soapCall("GetUserIdsByParams", array(
                'Params' => array(
                    'Email' => User::findCurrent()->email
                )
            ));
            $user_ids = $user_info->Strings;
            $eavsys_user_id = $user_ids[0];
            if ($eavsys_user_id) {
                $GLOBALS['user']->cfg->store("EVASYS_INTERNAL_USER_ID", $eavsys_user_id);
            }
        }

        $this->link = $soap->__soapCall("GetSessionForUser", array(
            'UserId' => $eavsys_user_id,
            'IdType' => "INTERNAL"
        ));
        if (is_a($this->link, "SoapFault")) {
            if ($GLOBALS['user']->cfg->ADMIN_COURSES_SEARCHTEXT) {
                $GLOBALS['user']->cfg->delete("EVASYS_INTERNAL_USER_ID");
                $this->redirect("passiveaccount/index");
            }

        } else {
            $this->link = (array) $this->link;
        }
    }
}