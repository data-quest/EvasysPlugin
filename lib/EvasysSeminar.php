<?php

require_once dirname(__file__)."/EvasysSoap.php";
require_once dirname(__file__)."/EvasysSoapClient.php";

class EvasysSeminar extends SimpleORMap {

    protected $db_table = 'evasys_seminar';

    static public function getStudipUser($user_id)
    {
        $st = DbManager::get()->prepare("SELECT *,a.user_id FROM auth_user_md5 a LEFT JOIN user_info b USING(user_id) WHERE a.user_id = ?");
        $st->execute(array($user_id));
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    static public function findBySeminar($course_id)
    {
        return self::findBySQL("Seminar_id = ".DBManager::get()->quote($course_id));
    }

    /**
     * Uploads all given seminars in one soap-call to EvaSys.
     * @param array $seminars : array of EvasysSeminar
     */
    static public function UploadSessions(array $seminars)
    {
        $soap = EvasysSoap::get();
        $sessionlist = array();
        foreach($seminars as $seminar) {
            $sessionlist[] = $seminar->getSessionPart();
        }
        $sessionlist = array(
            array('Sessions' => $sessionlist),
            true
        );
        $evasys_sem_object = $soap->__soapCall("UploadSessions", $sessionlist);
        if (is_a($evasys_sem_object, "SoapFault")) {
            if ($evasys_sem_object->getMessage() == "Not Found") {
                return "SoapPort der WSDL-Datei antwortet nicht.";
            } else {
                var_dump($evasys_sem_object);
                var_dump($soap->__getLastResponse());die();
                return "SOAP-error: " . $evasys_sem_object->getMessage().($evasys_sem_object->detail ? " (".$evasys_sem_object->detail.")" : "");
            }
        } else {
            return true;
        }
    }

    /**
     * Fetches all new evaluations (red icon) for the user.
     * @param string|null $user_id of the given user or null for current user
     * @return array : array of Seminar_ids with
     * @throws Exception
     */
    public function getEvaluationStatus($user_id = null)
    {
        if (isset($_SESSION['EVASYS_SEMINARS_STATUS'])
            && (time() - $_SESSION['EVASYS_STATUS_EXPIRE']) < 60 * Config::get()->EVASYS_CACHE) {
            return (int) $_SESSION['EVASYS_SEMINARS_STATUS'][$this['Seminar_id']];
        }
        $soap = EvasysSoap::get();
        $user = $user_id ? User::find($user_id) : User::findCurrent();
        $evasys_sem_object = $soap->__soapCall("GetEvaluationSummaryByParticipant", array($user['email']));
        if (is_a($evasys_sem_object, "SoapFault")) {
            if ($evasys_sem_object->getMessage() === "ERR_212") {
                $_SESSION['EVASYS_SEMINARS_STATUS'] = array();
            } else {
                var_dump($evasys_sem_object);
                die();
                throw new Exception("SOAP-error: " . $evasys_sem_object->detail);
            }
        } else {
            foreach ((array) $evasys_sem_object->SurveySummary as $survey) {
                if (!$survey->Participated && $survey->SurveyOpenState) {
                    $_SESSION['EVASYS_SEMINARS_STATUS'][$survey->SurveyCourseCode] += 1;
                }
            }
        }
        $_SESSION['EVASYS_STATUS_EXPIRE'] = time();
        return (int) $_SESSION['EVASYS_SEMINARS_STATUS'][$this['Seminar_id']];
    }

    /**
     * Not used right now. But this could upload a seminar to EvaSys. We don't use
     * this method because we upload many seminars in one request with
     * EvasysSeminar::UploadSessions .
     * @throws Exception
     */
    public function connectWithEvaSys()
    {
        //wird nicht verwendet, da wir alle Seminare gebündelt übertragen
        $soap = EvasysSoap::get();
        $arr = $this->getSessionPart();
        $evasys_sem_object = $soap->__soapCall("UploadSessions", array($arr));
        if (is_a($evasys_sem_object, "SoapFault")) {
            throw new Exception("SOAP-error: ".$evasys_sem_object->detail);
        }
        $this['evasys_id'] = "";
    }

    /**
     * Returns all information for a seminar to be uploaded to EvaySys.
     * @return array
     */
    public function getSessionPart()
    {
        $db = DBManager::get();
        $seminar = new Seminar($this['Seminar_id']);
        //$dozent = $this->getDozent();
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
            $student = self::getStudipUser($student_id);
            $participants[] = array(
                'm_nId' => "",
                'm_sTitle' => "",//$student['title_front'],
                'm_sIdentifier' => $student['Email'],
                'm_sEmail' => $student['Email'],
                'm_sFirstname' => "", //$student['Vorname'],
                'm_sLastname' => "", //$student['Nachname'],
                'm_nGender' => "", //$student['geschlecht'] == 1 ? "m" : "w",
                'm_sAddress' => "",
                'm_sCustom1' => "",
                'm_sCustom2' => "",
                'm_sCustom3' => ""
            );
        }

        $instructorlist = array();
        $dozenten = $db->query(
            "SELECT seminar_user.user_id " .
            "FROM seminar_user " .
            "WHERE seminar_user.Seminar_id = ".$db->quote($this['Seminar_id'])." " .
                "AND seminar_user.status = 'dozent' " .
            "ORDER BY seminar_user.position ASC " .
        "")->fetchAll(PDO::FETCH_COLUMN, 0);
        foreach ($dozenten as $dozent_id) {
            $dozent = User::find($dozent_id);
            if (in_array(Config::get()->EVASYS_EXPORT_DOZENT_BY_FIELD, array_keys($dozent->toArray()))) {
                $id = $dozent[Config::get()->EVASYS_EXPORT_DOZENT_BY_FIELD];
            } else {
                $id = DatafieldEntryModel::findOneBySQL("datafield_id = ? AND range_id = ? AND range_type = 'user'", array(
                    Config::get()->EVASYS_EXPORT_DOZENT_BY_FIELD,
                    $dozent_id
                ));
                $id = $id ? $id->content : $dozent_id;
            }
            $instructorlist[] = array(
                'InstructorUid' => $id,
                'FirstName' => $dozent['Vorname'],
                'LastName' => (Config::get()->EVASYS_EXPORT_TITLES ? $dozent['title_front']." ": "").$dozent['Nachname'],
                'Gender' => $dozent['geschlecht'] == 1 ? "m" : "w",
                'Email' => $dozent['Email']
            );
        }
        $stmt = DBManager::get()->prepare(
            "SELECT DISTINCT IF(sem_tree.studip_object_id IS NOT NULL, (SELECT Institute.Name FROM Institute WHERE Institute.Institut_id = sem_tree.studip_object_id), sem_tree.name) ".
            "FROM seminar_sem_tree ".
                "INNER JOIN sem_tree ON (seminar_sem_tree.sem_tree_id = sem_tree.sem_tree_id) " .
            "WHERE seminar_sem_tree.seminar_id = ? " .
            "ORDER BY sem_tree.name ASC " .
        "");
        $stmt->execute(array($this['Seminar_id']));
        $studienbereiche = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $datenfelder = DataFieldEntry::getDataFieldEntries($this['Seminar_id'], 'sem', $seminar->status);
        $custom_fields = array();
        foreach ($datenfelder as $id => $datafield) {
            $custom_fields[] = $datafield;
        }
        return array(
            'CourseCode' => $this['Seminar_id'],
            'CourseUid' => $this['Seminar_id'],
            'CoursePeriodId' => date("Y-m-d", $seminar->getSemesterStartTime()),
            'CoursePeriodIdType' => "PERIODDATE",
            'CourseName' => $seminar->getName(),
            'CourseType' => EvasysMatching::semtypeName($seminar->status),
            'm_nUserId' => count($participants),
            'SubunitName' => EvasysMatching::instituteName($seminar->institut_id),
            'ParticipantList' => $participants,
            'AnonymousParticipants' => true,
            'InstructorList' => $instructorlist,
            'CourseProgramOfStudy' => implode('|', $studienbereiche),
            'RoomName' => ($seminar->location),
            'CourseCustomField1' => $seminar->getNumber(),
            'CourseCustomField2' => $custom_fields[0] ? $custom_fields[0]->getValue() : "",
            'CourseCustomField3' => $custom_fields[1] ? $custom_fields[1]->getValue() : "",
            'CourseCustomField4' => $custom_fields[2] ? $custom_fields[2]->getValue() : "",
            'CourseCustomField5' => $custom_fields[3] ? $custom_fields[3]->getValue() : ""
        );
    }

