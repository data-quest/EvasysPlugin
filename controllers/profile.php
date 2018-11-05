<?php

class ProfileController extends PluginController {

    public function edit_action($course_id)
    {
        if (Navigation::hasItem("/course/admin/evasys")) {
            Navigation::activateItem("/course/admin/evasys");
        }
        PageLayout::setTitle(_("Evaluationsdaten bearbeiten"));
        $this->profile = EvasysCourseProfile::findBySemester($course_id);
        if (!$this->profile) {
            $this->profile = new EvasysCourseProfile();
            $this->profile['seminar_id'] = $course_id;
            $this->profile['semester_id'] = Semester::findCurrent()->id;
        }
        if (Request::isPost() && $this->profile->isEditable()) {
            $data = Request::getArray("data");
            $this->profile['applied'] = $data['applied'] ?: 0;
            if ($this->profile['applied'] && !EvasysPlugin::isAdmin($course_id) && !EvasysPlugin::isRoot()) {
                $this->profile['by_dozent'] = 1;
            }
            $this->profile['teachers'] = $data['teachers']
                ? $data['teachers']
                : null;
            $this->profile['results_email'] = $data['results_email'] ?: null;
            $this->profile['split'] = $data['split'] ? 1 : 0;
            $this->profile['form_id'] = $data['form_id'] !== $this->profile->getPresetFormId() ? $data['form_id'] : null;
            if ($data['begin']) {
                $this->profile['begin'] = strtotime($data['begin']);
                if ($this->profile['begin'] == $this->profile->getPresetBegin()) {
                    $this->profile['begin'] = null;
                }
            } else {
                $this->profile['begin'] = null;
            }
            if ($data['end']) {
                $this->profile['end'] = strtotime($data['end']);
                if ($this->profile['end'] == $this->profile->getPresetEnd()) {
                    $this->profile['end'] = null;
                }
            } else {
                $this->profile['end'] = null;
            }
            if ($data['mode']) {
                $this->profile['mode'] = $data['mode'];
                if ($this->profile['mode'] == $this->profile->getPresetMode()) {
                    $this->profile['mode'] = null;
                }
            } else {
                $this->profile['mode'] = null;
            }
            if ($data['address']) {
                $this->profile['address'] = $data['address'];
                if ($this->profile['address'] == $this->profile->getPresetAddress()) {
                    $this->profile['address'] = null;
                }
            } else {
                $this->profile['address'] = null;
            }
            $this->profile['number_of_sheets'] = $data['number_of_sheets'] ?: null;
            $this->profile['language'] = $data['language'] ?: null;

            $this->profile['user_id'] = $GLOBALS['user']->id;

            if (Request::submitted("unset_by_dozent") && (EvasysPlugin::isRoot() || EvasysPlugin::isAdmin($course_id))) {
                $this->profile['by_dozent'] = 0;
            }

            $this->profile->store();

            PageLayout::postSuccess(_("Daten wurden gespeichert."));
            $this->response->add_header("X-Dialog-Execute", json_encode(array(
                'func' => "STUDIP.Evasys.refreshCourseInOverview",
                'payload' => $course_id
            )));
        }
    }



