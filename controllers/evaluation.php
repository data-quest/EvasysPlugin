<?php

class EvaluationController extends PluginController
{

    public function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        $tab = Navigation::getItem("/course/evasys");
        $tab->setImage(Icon::create("evaluation", "info"));
        Navigation::activateItem("/course/evasys");
        $this->profile = EvasysCourseProfile::findBySemester(Context::get()->id);
        if ($this->profile->isNew()) {
            $profile = EvasysCourseProfile::findOneBySQL("seminar_id = ?", [Context::get()->id]);
            if ($profile) {
                $this->profile = $profile;
            }
        }
        PageLayout::addScript($this->plugin->getPluginURL()."/assets/qrcode.js");
        PageLayout::setTitle(dgettext("evasys", "Lehrveranstaltungsevaluation mit EvaSys"));
    }

    public function show_action()
    {
        $this->profiles = EvasysCourseProfile::findBySQL("INNER JOIN semester_data USING (semester_id) WHERE seminar_id = ? ORDER BY semester_data.beginn DESC ", [Context::get()->id]);
        $this->evasys_seminar = new EvasysSeminar(Context::get()->id);
    }

    public function toggle_publishing_action()
    {
        if (Request::get("dozent_vote") && !$GLOBALS['perm']->have_perm("admin")) {
            $evasys_seminar = EvasysSeminar::find(Context::get()->id);
            if (!$evasys_seminar) {
                $evasys_seminar = new EvasysSeminar(Context::get()->id);
                $evasys_seminar['activated'] = 1;
                $evasys_seminar->store();
            }
            $evasys_seminar->allowPublishing(Request::get("dozent_vote") === "y");
        }
        $this->redirect("evaluation/show");
    }

}
