<?php

require_once dirname(__file__)."/EvasysSoap.php";
require_once dirname(__file__)."/EvasysSoapClient.php";

class EvasysSeminar extends SimpleORMap
{

    protected $db_table = 'evasys_seminar';

    protected static function configure($config = [])
    {
        $config['db_table'] = 'evasys_seminar';
        $config['belongs_to']['course'] = [
            'class_name' => 'Course',
            'foreign_key' => 'seminar_id'
        ];
        $config['serialized_fields']['publishing_allowed_by_dozent'] = "JSONArrayObject";
        parent::configure($config);
    }

    static public function findBySeminar($course_id)
    {
        return self::findOneBySQL("Seminar_id = ".DBManager::get()->quote($course_id));
    }

    /**
     * Fetches all new evaluations (red icon) for the user.
     * @param string|null $user_id of the given user or null for current user
     * @return integer : number of new surveys
     */
    public function getEvaluationStatus($user_id = null)
    {
        if (Config::get()->EVASYS_RED_ICONS_STOP_UNTIL > 0) {
            if (Config::get()->EVASYS_RED_ICONS_STOP_UNTIL > time()) {
                return 0;
            } else {
                Config::get()->store("EVASYS_RED_ICONS_STOP_UNTIL", 0);
            }
        }
        $user = $user_id ? User::find($user_id) : User::findCurrent();
        if ($GLOBALS['perm']->have_perm("admin", $user->getId())) {
            return 0;
        }
        $profile = EvasysCourseProfile::findBySemester($this->getId());
        $id = $this->getExportedId();
        if (Config::get()->EVASYS_ENABLE_SPLITTING_COURSES && $profile['split']) {
            $seminar_ids = [];
            foreach ($profile['teachers'] as $dozent_id) {
                $seminar_ids[] = $id . $dozent_id;
            }
        } else {
            $seminar_ids = [$id];
        }
        if (isset($_SESSION['EVASYS_SEMINARS_STATUS'])
                && (time() - $_SESSION['EVASYS_STATUS_EXPIRE']) < 60 * Config::get()->EVASYS_CACHE) {
            $new = 0;
            foreach ($seminar_ids as $seminar_id) {
                $new += $_SESSION['EVASYS_SEMINARS_STATUS'][$seminar_id] ?? 0;
            }
            return $new;
        }
        $_SESSION['EVASYS_SEMINARS_STATUS'] = [];
        $soap = EvasysSoap::get();
        $start_time = microtime(true);
        // soapCall with socket timeout of 10
        $output_headers = null;
        $evasys_sem_object = $soap->soapCall(
            "GetEvaluationSummaryByParticipant",
            [$user['email']],
            null,
            null,
            $output_headers,
            10
        );
        $end_time = microtime(true);
        if (is_a($evasys_sem_object, "SoapFault")) {
            if ($end_time - $start_time >= 10) {
                //maybe another process has written something into config_values before we want to do that:
                $query = "SELECT config.field, IFNULL(config_values.value, config.value) AS value, type, section, `range`, description,
                                 config_values.comment, config_values.value IS NULL AS is_default
                          FROM config
                              LEFT JOIN config_values ON (config.field = config_values.field AND range_id = 'studip')
                          WHERE config.field = 'EVASYS_RED_ICONS_STOP_UNTIL'";
                $statement = DBManager::get()->prepare($query);
                $statement->execute();
                $config_value = $statement->fetch(PDO::FETCH_ASSOC);
                if ($config_value['value'] > 0) {
                    return 0;
                }
                Config::get()->store("EVASYS_RED_ICONS_STOP_UNTIL", time() + 60 * 30);
                $roots = User::findBySQL("perms = 'root'");
                $messaging = new messaging();
                $messaging->insert_message(
                    "Das Abrufen der Informationen zu neuen Befragungen von Studierenden hat zu lange gedauert (10 Sekunden oder länger) und wurde für eine halbe Stunde deaktiviert.",
                    array_map(function ($u) { return $u->username; }, $roots),
                    '____%system%____',
                    '',
                    '',
                    '',
                    '',
                    'EvaSys: Abrufen von Daten aus EvaSys auf Meine Veranstaltungen ist zu langsam',
                    '',
                    'normal',
                    ["EvaSys", "Rote Icons"]
                );
                return 0;
            }
            if ($evasys_sem_object->getMessage() === "ERR_212") {
                $_SESSION['EVASYS_SEMINARS_STATUS'] = [];
            } else {
                $message = "SOAP-error: " . $evasys_sem_object->getMessage()
                    . ((is_string($evasys_sem_object->detail) || (is_object($evasys_sem_object->detail) && method_exists($evasys_sem_object->detail, "__toString")))
                        ? " (" . $evasys_sem_object->detail . ")"
                        : "");
                PageLayout::postError($message);
                return 0;
            }
        } else {
            foreach ((array) $evasys_sem_object->SurveySummary as $survey) {
                if (!$survey->Participated && $survey->SurveyOpenState) {
                    if (isset($_SESSION['EVASYS_SEMINARS_STATUS'][$survey->SurveyCourseCode])) {
                        $_SESSION['EVASYS_SEMINARS_STATUS'][$survey->SurveyCourseCode] += 1;
                    } else {
                        $_SESSION['EVASYS_SEMINARS_STATUS'][$survey->SurveyCourseCode] = 1;
                    }
                }
            }
        }
        $_SESSION['EVASYS_STATUS_EXPIRE'] = time();
        $new = 0;
        foreach ($seminar_ids as $seminar_id) {
            $new += $_SESSION['EVASYS_SEMINARS_STATUS'][$seminar_id] ?? 0;
        }
        return $new;
    }


