<?php

require_once dirname(__file__)."/EvasysSoap.php";
require_once dirname(__file__)."/EvasysSoapClient.php";

class EvasysForm extends SimpleORMap
{
    protected static function configure($config = array())
    {
        $config['db_table'] = 'evasys_forms';
        parent::configure($config);
    }

    static public function findAll()
    {
        $message = self::GetAllForms();
        if ($message !== true) {
            PageLayout::postError($message);
        }
        return self::findBySQL("1=1 ORDER BY name ASC");
    }

    static public function GetAllForms()
    {
        if (Config::get()->EVASYS_CACHE && ((time() - $_SESSION['EVASYS_ALL_FORMS_EXPIRE']) < 60 * Config::get()->EVASYS_CACHE)) {
            //return true;
        }
        if (!class_exists("SoapClient")) {
            PageLayout::postError(dgettext("evasys","SoapClient existiert nicht."));
            return;
        }
        $soap = EvasysSoap::get();

        $forms = $soap->soapCall("GetFormsInfoByParams", array(
            'Params' => array(
                'Users' => array("1"), //1 is for admin
                'IncludeDeactivatedForms' => false,
                'SelectFields' => array(
                    "ShortName",
                    "Description",
                    //"MainLanguageId",
                    //"OriginalId",
                    //"HeadLogoId",
                    //"URL"
                )
            )
        ));
        if (is_a($forms, "SoapFault")) {
            if ($forms->getMessage() === "Not Found") {
                return "SoapPort der WSDL-Datei antwortet nicht.";
            } else {
                //var_dump($forms);
                //var_dump($soap->__getLastResponse());die();
                return "SOAP-error: " . $forms->getMessage()
                    . ((is_string($forms->detail) || (is_object($forms->detail) && method_exists($forms->detail, "__toString")))
                        ? " (" . $forms->detail . ")"
                        : "");
            }
        } else {
            $form_ids = array();
            $formdatae = $forms->Strings ?: $forms['Strings'];
            foreach ($formdatae as $formdata) {
                $formdata = json_decode($formdata, true);
                $form = EvasysForm::findOneBySQL("form_id = ?", array($formdata['FormId']));
                if (!$form) {
                    $form = new EvasysForm();
                    $form->setId($formdata['FormId']);
                }
                $form['name'] = $formdata['ShortName'];
                $form['description'] = html_entity_decode(strip_tags($formdata['Description']));
                $form->store();
                $form_ids[] = $formdata['FormId'];
            }
            EvasysForm::deleteBySQL("form_id NOT IN (?)", array($form_ids));
            $_SESSION['EVASYS_ALL_FORMS_EXPIRE'] = time();
            return true;
        }
    }

    public function getNumberOfCourses()
    {
        //doesn't look for table evasys_profiles_semtype_forms yet :
        $statement = DBManager::get()->prepare("
            SELECT COUNT(*)
            FROM (
                SELECT evasys_course_profiles.course_profile_id
                FROM seminare
                    INNER JOIN evasys_course_profiles ON (seminare.Seminar_id = evasys_course_profiles.seminar_id)
                    LEFT JOIN evasys_institute_profiles ON (seminare.Institut_id = evasys_institute_profiles.institut_id AND evasys_institute_profiles.semester_id = evasys_course_profiles.semester_id)
                    LEFT JOIN Institute AS heimat ON (heimat.Institut_id = seminare.Institut_id)
                    LEFT JOIN evasys_institute_profiles AS fakultaet_profiles ON (heimat.fakultaets_id = fakultaet_profiles.institut_id AND fakultaet_profiles.semester_id = evasys_course_profiles.semester_id)
                    LEFT JOIN evasys_global_profiles ON (evasys_global_profiles.semester_id = evasys_course_profiles.semester_id)
                WHERE evasys_course_profiles.applied = '1'
                    AND (evasys_course_profiles.form_id = :form_id
                        OR (evasys_course_profiles.form_id IS NULL
                            AND (evasys_institute_profiles.form_id = :form_id
                                OR (evasys_course_profiles.form_id IS NULL
                                    AND (fakultaet_profiles.form_id = :form_id
                                        OR (fakultaet_profiles.form_id IS NULL
                                            AND evasys_global_profiles.form_id = :form_id
                                        )
                                    )
                                )
                            )
                        )
                    )
                GROUP BY evasys_course_profiles.course_profile_id
            ) AS all_applied_seminars
        ");
        $statement->execute(array('form_id' => $this->getId()));
        return $statement->fetch(PDO::FETCH_COLUMN, 0);
    }

}
