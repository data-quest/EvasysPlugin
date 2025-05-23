<?php

class ProfileController extends PluginController
{
    public function edit_action($course_id)
    {
        if (Navigation::hasItem("/course/admin/evasys")) {
            Navigation::activateItem("/course/admin/evasys");
        }
        PageLayout::setTitle(dgettext("evasys", "Evaluationsdaten bearbeiten"));
        //guess the correct semester:
        $this->semester_id = null;
        $course = Course::find($course_id);
        if (Request::option("semester_id")) {
            $this->semester_id = Request::option("semester_id");
        } elseif ($GLOBALS['perm']->have_perm("admin") && $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE && strlen($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE) === 32) {
            $this->semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE;

            if (Navigation::hasItem("/course/admin/evasys")) {
                $sem = Semester::find($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE);
                if (!$course->isInSemester($sem)) {
                    //we are in the course and the course semester doesn't fit to our selected admin-semester:
                    $this->semester_id = $course->start_semester->getId();
                }
            }
        }
        if ($this->semester_id) {
            $this->profile = EvasysCourseProfile::findBySemester(
                $course_id,
                $this->semester_id
            );
        } else {
            $current_semester = Semester::findCurrent();
            if ($course->isInSemester($current_semester)) {
                $this->profile = EvasysCourseProfile::findBySemester($course_id);
            } else {
                $this->profile = EvasysCourseProfile::findBySemester(
                    $course_id,
                    $course->start_semester->getId()
                );
            }
            $this->semester_id = $this->profile['semester_id'];
        }




        if (!$this->profile) {
            $this->profile = new EvasysCourseProfile();
            $this->profile['seminar_id'] = $course_id;
            $this->profile['semester_id'] = Semester::findCurrent()->id;
        }

        if (Request::isPost()) {
            if ($this->profile->mayObjectToPublication()) {

                $data = Request::getArray("data");
                $this->profile['objection_to_publication'] = $data['objection_to_publication'] ? 1 : 0;
                $this->profile['objection_reason'] = $data['objection_reason'];
                //echo "peng \n";
                $this->profile->store();
            }

        }
        if (Request::isPost()
                && $this->profile->isEditable()
                && Request::getArray("data")
                && count(Request::getArray("data"))) {
            $data = Request::getArray("data");
            $this->profile['applied'] = $data['applied'] ?: 0;
            if (!$this->profile['applied']) {
                $this->profile['locked'] = 0;
            }
            if ($this->profile['applied'] && !EvasysPlugin::isAdmin($course_id) && !EvasysPlugin::isRoot()) {
                $this->profile['by_dozent'] = 1;
            }
            $this->profile['teachers'] = $data['teachers'] ?: null;
            $this->profile['results_email'] = $data['results_email'] ?: null;
            $this->profile['split'] = isset($data['split']) && $data['split'] ? 1 : 0;
            $this->profile['form_id'] = $data['form_id'] !== $this->profile->getPresetFormId() ? $data['form_id'] : null;
            $preset_begin = $this->profile->getPresetBegin();
            $begin = $data['begin'] ? strtotime($data['begin']) : $preset_begin;
            if ($data['begin']) {
                $this->profile['begin'] = strtotime($data['begin']);
            } else {
                $this->profile['begin'] = null;
            }
            if (!EvasysPlugin::isRoot() && !EvasysPlugin::isAdmin($course_id) && ($begin < time() + Config::get()->EVASYS_INDIVIDUAL_TIME_OFFSETS * 60)) {
                $this->profile['begin'] = time() + Config::get()->EVASYS_INDIVIDUAL_TIME_OFFSETS * 60;
            }
            if ($this->profile['begin'] == $preset_begin) {
                $this->profile['begin'] = null;
            }

            $preset_end = $this->profile->getPresetEnd();
            $end = $data['end'] ? strtotime($data['end']) : $preset_end;
            if ($data['end']) {
                $this->profile['end'] = strtotime($data['end']);
            } else {
                $this->profile['end'] = null;
            }
            if ($end < $begin) {
                $this->profile['end'] = $begin + 60 * 2;
            }
            if ($this->profile['end'] == $preset_end) {
                $this->profile['end'] = null;
            }
            if (isset($data['mode']) && $data['mode']) {
                $this->profile['mode'] = $data['mode'];
                if ($this->profile['mode'] == $this->profile->getPresetMode()) {
                    $this->profile['mode'] = null;
                }
            } else {
                $this->profile['mode'] = null;
            }

            $this->profile['user_id'] = $GLOBALS['user']->id;

            if (Request::submitted("unset_by_dozent") && (EvasysPlugin::isRoot() || EvasysPlugin::isAdmin($course_id))) {
                $this->profile['by_dozent'] = 0;
            }
            if (Request::submitted("set_by_dozent") && (EvasysPlugin::isRoot() || EvasysPlugin::isAdmin($course_id))) {
                $this->profile['by_dozent'] = 1;
            }
            if (Request::submitted('unlock') && EvasysPlugin::isRoot()) {
                $this->profile['locked'] = 0;
            }

            $this->profile->store();
            $this->profile->restore();

            foreach (Request::getArray("field") as $field_id => $value) {
                $field = new EvasysAdditionalField($field_id);
                if (!$field->isNew()) {
                    $field->valueFor("course", $this->profile->getId(), $value);
                }
            }


            if (Request::isDialog() && StudipVersion::newerThan('5.3.99')) {
                $this->response->add_header('X-Dialog-Close', 1);
                $this->response->add_header('X-Dialog-Execute', 'STUDIP.AdminCourses.App.loadCourse');
                $this->render_text($course_id);
            } else {
                PageLayout::postSuccess(dgettext("evasys", "Daten wurden gespeichert."));
            }
        }
        $log_actions = LogAction::findBySQL("`name` LIKE 'EVASYS_%'");
        $log_action_ids = SimpleORMapCollection::createFromArray($log_actions)->pluck('action_id');

        $this->logs = LogEvent::findBySQL("`coaffected_range_id` = :course_id AND `action_id` IN (:action_ids) ORDER BY `event_id` DESC", [
            'course_id' => $course_id,
            'action_ids' => $log_action_ids
        ]);

    }