    /**
     * Uploads all given seminars in one soap-call to EvaSys.
     * @param array $seminars : array of EvasysSeminar
     */
    static public function UploadSessions(array $seminars)
    {
        $soap = EvasysSoap::get();
        $courses = [];
        $semester_id = $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE;
        foreach($seminars as $seminar) {
            $part = $seminar->getCoursePart();
            if ($part && $part[0] !== "delete") {
                if ($part['CourseName']) {
                    //single course with data
                    if (!$part['CourseUid']) {
                        PageLayout::postError(sprintf(dgettext("evasys", "Veranstaltung %s hat keine ID und kann daher nicht übertragen werden."), $seminar->course['name']));
                    } else {
                        $courses[] = $part;
                    }
                } else {
                    //we have split courses for each teacher
                    foreach ($part as $subcourse) {
                        if (!$subcourse['CourseUid']) {
                            PageLayout::postError(sprintf(dgettext("evasys", "Veranstaltung %s hat keine ID und kann daher nicht übertragen werden."), $seminar->course['name']));
                        } else {
                            $courses[] = $subcourse;
                        }
                    }
                    //try to delete a course-evaluation if we have a split course
                    $soap->soapCall("DeleteCourse", [
                        'CourseId' => $seminar->getExportedId(),
                        'IdType' => "PUBLIC"
                    ]);
                }

            } elseif($part[0] === "delete") {
                //we need to delete the course from evasys
                foreach ($part[1] as $seminar_id) {
                    $soap->soapCall("DeleteCourse", [
                        'CourseId' => $seminar_id,
                        'IdType' => "PUBLIC"
                    ]);
                    $profile = EvasysCourseProfile::findBySemester(
                        $seminar['Seminar_id'],
                        $semester_id
                    );
                    if (!$profile->isNew()) {
                        $profile['transferred'] = 0;
                        $profile['transferdate'] = time();
                        $profile['chdate'] = time();
                        $profile->store();
                    }
                }
            }
        }
        if (empty($courses)) {
            //nothing to insert, we probably have only deleted something
            return true;
        }
        $sessionlist = [
            ['CourseCreators' => $courses],
            true
        ];
        $evasys_sem_object = $soap->soapCall("InsertCourses", $sessionlist);
        if (is_a($evasys_sem_object, "SoapFault")) {
            if (method_exists($evasys_sem_object, "getMessage")) {
                if ($evasys_sem_object->getMessage() == "Not Found") {
                    return "SoapPort der WSDL-Datei antwortet nicht.";
                } else {
                    return "SOAP-error: " . $evasys_sem_object->getMessage()
                        . ((is_string($evasys_sem_object->detail) || (is_object($evasys_sem_object->detail) && method_exists($evasys_sem_object->detail, "__toString")))
                            ? " (" . $evasys_sem_object->detail . ")"
                            : "");
                }
            } else {
                return "SOAP-error: ".print_r($evasys_sem_object, true);
            }

        } else {

            //Speichern der survey_ids, sodass wir beim nächsten Mal die alten Survey_ids mitgeben können.
            $uploadStatus = $evasys_sem_object->UploadStatus;
            foreach ((array) $uploadStatus as $status) {

                $course_id = self::getCourseIdByUID($status->CourseUid);

                //$status->StatusMessage;
                $profile = EvasysCourseProfile::findBySemester(
                    $course_id,
                    $semester_id
                );
                if ($status->StatusId === "ERR_108") {
                    PageLayout::postError(sprintf(
                        dgettext("evasys", "Die 'Veranstaltung '%s' konnte nicht korrekt übertragen werden."),
                        Course::find($course_id)->name
                    ), [$status->StatusMessage]);
                    $profile['transferred'] = 0;
                    $profile['transferdate'] = time();
                    $profile['chdate'] = time();
                } else {
                    $profile['transferred'] = 1;
                    $profile['transferdate'] = time();
                    if ($profile->lockAfterTransferForRole()) {
                        $profile['locked'] = 1;
                    } else {
                        $profile['locked'] = 0;
                    }
                    $profile['chdate'] = time();
                }
                if (!$profile->isNew()) {
                    foreach ($status->SurveyStatusList->SurveyStatusArray as $survey_status) {
                        if ($survey_status->SurveyId) {
                            if (!$profile['surveys']) {
                                $profile['surveys'] = [$status->CourseUid => $survey_status->SurveyId];
                            } else {
                                $profile['surveys'][$status->CourseUid] = $survey_status->SurveyId;
                            }
                        }
                    }
                    $profile['surveys']['form_id'] = $profile->getFinalFormId();
                }
                $profile->store();
            }
            return true;
        }
    }

