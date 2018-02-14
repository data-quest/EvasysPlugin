<?php

require_once 'app/controllers/plugin_controller.php';

class AdminController extends PluginController {

    public function index_action()
    {
        Navigation::activateItem("/admin/evasys");
        PageLayout::setTitle($this->plugin->getDisplayName());

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