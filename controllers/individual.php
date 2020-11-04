<?php

class IndividualController extends PluginController
{
    protected $max_list_items = 50;

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if ((!EvasysPlugin::isRoot() && RolePersistence::isAssignedRole($GLOBALS['user']->id, "Evasys-Admin"))
                || !Config::get()->EVASYS_ENABLE_PROFILES) {
            throw new AccessDeniedException();
        }
    }



    public function list_action()
    {
        PageLayout::setTitle(ucfirst(EvasysMatching::wording("freiwillige Evaluationen")));
        Navigation::activateItem("/admin/evasys/individual");
        $this->semester = Request::option("semester_id")
            ? Semester::find(Request::option("semester_id"))
            : Semester::findCurrent();
        $this->profiles = EvasysCourseProfile::findBySQL("INNER JOIN seminare ON (seminare.Seminar_id = evasys_course_profiles.seminar_id)
            WHERE by_dozent = '1'
            AND applied = '1'
            AND semester_id = :semester_id
            ORDER BY mkdate DESC
            LIMIT ".($this->max_list_items + 1)."
        ", array('semester_id' => $this->semester->getId()));
        if (count($this->profiles) > $this->max_list_items) {
            array_pop($this->profiles);
            $this->more = true;
        }
    }

    public function more_action()
    {
        $semester_id = Request::option("semester_id", Semester::findCurrent()->id);
        $this->profiles = EvasysCourseProfile::findBySQL("INNER JOIN seminare ON (seminare.Seminar_id = evasys_course_profiles.seminar_id)
            WHERE by_dozent = '1'
            AND applied = '1'
            ORDER BY mkdate DESC
            LIMIT ".Request::int("offset", 0).", ".($this->max_list_items + 1)."
        ", array('semester_id' => $semester_id));
        $output = array();
        if (count($this->profiles) > $this->max_list_items) {
            array_pop($this->profiles);
            $output['more'] = true;
        }
        $tf = $this->get_template_factory();
        $template = $tf->open("admin/_individuell.php");
        foreach ($this->profiles as $profile) {
            $template->profile = $profile;
            $output['profiles'][] = $template->render();
        }
        $this->render_json($output);
    }

    public function course_action($course_id)
    {
        $this->profile = EvasysCourseProfile::findBySemester($course_id);
    }

    public function csv_action()
    {
        $semester_id = Request::option("semester_id", Semester::findCurrent()->id);
        $this->profiles = EvasysCourseProfile::findBySQL("
            by_dozent = '1'
            AND applied = '1'
            AND semester_id = :semester_id
            ORDER BY mkdate DESC
        ", array('semester_id' => $semester_id));
        $caption = array(
            "Anrede",
            "Titel",
            "Vorname",
            "Nachname",
            "Emailadresse",
            "Telefonnummer",
            "Fachbereich",
            "Institut/Zentrum",
            "Straße&Nr.",
            "PLZ",
            "Ort",
            "Lehrveranstaltung",
            "Veranstaltungsnummer",
            "Raum/Ort der VA",
            "Seminar_id",
            "Studiengang",
            "Veranstaltungstyp",
            "Teilnehmer",
            "Hinweise",
            "Fragebogen",
            "Weitere Empfänger"
        );
        $data = array();
        foreach ($this->profiles as $profile) {
            $teachers = $profile['teachers']->getArrayCopy();
            if (empty($teachers)) {
                $seminar = new Seminar($profile['seminar_id']);
                $teachers = array_map(function ($dozent) { return $dozent['user_id']; }, $seminar->getMembers("dozent"));
            }
            foreach ($teachers as $teacher_id) {
                $user = new User($teacher_id);
                $data[] = array(
                    ($user['geschlecht'] == 1 ? "Frau" : ($user['geschlecht'] == 2 ? "Herr" : "")),
                    $user['title_front'],
                    $user['vorname'],
                    $user['nachname'],
                    $user['email'],
                    $user['home'],
                    $user->institute_memberships[0] ? $user->institute_memberships[0]->institute->faculty['name'] : "", //Fachbereich
                    $user->institute_memberships[0] ? $user->institute_memberships[0]->institute['name'] : "", //Institut,
                    $user->institute_memberships[0] ? $user->institute_memberships[0]->institute['strasse'] : "", //Straße&Nr
                    $user->institute_memberships[0] ? $user->institute_memberships[0]->institute['plz'] : "",
                    "Ort",
                    $profile->course['name'],
                    $profile->course['veranstaltungsnummer'],
                    $profile->course['ort'],
                    $profile->course->getId(),
                    "Studiengänge",
                    $profile->course['status'],
                    $profile->getFinalTeilnehmer(),
                    $profile['hinweis'],
                    EvasysForm::find($profile->getFinalFormId())->name,
                    implode("|", $profile->getFinalResultsEmails())
                );
            }
        }

        $this->response->add_header("Content-Type", "text/csv");
        $this->response->add_header("Content-Disposition", "attachment; filename=\"Anmeldungen.csv\"");
        $this->render_text(array_to_csv($data, null, $caption));
    }

}
