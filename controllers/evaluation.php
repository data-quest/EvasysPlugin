<?php

class EvaluationController extends PluginController
{

    public function show_action()
    {
        $tab = Navigation::getItem("/course/evasys");
        $tab->setImage(Icon::create("evaluation", "info"));

        PageLayout::addScript($this->plugin->getPluginURL()."/assets/qrcode.js");

        $profile = EvasysCourseProfile::findBySemester(Context::get()->id);
        if (Config::get()->EVASYS_ENABLE_PROFILES && $profile['split']) {
            $evasys_seminars = array();
            if ($profile['teachers']) {
                foreach ($profile['teachers'] as $dozent_id) {
                    $teachers = $profile['teachers']->getArrayCopy();
                }
            } else {
                $teachers = $db->query(
                    "SELECT seminar_user.user_id " .
                    "FROM seminar_user " .
                    "WHERE seminar_user.Seminar_id = ".$db->quote(Context::get()->id)." " .
                    "AND seminar_user.status = 'dozent' " .
                    "ORDER BY seminar_user.position ASC " .
                "")->fetchAll(PDO::FETCH_COLUMN, 0);
            }
            foreach ($teachers as $dozent_id) {
                $evasys_seminars = array_merge(
                    $evasys_seminars,
                    EvasysSeminar::findBySeminar(Context::get()->id . $dozent_id)
                );
            }
            //TODO maybe we do something different here and not use EvasysSeminar::findBySeminar or change getSurveyInformation?
        } else {
            $evasys_seminars = EvasysSeminar::findBySeminar(Context::get()->id);
        }

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
            && !$GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id)) {
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

        if ($GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id)
            || (count($active) > 0 && $publish && !count($this->open_surveys))) {
            $this->evasys_seminar = $evasys_seminar;
            $this->render_template("evaluation/survey_dozent", $GLOBALS['template_factory']->open("layouts/base"));
        } else {
            if ($user_can_participate) {
                unset($_SESSION['EVASYS_SEMINAR_SURVEYS'][Context::get()->id]);
            }
            $this->render_template("evaluation/survey_student", $GLOBALS['template_factory']->open("layouts/base"));
        }
    }

}