    static public function getCourseIdByUID($uid)
    {
        if (strlen($uid) > 32) {
            $uid = substr($uid, 0, strlen($uid) - 32);
        }
        switch (Config::get()->EVASYS_COURSE_IDENTIFIER) {
            case "seminar_id":
                $course_id = $uid;
                break;
            case "number":
                $course = Course::findOneBySQL("VeranstaltungsNummer = ?", [$uid]);
                $course_id = $course->getId();
                break;
            default: //Datenfeld:
                $course = Course::findOneBySQL("INNER JOIN datafields_entries ON (datafields_entries.range_id = seminare.Seminar_id) WHERE datafields_entries.content = :uid AND datafields_entries.datafield_id = :datafield_id ", [
                    'uid' => $uid,
                    'datafield_id' => Config::get()->EVASYS_COURSE_IDENTIFIER
                ]);
                $course_id = $course->getId();
                break;
        }
        return $course_id;
    }

    public function getExportedId()
    {
        switch (Config::get()->EVASYS_COURSE_IDENTIFIER) {
            case "seminar_id":
                $id = $this['Seminar_id'];
                break;
            case "number":
                $id = empty($this['VeranstaltungsNummer']) ? $this['Seminar_id'] : $this['VeranstaltungsNummer'];
                break;
            default: //Datenfeld:
                $datafield_entry = DatafieldEntryModel::findByModel($this->course, Config::get()->EVASYS_COURSE_IDENTIFIER);
                $id = empty($datafield_entry[0]['content']) ? $this['Seminar_id'] : $datafield_entry[0]['content'];
                break;
        }
        return $id;
    }

