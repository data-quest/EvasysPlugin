<?php

require_once 'app/controllers/plugin_controller.php';

class EvaluationController extends PluginController
{

    public function show_action()
    {
        $tab = Navigation::getItem("/course/evasys");
        $tab->setImage(Assets::image_path("icons/16/black/evaluation"));

        PageLayout::addScript($this->plugin->getPluginURL()."/assets/qrcode.js");

        $evasys_seminars = EvaSysSeminar::findBySeminar($_SESSION['SessionSeminar']);
        $this->surveys = array();
        $this->open_surveys = array();
        $active = array();
        $user_can_participate = array();
        $publish = false;
        if (Request::get("dozent_vote")) {
            foreach ($evasys_seminars as $evasys_seminar) {
                $evasys_seminar->vote(Request::get("dozent_vote") === "y");
            }
        }
        foreach ($evasys_seminars as $evasys_seminar) {
            $survey_information = $evasys_seminar->getSurveyInformation();
            $publish = $publish || $evasys_seminar->publishingAllowed();
            if (is_array($survey_information)) {
                foreach ($survey_information as $info) {
                    $this->surveys[] = $info;
                    if ($info->m_nState > 0) {
                        $active[] = count($this->surveys) - 1;
                    }
                }
            }
        }
        if (count($evasys_seminars)
            && !$GLOBALS['perm']->have_studip_perm("dozent", $_SESSION['SessionSeminar'])) {
            $this->open_surveys = $evasys_seminars[0]->getSurveys($GLOBALS['user']->id);
            if (is_array($this->open_surveys)) {
                foreach ($this->open_surveys as $one) {
                    if (is_object($one)) {
                        $user_can_participate[] = count($this->open_surveys) - 1;
                        break;
                    }
                }
            }
        }

        if ($GLOBALS['perm']->have_studip_perm("dozent", $_SESSION['SessionSeminar'])
            || (count($active) > 0 && $publish && !count($this->open_surveys))) {
            $this->evasys_seminar = $evasys_seminar;
            $this->render_template("evaluation/survey_dozent", $GLOBALS['template_factory']->open("layouts/base"));
        } else {
            if ($user_can_participate) {
                unset($_SESSION['EVASYS_SEMINAR_SURVEYS'][$_SESSION['SessionSeminar']]);
            }
            $this->render_template("evaluation/survey_student", $GLOBALS['template_factory']->open("layouts/base"));
        }
    }

}
