<?php

class AdminController extends PluginController
{

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!EvasysPlugin::isRoot() && !EvasysPlugin::isAdmin()) {
            throw new AccessDeniedException();
        }
    }

    public function upload_courses_action()
    {
        if (Request::isPost() && $GLOBALS['perm']->have_perm(Config::get()->EVASYS_TRANSFER_PERMISSION)) {
            $activate = Request::getArray("c");
            $evasys_seminar = [];

            $courses = array_map(function ($i) {
                $id = explode("_", $i); return $id[0];
                }, array_keys(Request::getArray("c")));
            $courses = array_unique($courses);
            foreach ($courses as $course_id) {
                $evasys_evaluation = EvasysSeminar::findBySeminar($course_id);
                if ($evasys_evaluation) {
                    $evasys_evaluation['activated'] = $activate[$course_id] ? 1 : 0;
                    if (!$evasys_evaluation['activated']) {
                        $evasys_evaluation->store();
                        unset($evasys_seminar[$course_id]);
                    } else {
                        $evasys_seminar[$course_id] = $evasys_evaluation;
                    }
                } else {
                    $evasys_seminar[$course_id] = new EvasysSeminar($course_id);
                    $evasys_seminar[$course_id]['activated'] = $activate[$course_id] ? 1 : 0;
                }
                if ($evasys_seminar[$course_id]) {
                    $evasys_seminar[$course_id]->store();
                }
            }

            if (!empty($evasys_seminar)) {
                $success = EvasysSeminar::UploadSessions($evasys_seminar);
                if ($success === true) {
                    foreach ($courses as $course_id) {
                        if (isset($evasys_seminar[$course_id])) {
                            $evasys_seminar[$course_id]->store();
                        }
                        try {
                            StudipLog::log(
                                'EVASYS_EVAL_TRANSFER',
                                $GLOBALS['user']->id,
                                $evasys_seminar[$course_id]['seminar_id'],
                                $evasys_seminar[$course_id]['seminar_id']
                            );
                        } catch (Exception $e) {
                            var_dump($evasys_seminar[$course_id]['seminar_id']);
                            die();
                        }
                    }
                    PageLayout::postMessage(MessageBox::success(sprintf(dgettext("evasys", "%s Veranstaltungen mit EvaSys synchronisiert."), count($activate))));
                } else {
                    PageLayout::postMessage(MessageBox::error(dgettext("evasys", "Fehler beim Synchronisieren mit EvaSys. ").$success));
                }
            } else {
                PageLayout::postMessage(MessageBox::info(dgettext("evasys", "Veranstaltungen abgewählt. Keine Synchronisation erfolgt.")));
            }
        }
        $this->redirect(URLHelper::getURL("dispatch.php/admin/courses/index"));
    }

}
