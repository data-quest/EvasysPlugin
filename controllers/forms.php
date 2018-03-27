<?php

class FormsController extends PluginController
{

    public function index_action() {
        Navigation::activateItem("/admin/evasys/forms");
        PageLayout::setTitle($this->plugin->getDisplayName());
        $this->forms = EvasysForm::findAll();
    }

    public function edit_action($form_id) {
        Navigation::activateItem("/admin/evasys/forms");
        PageLayout::setTitle(_("Fragebogen bearbeiten"));
        $this->form = EvasysForm::find($form_id);
        if (Request::isPost()) {
            $data = Request::getArray("data");
            $data['active'] || $data['active'] = 0;
            $this->form->setData($data);
            $this->form->store();
            PageLayout::postSuccess(_("Fragebogen wurde gespeichert"));
        }
    }

    public function activate_action()
    {
        if (Request::isPost()) {
            foreach (Request::getArray("a") as $form_id) {
                $form = EvasysForm::find($form_id);
                if ($form) {
                    $form['active'] = 1;
                    $form->store();
                }
            }
            $statement = DBManager::get()->prepare("
                UPDATE evasys_forms
                SET active = '0'
                WHERE form_id NOT IN (?)
            ");
            $statement->execute(array(Request::getArray("a")));
        }
        $this->redirect("forms/index");
    }
}