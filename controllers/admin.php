<?php

require_once 'app/controllers/plugin_controller.php';

class AdminController extends PluginController {

    public function index_action()
    {
        $db = DBManager::get();

        if (Request::submitted("absenden")) {
            $activate = Request::getArray("activate");
            $evasys_seminar = array();
            foreach (Request::getArray("course") as $course_id) {
                $evasys_evaluations = EvaSysSeminar::findBySeminar($course_id);
                if (count($evasys_evaluations)) {
                    foreach ($evasys_evaluations as $evaluation) {
                        $evaluation['activated'] = $activate[$course_id] ? 1 : 0;
                        if (!$evaluation['activated']) {
                            $evaluation->store();
                            unset($evasys_seminar[$course_id]);
                        } else {
                            $evasys_seminar[$course_id] = $evaluation;
                        }
                    }
                } else {
                    $evasys_seminar[$course_id] = new EvaSysSeminar(array($course_id, ""));
                    $evasys_seminar[$course_id]['activated'] = $activate[$course_id] ? 1 : 0;
                }
            }
            if (count($evasys_seminar) > 0) {
                $success = EvaSysSeminar::UploadSessions($evasys_seminar);
                if ($success === true) {
                    foreach (Request::getArray("course") as $course_id) {
                        if (isset($evasys_seminar[$course_id])) {
                            $evasys_seminar[$course_id]->store();
                        }
                    }
                    PageLayout::postMessage(MessageBox::success(sprintf(_("%s Veranstaltungen mit EvaSys synchronisiert."), count($activate))));
                } else {
                    PageLayout::postMessage(MessageBox::error(_("Fehler beim Synchronisieren mit EvaSys. ").$success));
                }
            } else {
                PageLayout::postMessage(MessageBox::info(_("Veranstaltungen abgewählt. Keine Synchronisation erfolgt.")));
            }
        }

        if (Request::get("inst") || Request::get("sem_type") || Request::get("sem_name") || Request::get("semester") || Request::get("sem_dozent")) {
            if (Request::get("semester")) {
                $semester = Semester::find(Request::option("semester"));
                $sem_condition = "AND seminare.start_time <=" . $semester["beginn"] . "
                                    AND (" . $semester["beginn"] . " <= (seminare.start_time + seminare.duration_time)
                                    OR seminare.duration_time = -1)";
            }
            if (Request::get("inst")) {
                $inst_condition = "AND seminar_inst.institut_id = " . $db->quote(Request::get("inst")) . " ";
            }

            if (Request::get("sem_type")) {
                $sem_type_condition = "AND seminare.status = " . $db->quote(Request::get("sem_type")) . " ";
            }
            if (Request::get("sem_dozent")) {
                $dozent_condition = "AND seminar_user.user_id = " . $db->quote(Request::get("sem_dozent")) . " AND seminar_user.status = 'dozent' ";
            }
            if (Request::get("sem_name")) {
                $name_condition = "AND CONCAT_WS(' ', seminare.VeranstaltungsNummer, seminare.Name) LIKE " . $db->quote('%' . Request::get("sem_name") . '%') . " ";
            }

            $this->courses = $db->query(
                "SELECT seminare.Name, seminare.Seminar_id, seminare.VeranstaltungsNummer, IFNULL(evasys_seminar.activated, 0) AS activated, seminare.start_time, seminare.duration_time, evasys_seminar.evasys_id, GROUP_CONCAT(seminar_user.user_id ORDER BY seminar_user.position ASC SEPARATOR '_') AS dozenten " .
                "FROM seminare " .
                "LEFT JOIN evasys_seminar ON (evasys_seminar.Seminar_id = seminare.Seminar_id) " .
                ($inst_condition ? "INNER JOIN seminar_inst ON (seminar_inst.seminar_id = seminare.Seminar_id) " : "") .
                "INNER JOIN seminar_user ON (seminar_user.Seminar_id = seminare.Seminar_id AND seminar_user.status = 'dozent') " .
                "WHERE TRUE  " .
                ($sem_condition ? $sem_condition : "") .
                ($inst_condition ? $inst_condition : "") .
                ($sem_type_condition ? $sem_type_condition : "") .
                ($name_condition ? $name_condition : "") .
                ($dozent_condition ? $dozent_condition : "") .
                "GROUP BY seminare.Seminar_id " .
                "ORDER BY Name ASC " .
                "")->fetchAll(PDO::FETCH_ASSOC);
            $this->searched = true;
        } else {
            $this->searched = false;
        }


        $this->institute = $db->query(
            "SELECT i2.* " .
            "FROM Institute AS i1 " .
            "INNER JOIN Institute AS i2 ON (i2.fakultaets_id = i1.Institut_id) " .
            "ORDER BY i1.Name ASC, i2.Name " .
        "")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function upload_courses_action()
    {
        if (Request::isPost()) {
            $activate = Request::getArray("activate");
            $evasys_seminar = array();
            foreach (Request::getArray("course") as $course_id) {
                $evasys_evaluations = EvaSysSeminar::findBySeminar($course_id);
                if (count($evasys_evaluations)) {
                    foreach ($evasys_evaluations as $evaluation) {
                        $evaluation['activated'] = $activate[$course_id] ? 1 : 0;
                        if (!$evaluation['activated']) {
                            $evaluation->store();
                            unset($evasys_seminar[$course_id]);
                        } else {
                            $evasys_seminar[$course_id] = $evaluation;
                        }
                    }
                } else {
                    $evasys_seminar[$course_id] = new EvaSysSeminar(array($course_id, ""));
                    $evasys_seminar[$course_id]['activated'] = $activate[$course_id] ? 1 : 0;
                }
            }
            if (count($evasys_seminar) > 0) {
                $success = EvaSysSeminar::UploadSessions($evasys_seminar);
                if ($success === true) {
                    foreach (Request::getArray("course") as $course_id) {
                        if (isset($evasys_seminar[$course_id])) {
                            $evasys_seminar[$course_id]->store();
                        }
                    }
                    PageLayout::postMessage(MessageBox::success(sprintf(_("%s Veranstaltungen mit EvaSys synchronisiert."), count($activate))));
                } else {
                    PageLayout::postMessage(MessageBox::error(_("Fehler beim Synchronisieren mit EvaSys. ").$success));
                }
            } else {
                PageLayout::postMessage(MessageBox::info(_("Veranstaltungen abgewählt. Keine Synchronisation erfolgt.")));
            }
        }
        $this->redirect(URLHelper::getURL("dispatch.php/admin/courses/index"));
    }

}