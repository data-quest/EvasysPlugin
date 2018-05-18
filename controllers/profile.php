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
            if ($this->profile->isNew() && !EvasysPlugin::isAdmin() && !EvasysPlugin::isRoot()) {
                $this->profile['by_dozent'] = 1;
                //Nachricht an zentrale QM und an Mitdozenten:
            }

            StudipLog::log(
                $this->profile->isNew() ? 'LAVE_LEHRAUFTRAG_CREATE' : 'LAVE_LEHRAUFTRAG_UPDATE',
                $this->user_id,
                $this->profile['seminar_id'],
                Semester::findCurrent()->id,
                json_encode([
                    'lehrauftrag' => $delta_lehrauftrag,
                    'user'        => $delta_user,
                    'userdata'    => $delta_userdata
                ]));

            $wasnew = $this->profile->isNew();
            $this->profile->store();

            if ($this->profile['by_dozent']) {
                //Nachricht an Dozenten:
                if (EvasysPlugin::isRoot()
                        || EvasysPlugin::isAdmin()
                        || Config::get()->EVASYS_ENABLE_MESSAGE_FOR_ADMINS
                        //|| !$wasnew
                        ) {
                    //Nur Dozenten
                    $statement = DBManager::get()->prepare("
                        SELECT username
                        FROM auth_user_md5
                            INNER JOIN seminar_user ON (seminar_user.user_id = auth_user_md5.user_id)
                        WHERE seminar_user.status = 'dozent'
                            AND seminar_user.Seminar_id = :seminar_id
                    ");
                    $statement->execute(array(
                        'seminar_id' => $this->profile['seminar_id']
                    ));

                } else {
                    //Dozenten und Admins:
                    $statement = DBManager::get()->prepare("
                        SELECT username
                        FROM auth_user_md5
                            INNER JOIN seminar_user ON (seminar_user.user_id = auth_user_md5.user_id)
                        WHERE seminar_user.status = 'dozent'
                            AND seminar_user.Seminar_id = :seminar_id
                        UNION SELECT username
                        FROM auth_user_md5
                            INNER JOIN roles_user ON (roles_user.userid = auth_user_md5.user_id)
                            INNER JOIN roles ON (roles.roleid = roles_user.roleid)
                        WHERE roles.rolename = 'Evasys-Admin'
                            AND (roles_user.institut_id = '' OR roles_user.institut_id = :institut_id)
                    ");
                    $statement->execute(array(
                        'seminar_id' => $this->profile['seminar_id'],
                        'institut_id' => $this->profile->course['institut_id']
                    ));
                }
                $dozenten = $statement->fetchAll(PDO::FETCH_COLUMN, 0);

                foreach ($dozenten as $dozent_username) {
                    if ($dozent_username !== $GLOBALS['user']->username) {
                        $messaging = new messaging();
                        $oldbase = URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
                        if ($wasnew) {
                            $message = sprintf(
                                _("%s hat gerade für die Veranstaltung %s eine Lehrevaluation beantragt. Die eingetragenen Daten können Sie hier einsehen und gegebenenfalls bearbeiten: \n\n %s"),
                                get_fullname($GLOBALS['user']->id),
                                $this->profile->course['name'],
                                URLHelper::getURL("plugins.php/evasysplugin/profile/edit/" . $this->profile['seminar_id'], array('cid' => $this->profile['seminar_id']), true)
                            );
                        } else {
                            $message = sprintf(
                                _("%s hat gerade die Lehrevaluationsdaten der Veranstaltung %s verändert. Die geänderten Daten können Sie hier einsehen und gegebenenfalls bearbeiten: \n\n %s"),
                                get_fullname($GLOBALS['user']->id),
                                $this->profile->course['name'],
                                URLHelper::getURL("plugins.php/evasysplugin/profile/edit/" . $this->profile['seminar_id'], array('cid' => $this->profile['seminar_id']), true)
                            );
                        }
                        $messaging->insert_message(
                            $message,
                            $dozent_username,
                            "____%system%____",
                            "",
                            "",
                            "",
                            "",
                            _("Bearbeitung der Evaluationsdaten"),
                            true,
                            "normal",
                            array("Lehrevaluation")
                        );
                        URLHelper::setBaseURL($oldbase);
                    }
                }

            }

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
                if (in_array("applied", Request::getArray("change"))) {
                    if (Request::get("applied") !== "") {
                        $profile['applied'] = Request::int("applied");
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
                $profile->store();
            }
            PageLayout::postSuccess(_("Evaluationsdaten wurden gespeichert"));
            $this->redirect(URLHelper::getURL("dispatch.php/admin/courses"));
        }

        $this->values = array();
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

        }
    }

}