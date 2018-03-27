<?php

class GlobalprofileController extends PluginController
{

    public function index_action()
    {
        Navigation::activateItem("/admin/evasys/globalprofile");
        PageLayout::setTitle($this->plugin->getDisplayName());
        $this->profile = EvasysGlobalProfile::findCurrent();
    }

    public function edit_action()
    {
        $this->profile = EvasysGlobalProfile::findCurrent();
        if (Request::isPost()) {
            $data = Request::getArray("data");
            $data['begin'] = $data['begin'] ? strtotime($data['begin']) : null;
            $data['end'] = $data['end'] ? strtotime($data['end']) : null;
            $this->profile->setData($data);
            $this->profile->store();
            PageLayout::postSuccess(_("Einstellungen wurden gespeichert"));
        }
        $this->redirect("globalprofile/index");
    }


}