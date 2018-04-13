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

            $this->profile['user_id'] = $GLOBALS['user']->id;

            $this->profile->store();
            PageLayout::postSuccess(_("Daten wurden gespeichert."));
            $this->response->add_header("X-Dialog-Execute", json_encode(array(
                'func' => "STUDIP.EVASYS.refreshCourseInOverview",
                'payload' => $course_id
            )));
        }
    }

}