    public function getSurveys($user_id = null)
    {
        if (isset($_SESSION['EVASYS_SEMINAR_SURVEYS'][$this['Seminar_id']])
                && (time() - $_SESSION['EVASYS_SEMINAR_SURVEYS_EXPIRE'][$this['Seminar_id']]) < 60 * Config::get()->EVASYS_CACHE) {
            return $_SESSION['EVASYS_SEMINAR_SURVEYS'][$this['Seminar_id']];
        }
        $soap = EvasysSoap::get();
        $sem = new Seminar($this['Seminar_id']);
        $user_id || $user_id = $GLOBALS['user']->id;
        $email = DBManager::get()->query("SELECT Email FROM auth_user_md5 WHERE user_id = ".DBManager::get()->quote($user_id))->fetch(PDO::FETCH_COLUMN, 0);

        $surveys = $soap->__soapCall("GetPswdsByParticipant", array(
            'UserMailAddress' => $email,
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

    static public function compareSurveysDESC($a, $b) {
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

    public function getVotesForPublishing()
    {
        $db = DBManager::get();
        return $db->query(
            "SELECT COUNT(*) " .
            "FROM evasys_publishing_votes " .
            "WHERE seminar_id = ".$db->quote($this['Seminar_id'])." " .
                "AND vote = '1' " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
    }

    public function publishingAllowed()
    {
        if (Config::get()->EVASYS_PUBLISH_RESULTS) {
            /*$sem = new Seminar($this->getId());
            return count($sem->getMembers("dozent")) == $this->getVotesForPublishing();
            */
            return $this->publishing_allowed;
        } else {
            return false;
        }
    }

    public function getMyVote()
    {
        $db = DBManager::get();
        if (!$GLOBALS['perm']->have_studip_perm("dozent", $this['Seminar_id'])) {
            return false;
        }
        return (bool) $db->query(
            "SELECT vote " .
            "FROM evasys_publishing_votes " .
            "WHERE seminar_id = ".$db->quote($this->getId())." " .
                "AND user_id = ".$db->quote($GLOBALS['user']->id)." " .
        "")->fetch(PDO::FETCH_COLUMN, 0);
    }

    public function vote($vote)
    {
        if (!$GLOBALS['perm']->have_studip_perm("dozent", $this['Seminar_id'])) {
            return false;
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
