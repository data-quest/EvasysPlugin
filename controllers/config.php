<?php

class ConfigController extends PluginController
{
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!Config::get()->EVASYS_ENABLE_PROFILES) {
            throw new AccessDeniedException();
        }

        if (!EvasysPlugin::isRoot()) {
            throw new AccessDeniedException();
        }
        Navigation::activateItem("/admin/evasys/additionalfields");
        PageLayout::addScript($this->plugin->getPluginURL()."/assets/evasys.js");
    }


    public function additionalfields_action()
    {
        PageLayout::setTitle($this->plugin->getDisplayName());
        $this->fields = EvasysAdditionalField::findBySQL("1=1 ORDER BY position ASC, name ASC");
    }

    public function edit_additionalfield_action($field_id = null)
    {
        $this->field = new EvasysAdditionalField($field_id);
        if (Request::isPost()) {
            $this->field->setData(Request::getArray("data"));
            $this->field['name'] = Request::i18n("name");
            $this->field->store();
            PageLayout::postSuccess(dgettext("evasys", "Angaben des Feldes wurden gespeichert."));
            $this->redirect("config/additionalfields");
        }
    }

    public function delete_additionalfield_action($field_id)
    {
        $this->field = new EvasysAdditionalField($field_id);
        if (Request::isPost()) {
            $this->field->delete();
            PageLayout::postSuccess(dgettext("evasys", "Feld wurde gelöscht."));
            $this->redirect("config/additionalfields");
        }
    }

}