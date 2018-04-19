<?php

class ProfileController extends PluginController {

    public function edit_action($course_id)
    {
        PageLayout::setTitle(_("Evaluationsdaten bearbeiten"));
        $this->profile = EvasysCourseProfile::findOneBySQL("seminar_id = :seminar_id AND semester_id = :semester_id", array(
            'seminar_id' => $course_id,
            'semester_id' => Semester::findCurrent()->id
        ));
        if (!$this->profile) {
            $this->profile = new EvasysCourseProfile();
            $this->profile['seminar_id'] = $course_id;
            $this->profile['semester_id'] = Semester::findCurrent()->id;
        }
        if (Request::isPost() && $this->profile->isEditable()) {
            $data = Request::getArray("data");
            $this->profile['applied'] = $data['applied'] ?: 0;
            $seminar = new Seminar($this->profile['seminar_id']);
            $teachers = $seminar->getMembers("dozent");
            $this->profile['teachers'] = $data['teachers'] && count($data['teachers']) !== count($teachers)
                ? $data['teachers']
                : null;
            $this->profile['teachers_results'] = $data['teachers_results'] && count($data['teachers_results']) !== count($teachers)
                ? $data['teachers_results']
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

            $this->profile->store();
            PageLayout::postSuccess(_("Daten wurden gespeichert."));
            $this->response->add_header("X-Dialog-Execute", json_encode(array(
                'func' => "STUDIP.EVASYS.refreshCourseInOverview",
                'payload' => $course_id
            )));
        }
    }

    public function bulkedit_action()
    {
        $this->course_ids = array_keys(Request::getArray("c"));
        if (!count($this->course_ids)) {
            PageLayout::postError(_("Es wurden keine Veranstaltungen zum Bearbeiten ausgewählt."));
            $this->redirect(URLHelper::getURL("dispatch.php/admin/courses"));
        }
        $this->profiles = EvasysCourseProfile::findManyBySemester($this->course_ids);

        if (Request::isPost() && Request::submitted("submit")) {
            foreach ($this->profiles as $profile) {
                if (in_array("applied", Request::getArray("change"))) {
                    if (Request::get("applied") !== "") {
                        $profile['applied'] = Request::int("applied");
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

                $profile->store();
            }
            PageLayout::postSuccess(_("Evaluationsdaten wurden gespeichert"));
            $this->redirect(URLHelper::getURL("dispatch.php/admin/courses"));
        }

        $this->values = array(
            'applied' => null
        );
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
        }
    }

}