    public function bulkedit_action()
    {
        Navigation::activateItem("/browse/my_courses/list");
        PageLayout::setTitle(_("Evaluationsdaten"));
        $this->course_ids = array_keys(Request::getArray("c"));
        if (!count($this->course_ids)) {
            PageLayout::postError(_("Es wurden keine Veranstaltungen zum Bearbeiten ausgewählt."));
            $this->redirect(URLHelper::getURL("dispatch.php/admin/courses"));
        }
        $this->profiles = EvasysCourseProfile::findManyBySemester($this->course_ids);

        if (Request::isPost() && Request::submitted("submit")) {
            foreach ($this->profiles as $profile) {
                if (!$profile->isEditable()) {
                    continue;
                }
                if (in_array("applied", Request::getArray("change"))) {
                    if (Request::get("applied") !== "") {
                        $profile['applied'] = Request::int("applied");
                        if ($profile['applied'] && $profile['teachers'] === null) {
                            $seminar = new Seminar($profile['seminar_id']);
                            $teachers = $seminar->getMembers("dozent");
                            $teacher = array_shift(array_values($teachers));
                            $profile['teachers'] = array($teacher['user_id']);
                        }
                    }
                }
                if (in_array("split", Request::getArray("change"))) {
                    if (Request::get("split") !== "") {
                        $profile['split'] = Request::int("split");
                    }
                }
                if (in_array("begin", Request::getArray("change"))) {
                    if (Request::get("begin")) {
                        $begin = strtotime(Request::get("begin"));
                        if ($begin > 0) {
                            $profile['begin'] = $begin != $profile->getPresetBegin() ? $begin : null;
                        }
                    } else {
                        $profile['begin'] = null;
                    }
                }
                if (in_array("end", Request::getArray("change"))) {
                    if (Request::get("end")) {
                        $end = strtotime(Request::get("end"));
                        if ($begin > 0) {
                            $profile['end'] = $end != $profile->getPresetEnd() ? $end : null;
                        }
                    } else {
                        $profile['end'] = null;
                    }
                }
                if (in_array("form_id", Request::getArray("change"))) {
                    if (Request::option("form_id") !== "") {
                        $profile['form_id'] = $profile->getPresetFormId() != Request::option("form_id")
                            ? Request::option("form_id")
                            : null;
                    }
                }
                if (in_array("mode", Request::getArray("change"))) {
                    if (Request::option("mode") !== "") {
                        $profile['mode'] = $profile->getPresetMode() != Request::option("mode")
                            ? Request::option("mode")
                            : null;
                    }
                }
                if (in_array("language", Request::getArray("change"))) {
                    if (Request::option("language") !== "") {
                        $profile['language'] = Request::get("language");
                    }
                }
                $profile['user_id'] = $GLOBALS['user']->id;

                $profile->store();
            }
            PageLayout::postSuccess(_("Evaluationsdaten wurden gespeichert"));
            if (Request::get("individual")) {
                $this->redirect(PluginEngine::getURL($this->plugin, array(), "individual/list"));
            } else {
                $this->redirect(URLHelper::getURL("dispatch.php/admin/courses"));
            }
            return;
        }

        $this->values = array();
        $this->all_form_ids = null;
        $this->available_form_ids = null;
        foreach ($this->profiles as $profile) {

            if ($this->values['applied'] === null) {
                $this->values['applied'] = $profile['applied'];
            } elseif ($this->values['applied'] !== $profile['applied']) {
                $this->values['applied'] = "EVASYS_UNEINDEUTIGER_WERT";
            }


            $begin = $profile->getFinalBegin();
            if ($this->values['begin'] === null) {
                $this->values['begin'] = $begin;
            } elseif ($this->values['begin'] !== $begin) {
                $this->values['begin'] = "EVASYS_UNEINDEUTIGER_WERT";
            }
            $end = $profile->getFinalEnd();
            if ($this->values['end'] === null) {
                $this->values['end'] = $end;
            } elseif ($this->values['end'] !== $end) {
                $this->values['end'] = "EVASYS_UNEINDEUTIGER_WERT";
            }

            $form_id = $profile->getFinalFormId();
            if ($this->values['form_id'] === null) {
                $this->values['form_id'] = $form_id;
            } elseif ($this->values['form_id'] !== $form_id) {
                $this->values['form_id'] = "EVASYS_UNEINDEUTIGER_WERT";
            }
            $available = array($profile->getPresetFormId());
            $available = array_unique(array_merge($available, $profile->getAvailableFormIds()));
            if ($this->all_form_ids === null) {
                $this->all_form_ids = $available;
            } else {
                $this->all_form_ids = array_merge($this->all_form_ids, $available);
            }
            $this->all_form_ids = array_unique($this->all_form_ids);
            if ($this->available_form_ids === null) {
                $this->available_form_ids = $available;
            } else {
                $this->available_form_ids = array_intersect($this->available_form_ids, $available);
            }

            $mode = $profile->getFinalMode();
            if ($this->values['mode'] === null) {
                $this->values['mode'] = $mode;
            } elseif ($this->values['mode'] !== $mode) {
                $this->values['mode'] = "EVASYS_UNEINDEUTIGER_WERT";
            }

            $language = $profile['language'];
            if ($this->values['language'] === null) {
                $this->values['language'] = $language;
            } elseif ($this->values['language'] !== $language) {
                $this->values['language'] = "EVASYS_UNEINDEUTIGER_WERT";
            }

        }
    }
}