<?php

require_once dirname(__file__)."/EvasysSoap.php";
require_once dirname(__file__)."/EvasysSoapClient.php";

class EvasysSeminar extends SimpleORMap
{

    protected $db_table = 'evasys_seminar';

    protected static function configure($config = array())
    {
        $config['db_table'] = 'evasys_seminar';
        $config['belongs_to']['course'] = array(
            'class_name' => 'Course',
            'foreign_key' => 'seminar_id'
        );
        $config['serialized_fields']['publishing_allowed_by_dozent'] = "JSONArrayObject";
        parent::configure($config);
    }

    static public function findBySeminar($course_id)
    {
        return self::findBySQL("Seminar_id = ".DBManager::get()->quote($course_id));
    }

    /**
     * Fetches all new evaluations (red icon) for the user.
     * @param string|null $user_id of the given user or null for current user
     * @return integer : number of new surveys
     */
    public function getEvaluationStatus($user_id = null)
    {
        $user = $user_id ? User::find($user_id) : User::findCurrent();
        if ($GLOBALS['perm']->have_perm("admin", $user->getId())) {
            return 0;
        }
        $profile = EvasysCourseProfile::findBySemester($seminar['Seminar_id']);
        if (Config::get()->EVASYS_ENABLE_SPLITTING_COURSES && $profile['split']) {
            $seminar_ids = array();
            foreach ($profile['teachers'] as $dozent_id) {
                $seminar_ids[] = $seminar['Seminar_id'] . $dozent_id;
            }
        } else {
            $seminar_ids = array($seminar['Seminar_id']);
        }
        if (isset($_SESSION['EVASYS_SEMINARS_STATUS'])
            && (time() - $_SESSION['EVASYS_STATUS_EXPIRE']) < 60 * Config::get()->EVASYS_CACHE) {
            $new = 0;
            foreach ($seminar_ids as $seminar_id) {
                $new += $_SESSION['EVASYS_SEMINARS_STATUS'][$seminar_id];
            }
            return $new;
        }
        $soap = EvasysSoap::get();
        $evasys_sem_object = $soap->__soapCall("GetEvaluationSummaryByParticipant", array($user['email']));
        if (is_a($evasys_sem_object, "SoapFault")) {
            if ($evasys_sem_object->getMessage() === "ERR_212") {
                $_SESSION['EVASYS_SEMINARS_STATUS'] = array();
            } else {
                PageLayout::postError("SOAP-error: " . $evasys_sem_object->detail);
                return 0;
            }
        } else {
            foreach ((array) $evasys_sem_object->SurveySummary as $survey) {
                if (!$survey->Participated && $survey->SurveyOpenState) {
                    $_SESSION['EVASYS_SEMINARS_STATUS'][$survey->SurveyCourseCode] += 1;
                }
            }
        }
        $_SESSION['EVASYS_STATUS_EXPIRE'] = time();
        $new = 0;
        foreach ($seminar_ids as $seminar_id) {
            $new += $_SESSION['EVASYS_SEMINARS_STATUS'][$seminar_id];
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
        $courses = array();
        foreach($seminars as $seminar) {
            $part = $seminar->getCoursePart();
            if ($part && $part[0] !== "delete") {
                if ($part['CourseName']) {
                    $courses[] = $part;
                    $profile = EvasysCourseProfile::findBySemester($seminar['seminar_id']);
                    if (!$profile->isNew()) {
                        $profile['transferred'] = 1;
                        $profile->store();
                    }
                } else {
                    //we have split courses for each teacher
                    foreach ($part as $subcourse) {
                        $courses[] = $subcourse;
                    }
                    //try to delete a course-evaluation if we have a split course
                    $soap->__soapCall("DeleteCourse", array(
                        'CourseId' => $seminar['seminar_id'],
                        'IdType' => "PUBLIC"
                    ));
                    $profile = EvasysCourseProfile::findBySemester($seminar['seminar_id']);
                    if (!$profile->isNew()) {
                        $profile['transferred'] = 1;
                        $profile->store();
                    }
                }

            } elseif($part[0] === "delete") {
                foreach ($part[1] as $seminar_id) {
                    $soap->__soapCall("DeleteCourse", array(
                        'CourseId' => $seminar_id,
                        'IdType' => "PUBLIC"
                    ));
                    $profile = EvasysCourseProfile::findBySemester($seminar['Seminar_id']);
                    if (!$profile->isNew()) {
                        $profile['transferred'] = 0;
                        $profile->store();
                    }
                }
            }
        }
        $sessionlist = array(
            array('CourseCreators' => $courses),
            true
        );
        //var_dump($sessionlist); die();
        $evasys_sem_object = $soap->__soapCall("InsertCourses", $sessionlist);
        if (is_a($evasys_sem_object, "SoapFault")) {
            if ($evasys_sem_object->getMessage() == "Not Found") {
                return "SoapPort der WSDL-Datei antwortet nicht.";
            } else {
                var_dump($evasys_sem_object);
                var_dump($soap->__getLastResponse());die();
                return "SOAP-error: " . $evasys_sem_object->getMessage().($evasys_sem_object->detail ? " (".$evasys_sem_object->detail.")" : "");
            }
        } else {
            //Speichern der survey_ids, sodass wir beim nächsten Mal die alten Survey_ids mitgeben können.
            foreach ((array) $evasys_sem_object->UploadStatus as $status) {

                $course_uid = $status->CourseUid;
                if (strlen($course_uid) > 32) {
                    $course_id = substr($course_uid, 0, 32);
                } else {
                    $course_id = $course_uid;
                }
                $profile = EvasysCourseProfile::findBySemester($course_id);
                if (!$profile->isNew()) {
                    foreach ($status->SurveyStatusList->SurveyStatusArray as $survey_status) {
                        if ($survey_status->SurveyId) {
                            if (!$profile['surveys']) {
                                $profile['surveys'] = array($course_uid => $survey_status->SurveyId);
                            } else {
                                $profile['surveys'][$course_uid] = $survey_status->SurveyId;
                            }
                        }
                    }
                    $profile['surveys']['form_id'] = $profile->getFinalFormId();
                    $profile->store();
                }
            }
            return true;
        }
    }

    public function getCoursePart()
    {
        $db = DBManager::get();
        $seminar = new Seminar($this['Seminar_id']);
        $profile = EvasysCourseProfile::findBySemester($this['Seminar_id']);
        if (Config::get()->EVASYS_ENABLE_PROFILES && !$profile['applied'] && !$profile['split']) {
            return $profile['transferred'] ? array("delete", array($this['Seminar_id'])) : null; //course should be deleted from evasys database
        }
        $participants = array();

        $user_permissions = ['autor', 'tutor'];

        if (EvasysPlugin::useLowerPermissionLevels()) {
            $user_permissions[] = 'user';
        }

        $statement = DBManager::get()->prepare("
            SELECT auth_user_md5.user_id
            FROM auth_user_md5 
                INNER JOIN seminar_user ON (seminar_user.user_id = auth_user_md5.user_id)
            WHERE seminar_user.Seminar_id = :seminar_id
                AND seminar_user.status IN ( :user_permissions )
        ");
        $statement->execute(array(
            'seminar_id' => $this['Seminar_id'],
            'user_permissions' => $user_permissions
        ));
        $students = $statement->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($students as $student_id) {
            $student = User::find($student_id);
            $participants[] = array(
                'm_nId' => "",
                'm_sTitle' => "",//$student['title_front'],
                'm_sIdentifier' => $student['email'],
                'm_sEmail' => $student['email'],
                'm_sFirstname' => "", //$student['Vorname'],
                'm_sLastname' => "", //$student['Nachname'],
                'm_nGender' => "", //$student['geschlecht'] == 1 ? "m" : "w",
                'm_sAddress' => "",
                'm_sCustomFieldsJSON' => "",
            );
        }

        $stmt = DBManager::get()->prepare("
            SELECT DISTINCT IF(sem_tree.studip_object_id IS NOT NULL, (SELECT Institute.Name FROM Institute WHERE Institute.Institut_id = sem_tree.studip_object_id), sem_tree.name) 
            FROM seminar_sem_tree 
                INNER JOIN sem_tree ON (seminar_sem_tree.sem_tree_id = sem_tree.sem_tree_id) 
            WHERE seminar_sem_tree.seminar_id = ? 
            ORDER BY sem_tree.name ASC 
        ");
        $stmt->execute(array($this['Seminar_id']));
        $studienbereiche = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $datenfelder = DataFieldEntry::getDataFieldEntries($this['Seminar_id'], 'sem', $seminar->status);
        $custom_fields = array(
            '1' => $seminar->getNumber(),
            '2' => "" //Anzahl der Bögen ?
        );
        $i = 3;
        foreach ($datenfelder as $id => $datafield) {
            $custom_fields[$i] = $datafield;
            $i++;
        }
        $surveys = array();

        if (Config::get()->EVASYS_ENABLE_PROFILES) {
            $form_id = $profile->getFinalFormId();
            if ($profile['applied'] && $profile['surveys']['form_id'] && ($profile['surveys']['form_id'] != $form_id)) {
                //We need to update the surveys manually via SOAP before updating the rest of the course-info
                //Otherwise we would have two or more surveys, because Evasys ignores the SurveyId in
                //the InsertCourses-method and identifies the survey just by course_id, semester and form_id.

                //UpdateSurvey
                if (!$profile['split']) {
                    $seminar_ids = array($this['Seminar_id']);
                } else {
                    $seminar_ids = $profile['surveys']->getArrayCopy();
                    $seminar_ids = array_keys($seminar_ids);
                }

                foreach ($seminar_ids as $seminar_id) {
                    if ($seminar_id !== "form_id") {
                        //Check if we are allowed to change the survey?
                        //And what happens if the check fails and we are not allowed?
                        $survey = array(
                            'm_sTitle' => "bla", //necessary but useless ... ?
                            'm_nSurveyId' => $profile['surveys'] && $profile['surveys'][$seminar_id]
                                ? $profile['surveys'][$seminar_id]
                                : "", //experimental
                            'm_cType' => "",
                            'm_nFrmid' => $form_id,
                            'm_sLastDataCollectionDate' => "",
                            'm_sMaskTan' => ""
                        );
                        $soap = EvasysSoap::get();
                        $data = array(
                            'oSurvey' => $survey
                        );
                        $soap->__soapCall("UpdateSurvey", $data);
                    }
                }
            }

            $surveys[] = array(
                'FormId' => $form_id,
                'FormIdType' => "INTERNAL",
                'SurveyID' => $profile['surveys'] && $profile['surveys'][$this['Seminar_id']]
                    ? $profile['surveys'][$this['Seminar_id']]
                    : "", //experimental
                'PeriodId' => date("Y-m-d", $seminar->getSemesterStartTime()),
                'PeriodIdType' => "PERIODDATE",
                'SurveyType' => array(
                    'm_chSurveyType' => ($profile['mode'] === "paper" && !Config::get()->EVASYS_FORCE_ONLINE)
                        ? "d"  // d = Deckblatt, s = Selbstdruck
                        : "o", // o = online+TAN
                    'm_sDescription' => ""
                ),
                'Verification' => false,
                'Notice' => "",
                'FormRecipientList' => array(), //Emails, an die die PDF des Fragebogens verschickt wird, aber wie oft soll er ausdrucken??
                'InviteParticipants' => false,
                'InvitationTask' => array(
                    'SurveyID' => $profile['surveys'] && $profile['surveys'][$this['Seminar_id']]
                        ? $profile['surveys'][$this['Seminar_id']]
                        : "",
                    'StartTime' => date("c", $profile->getFinalBegin()),
                    'EmailSubject' => "###PREVENT_DISPATCH###" //Keine Mail an die Studierenden mit den TANs senden
                ),
                'CloseTask' => array(
                    'SurveyID' => $profile['surveys'] && $profile['surveys'][$this['Seminar_id']]
                        ? $profile['surveys'][$this['Seminar_id']]
                        : "",
                    'StartTime' => date("c", $profile->getFinalEnd())
                ),
                'SerialPrint' => false
            );
        }

        $dozenten = $db->query(
            "SELECT seminar_user.user_id " .
            "FROM seminar_user " .
            "WHERE seminar_user.Seminar_id = ".$db->quote($this['Seminar_id'])." " .
            "AND seminar_user.status = 'dozent' " .
            "ORDER BY seminar_user.position ASC " .
            "")->fetchAll(PDO::FETCH_COLUMN, 0);

        if (Config::get()->EVASYS_ENABLE_PROFILES && $profile['applied'] && $profile['split']) {
            //we split this course into one course for each teacher.
            $parts = array();

            foreach ($dozenten as $dozent_id) {
                if (!$profile['teachers'] || in_array($dozent_id, $profile['teachers']->getArrayCopy())) {
                    $instructorlist = array();

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

                    $parts[] = array(
                        'CourseUid' => $this['Seminar_id'] . $dozent_id,
                        'CourseName' => $seminar->getName(),
                        'CourseCode' => $this['Seminar_id'],
                        'CourseType' => EvasysMatching::semtypeName($seminar->status),
                        'CourseProgramOfStudy' => implode('|', $studienbereiche),
                        'CourseEnrollment' => 0, // ?
                        'CustomFieldsJSON' => json_encode($custom_fields),
                        'CoursePeriodId' => date("Y-m-d", $seminar->getSemesterStartTime()),
                        'CoursePeriodIdType' => "PERIODDATE",
                        'InstructorList' => $instructorlist,
                        'RoomName' => (string) $seminar->location,
                        'SubunitName' => (string) EvasysMatching::instituteName($seminar->institut_id),
                        'ParticipantList' => $participants,
                        'AnonymousParticipants' => true,
                        'SurveyCreatorList' => $surveys2,
                    );
                }
            }
            return $parts;

        } elseif(Config::get()->EVASYS_ENABLE_PROFILES && !$profile['applied'] && $profile['split']) {
            //we need to delete all former sub-courses
            if ($profile['transferred']) {
                $ids = array();
                foreach ($dozenten as $dozent_id) {
                    $ids[] = $this['Seminar_id'].$dozent_id;
                }
                return array("delete", $ids);
            }  else {
                return null;
            }
        } else {
            //we just want to import/update this course
            $instructorlist = array();
            $instructors = array();
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

            return array(
                'CourseUid' => $this['Seminar_id'],
                'CourseName' => $seminar->getName(),
                'CourseCode' => $this['Seminar_id'],
                'CourseType' => EvasysMatching::semtypeName($seminar->status),
                'CourseProgramOfStudy' => implode('|', $studienbereiche),
                'CourseEnrollment' => 0, // ?
                'CustomFieldsJSON' => json_encode($custom_fields),
                'CoursePeriodId' => date("Y-m-d", $seminar->getSemesterStartTime()),
                'CoursePeriodIdType' => "PERIODDATE",
                'InstructorList' => $instructorlist,
                'RoomName' => (string) $seminar->location,
                'SubunitName' => (string) EvasysMatching::instituteName($seminar->institut_id),
                'ParticipantList' => $participants,
                'AnonymousParticipants' => true,
                'SurveyCreatorList' => $surveys,
            );
        }
    }

    protected function getInstructorPart($id, $is_email = false)
    {
        $user = !$is_email ? User::find($id) : User::findOneBySQL("Email = ?", array($id));
        if ($user) {
            if (in_array(Config::get()->EVASYS_EXPORT_DOZENT_BY_FIELD, array_keys($user->toArray()))) {
                $common_id = $user[Config::get()->EVASYS_EXPORT_DOZENT_BY_FIELD];
            } else {
                $common_id = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND range_id = ? AND range_type = 'user'", array(
                    Config::get()->EVASYS_EXPORT_DOZENT_BY_FIELD,
                    $user->id
                ));
                $common_id = $common_id ? $common_id->content : $user->id;
            }
            return array(
                'InstructorUid' => $common_id ?: "",
                //'InstructorLogin' => "",
                'FirstName' => $user['Vorname'] ?: "",
                'LastName' => (Config::get()->EVASYS_EXPORT_TITLES ? $user['title_front'] . " " : "") . $user['Nachname'],
                'Gender' => $user['geschlecht'] == 1 ? "m" : "w",
                'Email' => $user['Email']
            );
        } else {
            return array(
                'InstructorUid' => $id,
                'LastName' => "N.N.",
                'Email' => $id
            );
        }
    }

    public function getSurveys($user_id = null)
    {
        if (isset($_SESSION['EVASYS_SEMINAR_SURVEYS'][$this['Seminar_id']])
                && (time() - $_SESSION['EVASYS_SEMINAR_SURVEYS_EXPIRE'][$this['Seminar_id']]) < 60 * Config::get()->EVASYS_CACHE) {
            return $_SESSION['EVASYS_SEMINAR_SURVEYS'][$this['Seminar_id']];
        }
        $soap = EvasysSoap::get();
        $user_id || $user_id = $GLOBALS['user']->id;
        $user = new User($user_id);

        var_dump($user->email);
        var_dump($this['Seminar_id']);

        $surveys = $soap->__soapCall("GetPswdsByParticipant", array(
            'UserMailAddress' => $user->email,
            'CourseCode' => $this['Seminar_id']
        ));

        if (is_a($surveys, "SoapFault")) {
            if ($surveys->faultstring === "ERR_206") {
                PageLayout::postMessage(MessageBox::info($surveys->detail));
                $surveys = array();
            } elseif ($surveys->faultstring === "ERR_207") {
                $surveys = array("schon teilgenommen");
            } else {
                throw new Exception("SOAP-Fehler: ".$surveys->detail);
            }
        }
        $_SESSION['EVASYS_SEMINAR_SURVEYS_EXPIRE'][$this['Seminar_id']] = time();
        return $_SESSION['EVASYS_SEMINAR_SURVEYS'][$this['Seminar_id']] = $surveys->OnlineSurveyKeys;
    }

    static public function compareSurveysDESC($a, $b)
    {
        return $a->m_oPeriod->m_sEndDate < $b->m_oPeriod->m_sEndDate;
    }

    public function getSurveyInformation()
    {
        $id = $this['Seminar_id']."-".$this['evasys_id'];
        if (isset($_SESSION['EVASYS_SURVEY_INFO'][$id])
                && (time() - $_SESSION['EVASYS_SURVEY_INFO_EXPIRE'][$id] < 60 * Config::get()->EVASYS_CACHE)) {
            return $_SESSION['EVASYS_SURVEY_INFO'][$id];
        }
        $soap = EvasysSoap::get();
        $course = $soap->__soapCall("GetCourse", array(
            'CourseId' => $this['Seminar_id'],
            'IdType' => "PUBLIC",
            //'IncludeParticipants' => 1,
            'IncludeSurveys' => 1
        ));
        //var_dump($course);
        if (is_a($course, "SoapFault")) {
            return null;
        } else {
            $this['evasys_id'] = $course->m_nCourseId; //kann nie schaden
            $this->store();
        }
        $surveys = (array) $course->m_oSurveyHolder->m_aSurveys->Surveys;
        //usort($surveys, "EvasysSeminar::compareSurveysDESC");
        $_SESSION['EVASYS_SURVEY_INFO_EXPIRE'][$id] = time();
        $_SESSION['EVASYS_SURVEY_INFO'][$id] = $surveys;

        return $_SESSION['EVASYS_SURVEY_INFO'][$id];
    }

    public function getPDFLink($survey_id)
    {
        if (!is_array($_SESSION['EVASYS_SURVEY_PDF_LINK'])) {
            $_SESSION['EVASYS_SURVEY_PDF_LINK'] = array();
        }
        if (isset($_SESSION['EVASYS_SURVEY_PDF_LINK'][$survey_id])
                && (time() - $_SESSION['EVASYS_SURVEY_PDF_LINK_EXPIRE'][$survey_id] < 60 * Config::get()->EVASYS_CACHE)) {
            return $_SESSION['EVASYS_SURVEY_PDF_LINK'][$survey_id];
        }
        $soap = EvasysSoap::get();
        $link = $soap->__soapCall("GetPDFReport", array(
            'nSurveyId' => $survey_id
        ));
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
        if (Config::get()->EVASYS_PUBLISH_RESULTS) {
            $profile = EvasysCourseProfile::findBySemester($this['Seminar_id']);
            if ($profile && $profile['split']) {
                return (bool) $this->publishing_allowed_by_dozent[$dozent_id];
            } else {
                return (bool) $this->publishing_allowed;
            }
        } else {
            return false;
        }
    }

    public function allowPublishing($vote)
    {
        if (!$GLOBALS['perm']->have_studip_perm("dozent", $this['Seminar_id'])) {
            return false;
        }
        $profile = EvasysCourseProfile::findBySemester($this['Seminar_id']);
        if ($profile && $profile['split']) {
            $this->publishing_allowed_by_dozent[$GLOBALS['user_id']] = (bool) $vote;
        }
        $this->publishing_allowed = (int) $vote;
        return $this->store();
    }

    public function getDozent()
    {
        $db = DBManager::get();
        return $db->query(
            "SELECT user_id " .
            "FROM seminar_user " .
            "WHERE Seminar_id = ".$db->quote($this['Seminar_id'])." " .
                "AND status = 'dozent' " .
            "ORDER BY position ASC " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
    }

}