    public function bulkedit_action()
    {
        Navigation::activateItem("/browse/my_courses/list");
        PageLayout::setTitle(dgettext("evasys", "Evaluationsdaten"));
        $this->ids = array_keys(Request::getArray("c"));
        if (empty($this->ids)) {
            PageLayout::postError(dgettext("evasys", "Es wurden keine Veranstaltungen zum Bearbeiten ausgewählt."));
            $this->redirect(URLHelper::getURL("dispatch.php/admin/courses"));
        }
        $this->profiles = [];
        foreach ($this->ids as $id) {
            if (strpos($id, '_') !== false) {
                list($seminar_id, $semester_id) = explode("_", $id);
            } else {
                $seminar_id = $id;
                $semester_id = false;
            }
            if (!$semester_id) {
                $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE;
                if (!$semester_id || $semester_id === "all") {
                    $semester_id = Course::find($seminar_id)->start_semester->getId();
                }
            }
            $this->profiles[] = EvasysCourseProfile::findBySemester(
                $seminar_id,
                $semester_id
            );
        }

        if (Request::isPost() && Request::submitted("submit")) {
            $fields = EvasysAdditionalField::findBySQL("1=1 ORDER BY position ASC, name ASC");
            foreach ($this->profiles as $profile) {
                if (!$profile->isEditable()) {
                    continue;
                }
                if (in_array("applied", Request::getArray("change"))) {
                    if (Request::get("applied") !== "") {
                        $formally_applied = $profile['applied'];
                        $profile['applied'] = Request::int("applied");
                        if ($profile['applied'] && (
                                ($profile['teachers'] === null)
                                || !$formally_applied
                                || count($profile->teachersNotInCourse()) > 0
                            )
                        ) {
                            $teacher_ids = $profile->course->members
                                ->filter(function ($m) { return $m->status === 'dozent'; })
                                ->orderBy('position ASC')
                                ->pluck('user_id');

                            if (Config::get()->EVASYS_SELECT_FIRST_TEACHER) {
                                $teacher = array_shift($teacher_ids);
                                $teacher_ids = array($teacher);
                            }
                            $profile['teachers'] = $teacher_ids;
                        }
                        if (!$profile['applied']) {
                            $profile['teachers'] = null;
                        }
                    }
                }
                if (in_array("split", Request::getArray("change"))) {
                    if (Request::get("split") !== "") {
                        $profile['split'] = Request::int("split");
                    }
                }
                if (in_array("results_email", Request::getArray("change"))) {
                    if (trim(Request::get("results_email"))) {
                        $emails = preg_split("/[\s,;]+/", Request::get("results_email"), -1, PREG_SPLIT_NO_EMPTY);
                        foreach ($emails as $email) {
                            if (stripos($profile['results_email'], $email) === false) {
                                $profile['results_email'] = $profile['results_email']
                                    . ($profile['results_email'] ? " " : "")
                                    . $email;
                            }
                        }
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
                        if ($end > 0) {
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
                if (!Config::get()->EVASYS_FORCE_ONLINE && in_array("mode", Request::getArray("change"))) {
                    if (Request::option("mode") !== "") {
                        $profile['mode'] = $profile->getPresetMode() != Request::option("mode")
                            ? Request::option("mode")
                            : null;
                    }
                }
                if (in_array("by_dozent", Request::getArray("change"))) {
                    if (Request::option("by_dozent") !== "") {
                        $profile['by_dozent'] = Request::option("by_dozent");
                    }
                }
                $profile['user_id'] = $GLOBALS['user']->id;

                $profile->store();

                foreach ($fields as $field) {
                    if (in_array($field->getId(), Request::getArray("change"))) {
                        $field->valueFor("course", $profile->getId(), Request::get($field->getId()));
                    }
                }
            }
            PageLayout::postSuccess(dgettext("evasys", "Evaluationsdaten wurden gespeichert"));
            if (Request::get("individual")) {
                $this->redirect(PluginEngine::getURL($this->plugin, [], "individual/list"));
            } else {
                $this->redirect(URLHelper::getURL("dispatch.php/admin/courses"));
            }
            return;
        }

        $this->values = [];
        $this->all_form_ids = null;
        $this->available_form_ids = null;
        foreach ($this->profiles as $profile) {

            if (empty($this->values['applied'])) {
                $this->values['applied'] = $profile['applied'];
            } elseif ($this->values['applied'] !== $profile['applied']) {
                $this->values['applied'] = "EVASYS_UNEINDEUTIGER_WERT";
            }


            $begin = $profile->getFinalBegin();
            if (empty($this->values['begin'])) {
                $this->values['begin'] = $begin;
            } elseif ($this->values['begin'] !== $begin) {
                $this->values['begin'] = "EVASYS_UNEINDEUTIGER_WERT";
            }
            $end = $profile->getFinalEnd();
            if (empty($this->values['end'])) {
                $this->values['end'] = $end;
            } elseif ($this->values['end'] !== $end) {
                $this->values['end'] = "EVASYS_UNEINDEUTIGER_WERT";
            }

            $form_id = $profile->getFinalFormId();
            if (empty($this->values['form_id'])) {
                $this->values['form_id'] = $form_id;
            } elseif ($this->values['form_id'] !== $form_id) {
                $this->values['form_id'] = "EVASYS_UNEINDEUTIGER_WERT";
            }
            $available = [$profile->getPresetFormId()];
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
            if (empty($this->values['mode'])) {
                $this->values['mode'] = $mode;
            } elseif ($this->values['mode'] !== $mode) {
                $this->values['mode'] = "EVASYS_UNEINDEUTIGER_WERT";
            }

            foreach (EvasysAdditionalField::findBySQL("1=1 ORDER BY position ASC, name ASC") as $field) {
                $value = $field->valueFor("course", $profile->getId());
                if ($this->values[$field->getId()] === null) {
                    $this->values[$field->getId()] = $value;
                } elseif ($this->values[$field->getId()] !== $value) {
                    $this->values[$field->getId()] = "EVASYS_UNEINDEUTIGER_WERT";
                }
            }


            $by_dozent = $profile['by_dozent'];
            if (empty($this->values['by_dozent'])) {
                $this->values['by_dozent'] = $by_dozent;
            } elseif ($this->values['by_dozent'] !== $by_dozent) {
                $this->values['by_dozent'] = "EVASYS_UNEINDEUTIGER_WERT";
            }

        }
    }
}