    public function getCoursePart()
    {
        $db = DBManager::get();
        $course = new Course($this['Seminar_id']);
        $id = $this->getExportedId();
        $profile = EvasysCourseProfile::findBySemester(
            $this['Seminar_id'],
            ($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE && $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all"
                ? $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE
                : Semester::findCurrent()->id)
        );
        if (!$profile['applied'] && !$profile['split']) {
            return $profile['transferred']
                ? ["delete", [$id]]
                : null; //course should be deleted from evasys database
        }

        $participants = [];

        $user_permissions = Config::get()->EVASYS_PLUGIN_PARTICIPANT_ROLES;
        $user_permissions = preg_split("/\s/", $user_permissions, -1, PREG_SPLIT_NO_EMPTY);

        $statement = DBManager::get()->prepare("
            SELECT auth_user_md5.user_id
            FROM auth_user_md5
                INNER JOIN seminar_user ON (seminar_user.user_id = auth_user_md5.user_id)
            WHERE seminar_user.Seminar_id = :seminar_id
                AND seminar_user.status IN ( :user_permissions )
        ");
        $statement->execute([
            'seminar_id' => $this['Seminar_id'],
            'user_permissions' => $user_permissions
        ]);
        $students = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($students as $student_id) {
            $student = User::find($student_id);
            $participants[] = [
                'm_nId' => "",
                'm_sTitle' => "",//$student['title_front'],
                'm_sIdentifier' => $student['email'],
                'm_sEmail' => $student['email'],
                'm_sFirstname' => "", //$student['Vorname'],
                'm_sLastname' => "", //$student['Nachname'],
                'm_nGender' => "", //$student['geschlecht'] == 1 ? "m" : "w",
                'm_sAddress' => "",
                'm_sCustomFieldsJSON' => "",
            ];
        }

        $stmt = DBManager::get()->prepare("
            SELECT DISTINCT sem_tree.sem_tree_id
            FROM seminar_sem_tree
                INNER JOIN sem_tree ON (seminar_sem_tree.sem_tree_id = sem_tree.sem_tree_id)
            WHERE seminar_sem_tree.seminar_id = ?
            ORDER BY sem_tree.name ASC
        ");
        $stmt->execute([$this['Seminar_id']]);
        $studienbereiche = [];
        $study_areas = StudipStudyArea::findMany($stmt->fetchAll(PDO::FETCH_COLUMN, 0));
        foreach ($study_areas as $studyarea) {
            switch (Config::get()->EVASYS_STUDYAREA_MATCHING) {
                case 'name':
                    if ($studyarea['name']) {
                        $studienbereiche[] = $studyarea['name'];
                    }
                    break;
                case 'info':
                    if ($studyarea['info']) {
                        $studienbereiche[] = $studyarea['info'];
                    }
                    break;
                default:
                case 'path':
                    $path = $studyarea->getPath(" » ");
                    if ($path) {
                        $studienbereiche[] = $path;
                    }
                    break;
            }

        }

        //TODO:
        $datafields = DataField::getDataFields('sem');
        $custom_fields = [
            '1' => $course['veranstaltungsnummer'],
            '2' => "" //Anzahl der Bögen ?
        ];
        $i = 3;
        foreach ($datafields as $datafield) {
            $datafield_entry = DatafieldEntryModel::findOneBySQL('datafield_id = :datafield_id AND range_id = :course_id', [
                'datafield_id' => $datafield->getId(),
                'course_id' => $course->getId()
            ]);
            $custom_fields[$i] = $datafield_entry ? (string) $datafield_entry['content'] : $datafield['default_value'];
            $i++;
        }
        $surveys = [];

        $form_id = $profile->getFinalFormId();
        if ($profile['applied'] && $profile['surveys']['form_id']) {
            //We have transferred the course before and want to update it. Unfortunately in order to update the
            //participants and/or the form_id  of a survey, we need to delete the survey. Sad but true.

            //UpdateSurvey
            if (!$profile['split']) {
                $seminar_ids = [$this['Seminar_id']];
            } else {
                if ($profile['surveys']) {
                    $seminar_ids = $profile['surveys']->getArrayCopy();
                    $seminar_ids = array_keys($seminar_ids);
                } else {
                    $seminar_ids = [];
                }
            }

            $eval_begin = $profile->getFinalBegin();
            if (time() < $eval_begin) {
                //The survey didn't start yet (as far as we know), so we can go on:
                foreach ($seminar_ids as $seminar_id) {
                    if ($seminar_id !== "form_id") {
                        $survey_id = $profile['surveys'] && $profile['surveys'][$seminar_id]
                            ? $profile['surveys'][$seminar_id]
                            : false;
                        if ($survey_id) {
                            $soap = EvasysSoap::get();
                            $soap->soapCall("DeleteSurvey", [
                                'SurveyId' => (int) $survey_id
                            ]);
                        }
                    }
                }
            } else {
                PageLayout::postError(sprintf(
                    dgettext("evasys", "Evaluation für die Veranstaltung '%s' ist schon gestartet und konnte nicht mehr verändert werden."),
                    $course['name']
                ));
            }
        }

        $start_time = $profile->getFinalBegin();
        if ($start_time < time()) {
            $start_time = time() + 60 * 30;
        }
        $end_time = $profile->getFinalEnd() + $profile->getPresetAttribute("send_report_delay");
        if ($end_time <= time()) {
            $end_time = time() + 60 * 60 * 2 + $profile->getPresetAttribute("send_report_delay");
        }

        $surveys[] = [
            'FormId' => $form_id,
            'FormIdType' => "INTERNAL",
            'SurveyID' => $profile['surveys'] && $profile['surveys'][$this['Seminar_id']]
                ? $profile['surveys'][$this['Seminar_id']]
                : "", //experimental
            'PeriodId' => date("Y-m-d", $profile->getFinalBegin()),
            'PeriodIdType' => Config::get()->EVASYS_MATCH_INNER_PERIOD ? "INNER_ACTIVE_PERIOD_ON_DATE" : "PERIODDATE", //ab v91 INNER_ACTIVE_PERIOD_ON_DATE
            'SurveyType' => [
                'm_chSurveyType' => ($profile['mode'] === "paper" && !Config::get()->EVASYS_FORCE_ONLINE)
                    ? $profile->getPresetAttribute("paper_mode") // d = Deckblatt, s = Selbstdruck
                    : "o", // o = online+TAN
                           // was für Losungsbasiert?
                'm_sDescription' => ""
            ],
            'Verification' => false,
            'Notice' => "",
            'FormRecipientList' => [], //Emails, an die die PDF des Fragebogens verschickt wird, aber wie oft soll er ausdrucken??
            'InviteParticipants' => false,
            'InvitationTask' => [
                'SurveyID' => $profile['surveys'] && $profile['surveys'][$this['Seminar_id']]
                    ? $profile['surveys'][$this['Seminar_id']]
                    : "",
                'StartTime' => date("c", $start_time),
                //'sendInstructorNotification' => true, // Template kann nicht überschrieben werden.
                'EmailSubject' => "###PREVENT_DISPATCH###" //Keine Mail an die Studierenden mit den TANs senden
            ],
            'CloseTask' => [
                'SurveyID' => $profile['surveys'] && $profile['surveys'][$this['Seminar_id']]
                    ? $profile['surveys'][$this['Seminar_id']]
                    : "",
                'StartTime' => date("c", $end_time),
                'SendReport' => ($profile->getPresetAttribute("send_report") === 'yes')
            ],
            'SerialPrint' => false
        ];

        $dozenten = $db->query(
            "SELECT seminar_user.user_id " .
            "FROM seminar_user " .
            "WHERE seminar_user.Seminar_id = ".$db->quote($this['Seminar_id'])." " .
            "AND seminar_user.status = 'dozent' " .
            "ORDER BY seminar_user.position ASC " .
            "")->fetchAll(PDO::FETCH_COLUMN, 0);

        if ($profile['applied'] && $profile['split']) {
            //we split this course into one course for each teacher.
            $parts = [];

            foreach ($dozenten as $dozent_id) {
                if (!$profile['teachers'] || in_array($dozent_id, $profile['teachers'] ? $profile['teachers']->getArrayCopy() : [])) {
                    $instructorlist = [];

                    $instructorlist[] = $this->getInstructorPart($dozent_id);
                    foreach ($profile->getFinalResultsEmails() as $email) {
                        $instructorlist[] = $this->getInstructorPart($email, true);
                    }

                    $surveys2 = $surveys;
                    foreach ($surveys2 as $i => $survey) {
                        if ($profile['surveys'] && $profile['surveys'][$this['Seminar_id'] . $dozent_id]) {
                            $surveys2[$i]['SurveyID'] = $profile['surveys'][$this['Seminar_id'] . $dozent_id]; //experimental
                            $surveys2[$i]['InvitationTask']['SurveyID'] = $profile['surveys'][$this['Seminar_id'] . $dozent_id];
                            $surveys2[$i]['CloseTask']['SurveyID'] = $profile['surveys'][$this['Seminar_id'] . $dozent_id];
                        }
                    }

                    $parts[] = [
                        'CourseUid' => $id . $dozent_id,
                        'CourseName' => mb_substr($course['name'], 0, 199),
                        'CourseCode' => $id . $dozent_id,
                        'CourseType' => mb_substr(EvasysMatching::semtypeName($course->status), 0, 49),
                        'CourseProgramOfStudy' => implode(' | ', $studienbereiche),
                        'CourseEnrollment' => 0, // ?
                        'CustomFieldsJSON' => json_encode($custom_fields),
                        'CoursePeriodId' => date("Y-m-d", $course->start_semester['beginn']),
                        'CoursePeriodIdType' => Config::get()->EVASYS_MATCH_INNER_PERIOD ? "INNER_ACTIVE_PERIOD_ON_DATE" : "PERIODDATE", //ab v91 INNER_ACTIVE_PERIOD_ON_DATE
                        'InstructorList' => $instructorlist,
                        'RoomName' => (string) $course->ort,
                        'SubunitName' => $profile['objection_to_publication'] && ($profile->getPresetAttribute('enable_objection_to_publication') === 'yes') && $profile->getPresetAttribute('objection_teilbereich')
                            ? $profile->getPresetAttribute('objection_teilbereich')
                            : (string) EvasysMatching::instituteName($course->institut_id),
                        'ParticipantList' => $participants,
                        'AnonymousParticipants' => true,
                        'SurveyCreatorList' => $surveys2,
                    ];
                }
            }
            return $parts;

        } elseif(!$profile['applied'] && $profile['split']) {
            //we need to delete all former sub-courses
            if ($profile['transferred']) {
                $ids = [];
                foreach ($dozenten as $dozent_id) {
                    $ids[] = $id.$dozent_id;
                }
                return ["delete", $ids];
            } else {
                return null;
            }
        } else {
            //we just want to import/update this course
            $instructorlist = [];
            $instructors = [];
            if ($profile['teachers']) {
                foreach ($profile['teachers'] as $dozent_id) {
                    $instructors[] = $dozent_id;
                    $instructorlist[] = $this->getInstructorPart($dozent_id);
                }
            } else {
                foreach ($dozenten as $dozent_id) {
                    $instructors[] = $dozent_id;
                    $instructorlist[] = $this->getInstructorPart($dozent_id);
                }
            }
            foreach ($profile->getFinalResultsEmails() as $email) {
                $instructorlist[] = $this->getInstructorPart($email, true);
            }

            return [
                'CourseUid' => $id,
                'CourseName' => mb_substr($course['name'], 0, 199),
                'CourseCode' => $id,
                'CourseType' => mb_substr(EvasysMatching::semtypeName($course->status), 0, 49),
                'CourseProgramOfStudy' => implode(' | ', $studienbereiche),
                'CourseEnrollment' => 0, // ?
                'CustomFieldsJSON' => json_encode($custom_fields),
                'CoursePeriodId' => date("Y-m-d", $course->start_semester['beginn']),
                'CoursePeriodIdType' => Config::get()->EVASYS_MATCH_INNER_PERIOD ? "INNER_ACTIVE_PERIOD_ON_DATE" : "PERIODDATE", //ab v91 INNER_ACTIVE_PERIOD_ON_DATE
                'InstructorList' => $instructorlist,
                'RoomName' => (string) $course->ort,
                'SubunitName' => $profile['objection_to_publication'] && ($profile->getPresetAttribute('enable_objection_to_publication') === 'yes') && $profile->getPresetAttribute('objection_teilbereich')
                    ? $profile->getPresetAttribute('objection_teilbereich')
                    : (string) EvasysMatching::instituteName($course->institut_id),
                'ParticipantList' => $participants,
                'AnonymousParticipants' => true,
                'SurveyCreatorList' => $surveys,
            ];
        }
    }

    protected function getInstructorPart($id, $is_email = false)
    {
        $user = !$is_email ? User::find($id) : User::findOneBySQL("Email = ?", [$id]);
        if ($user) {
            if (in_array(Config::get()->EVASYS_EXPORT_DOZENT_BY_FIELD, array_keys($user->toArray()))) {
                $common_id = $user[Config::get()->EVASYS_EXPORT_DOZENT_BY_FIELD];
            } else {
                $common_id = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND range_id = ? AND range_type = 'user'", [
                    Config::get()->EVASYS_EXPORT_DOZENT_BY_FIELD,
                    $user->id
                ]);
                $common_id = $common_id ? $common_id->content : $user->id;
            }
            return [
                'InstructorUid' => $common_id ?: "",
                //'InstructorLogin' => "",
                'FirstName' => $user['Vorname'] ?: "",
                'LastName' => $user['Nachname'] ?: "",
                'Gender' => $user['geschlecht'] == 1 ? "m" : ($user['geschlecht'] == 2 ? "w" : "n"),
                'Email' => $user['Email'],
                'Title' => $user['title_front']
            ];
        } else {
            return [
                'InstructorUid' => $id,
                'LastName' => "N.N.",
                'Email' => $id
            ];
        }
    }

