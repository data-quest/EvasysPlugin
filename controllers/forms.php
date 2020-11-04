<?php

class FormsController extends PluginController
{

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (!EvasysPlugin::isRoot() || !Config::get()->EVASYS_ENABLE_PROFILES) {
            throw new AccessDeniedException();
        }
    }

    public function index_action() {
        Navigation::activateItem("/admin/evasys/forms");
        PageLayout::setTitle($this->plugin->getDisplayName());
        try {
            $this->forms = EvasysForm::findAll();
        } catch (Exception $e) {
            PageLayout::postError($e->getMessage());
        }
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

    public function sort_action($profile_type, $sem_type, $profile_id)
    {
        $this->profile_type = $profile_type;
        $this->sem_type = $sem_type;
        $this->profile_id = $profile_id;
        if ($this->profile_type === "global") {
            if (!$this->plugin->isRoot()) {
                throw new AccessDeniedException();
            }
        } else {
            //
        }
        PageLayout::setTitle(sprintf(_("Fragebögen sortieren für Typ %s"), $GLOBALS['SEM_TYPE'][$this->sem_type]['name']));
        $this->forms = EvasysProfileSemtypeForm::findBySQL("profile_type = :profile_type AND profile_id = :profile_id AND sem_type = :sem_type AND standard = '0' ORDER BY position ASC", array(
            'profile_type' => $this->profile_type,
            'sem_type' => $this->sem_type,
            'profile_id' => $this->profile_id
        ));
        if (Request::isPost()) {
            $positions = array_flip(Request::getArray("form"));
            foreach ($this->forms as $form) {
                $form['position'] = $positions[$form['form_id']];
                $form->store();
            }
            PageLayout::postSuccess(_("Sortierung wurde gespeichert."));
            $this->forms = EvasysProfileSemtypeForm::findBySQL("profile_type = :profile_type AND profile_id = :profile_id AND sem_type = :sem_type AND standard = '0' ORDER BY position ASC", array(
                'profile_type' => $this->profile_type,
                'sem_type' => $this->sem_type,
                'profile_id' => $this->profile_id
            ));
        }
        $this->standardform = EvasysProfileSemtypeForm::findOneBySQL("profile_type = :profile_type AND profile_id = :profile_id AND sem_type = :sem_type AND standard = '1'", array(
            'profile_type' => $this->profile_type,
            'sem_type' => $this->sem_type,
            'profile_id' => $this->profile_id
        ));
    }
}