    public function getPDFLink($survey_id)
    {
        if (!is_array($_SESSION['EVASYS_SURVEY_PDF_LINK'])) {
            $_SESSION['EVASYS_SURVEY_PDF_LINK'] = [];
        }
        if (isset($_SESSION['EVASYS_SURVEY_PDF_LINK'][$survey_id])
                && ($_SESSION['EVASYS_SURVEY_PDF_LINK'][$survey_id])
                && (time() - $_SESSION['EVASYS_SURVEY_PDF_LINK_EXPIRE'][$survey_id] < 60 * Config::get()->EVASYS_CACHE)) {
            //return $_SESSION['EVASYS_SURVEY_PDF_LINK'][$survey_id];
        }

        $soap = EvasysSoap::get();
        $params = [
            'nSurveyId' => $survey_id
        ];
        //GetFormTranslations pro form_id liefert eine SystemLanguage (SystemLanguageAbbreviation ist beispielsweise en_GB de_edu, en_edu)
        $profile = null;
        $profiles = EvasysCourseProfile::findBySQL('seminar_id = :course_id AND `surveys` LIKE :survey_id', [
            'course_id' => $this['Seminar_id'],
            'survey_id' => '%'.$survey_id.'%'
        ]);
        foreach ($profiles as $p) {
            if ($p['surveys'] && in_array($survey_id, array_values($p['surveys']->getArrayCopy()))) {
                $profile = $p;
            }
        }
        if ($profile) {
            $form = EvasysForm::find($profile->getFinalFormId());
            if ($form && $form['translations']) {
                $user_language = getUserLanguage($GLOBALS['user']->id);
                if ($form['translations'][$user_language]) {
                    $params['nUserId'] = 0;
                    $params['nCustomPDFId'] = '';
                    $params['nLanguageID'] = $form['translations'][$user_language]; //$user_language === "en_GB" ? 2 : 1 //SystemLanguage 1= 2=
                }
            }
        }

        $link = $soap->soapCall("GetPDFReport", $params);
        $_SESSION['EVASYS_SURVEY_PDF_LINK_EXPIRE'][$survey_id] = time();
        if (is_a($link, "SoapFault")) {
            return $_SESSION['EVASYS_SURVEY_PDF_LINK'][$survey_id] = false;
        } else {
            $link = str_replace("http://localhost/evasys", Config::get()->EVASYS_URI, $link);
            return $_SESSION['EVASYS_SURVEY_PDF_LINK'][$survey_id] = $link;
        }
    }

    public function publishingAllowed($dozent_id = null)
    {
        $seminar_id = strlen($this['Seminar_id']) > 32
            ? substr($this['Seminar_id'], 0, 32)
            : $this['Seminar_id'];
        $statement = DBManager::get()->prepare("
            SELECT user_id
            FROM seminar_user
            WHERE Seminar_id = ?
                AND status = 'dozent'
        ");
        $statement->execute([$seminar_id]);
        $dozent_ids = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        if (Config::get()->EVASYS_PUBLISH_RESULTS || in_array($GLOBALS['user']->id, $dozent_ids)) {
            $semester = $this->course->start_semester;
            $profile = EvasysCourseProfile::findBySemester(
                $seminar_id,
                $semester ? $semester->getId() : null
            );
            if ($profile && $profile['split']) {
                return (bool) $this->publishing_allowed_by_dozent[$dozent_id];
            } else {
                return (bool) $this->publishing_allowed;
            }
        } else {
            return false;
        }
    }

    public function reportsAllowed()
    {
        $seminar_id = strlen($this['Seminar_id']) > 32
            ? substr($this['Seminar_id'], 0, 32)
            : $this['Seminar_id'];
        $semester = $this->course->start_semester;
        $profile = EvasysCourseProfile::findBySemester(
            $seminar_id,
            $semester ? $semester->getId() : null
        );
        $offset_days = $profile->getPresetAttribute("extended_report_offset");
        if (($profile->getPresetAttribute("reports_after_evaluation") === "yes") && (($profile->getFinalEnd() + ($offset_days * 86400)) > time())) {
            return false;
        }
        return true;
    }

    public function allowPublishing($vote)
    {
        if (!$GLOBALS['perm']->have_studip_perm("dozent", $this['Seminar_id'])) {
            return false;
        }
        $dozenten = $this->publishing_allowed_by_dozent ? $this->publishing_allowed_by_dozent->getArrayCopy() : [];
        $dozenten[$GLOBALS['user']->id] = $vote ? 1 : 0;
        $this->publishing_allowed_by_dozent = $dozenten;
        $this->publishing_allowed = $vote ? 1 : 0;
        return $this->store();
    }

}
