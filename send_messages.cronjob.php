<?php

class EvasysSendMessagesJob extends CronJob
{
    /**
     * Returns the name of the cronjob.
     */
    public static function getName()
    {
        return _('evasys: Nachrichten verschicken');
    }

    /**
     * Returns the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Sendet a) an die Studierenden eine Nachricht über eine neu geöffnete Evaluation, an der sie teilnehmen können, und b) eine Nachricht an Lehrende 24 Stunden vor Beginn der Befragung.');
    }

    /**
     * Setup method. Loads neccessary classes and checks environment. Will
     * bail out with an exception if environment does not match requirements.
     */
    public function setUp()
    {
        require_once __DIR__."/EvasysPlugin.class.php";
    }

    /**
     * Return the parameters for this cronjob.
     *
     * @return Array Parameters.
     */
    public static function getParameters()
    {
        return [];
    }

    /**
     * Executes the cronjob.
     *
     * @param mixed $last_result What the last execution of this cronjob
     *                           returned.
     * @param Array $parameters Parameters for this cronjob instance which
     *                          were defined during scheduling.
     *                          Only valid parameter at the moment is
     *                          "verbose" which toggles verbose output while
     *                          purging the cache.
     */
    public function execute($last_result, $parameters = [])
    {
        $last_execution = Config::get()->EVASYS_SEND_MESSAGES_LAST_EXECUTION;

        $sent_messages = 0;
        $courses_count = 0;
        $next_courses_count = 0;

        if (!$last_execution || $last_execution < time() - 86400) {
            //if we do this for the first time, we use the last 24 hours:
            $last_execution = time() - 86400;
        }
        $messaging = new messaging();

        Config::get()->store("EVASYS_SEND_MESSAGES_LAST_EXECUTION", time());

        //Send reminder to teachers 24 Stunden vor Beginn:
        $fetch_profiles = DBManager::get()->prepare("
            SELECT `evasys_course_profiles`.*
            FROM `evasys_course_profiles`
                LEFT JOIN seminare ON (`evasys_course_profiles`.`seminar_id` = `seminare`.`Seminar_id`)
                LEFT JOIN evasys_institute_profiles ON (`evasys_institute_profiles`.`institut_id` = `seminare`.`Institut_id`
                        AND evasys_institute_profiles.semester_id = `evasys_course_profiles`.`semester_id`)
                LEFT JOIN `Institute` ON (`seminare`.`Institut_id` = `Institute`.`Institut_id`)
                LEFT JOIN `evasys_institute_profiles` AS `evasys_fakultaet_profiles` ON (`evasys_fakultaet_profiles`.`institut_id` = `Institute`.`fakultaets_id`
                        AND `evasys_fakultaet_profiles`.`semester_id` = `evasys_course_profiles`.`semester_id`)
                LEFT JOIN evasys_global_profiles ON (`evasys_global_profiles`.`semester_id` = `evasys_course_profiles`.`semester_id`)
            WHERE `evasys_course_profiles`.`applied` = '1'
                AND `evasys_course_profiles`.`transferred` = '1'
                AND IFNULL(`evasys_course_profiles`.`begin`, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) < :now
                AND IFNULL(`evasys_course_profiles`.`begin`, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) >= :last_execution
                AND IFNULL(evasys_institute_profiles.mail_begin_subject, IFNULL(evasys_fakultaet_profiles.mail_begin_subject, evasys_global_profiles.mail_begin_subject)) IS NOT NULL
                AND IFNULL(evasys_institute_profiles.mail_begin_body, IFNULL(evasys_fakultaet_profiles.mail_begin_body, evasys_global_profiles.mail_begin_body)) IS NOT NULL
            GROUP BY `evasys_course_profiles`.`seminar_id`
        ");
        $fetch_profiles->execute([
            'now' => time() + 86400,
            'last_execution' => $last_execution + 86400
        ]);
        while ($course_data = $fetch_profiles->fetch(PDO::FETCH_ASSOC)) {
            $profile = EvasysCourseProfile::buildExisting($course_data);
            $subject = $profile->getPresetAttribute('mail_begin_subject');
            $body = $profile->getPresetAttribute('mail_begin_body');
            $next_courses_count++;
            if ($subject && $body) {
                $teachers = $profile->teachers->getArrayCopy();
                $oldbase = URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
                $url = URLHelper::getURL("plugins.php/evasysplugin/evaluation/show", [
                    'cid' => $profile['seminar_id']
                ]);
                foreach ($teachers as $teacher_id) {
                    setTempLanguage($teacher_id);
                    $templates = [
                        '{{course}}',
                        '{{coursename}}',
                        '{{url}}',
                        '{{evaluationsende}}',
                        '{{evaluationsbeginn}}'
                    ];
                    $replacement = [
                        $profile->course->getFullName(),
                        $profile->course['name'],
                        $url,
                        date("d.m.Y H:i", $profile->getFinalEnd()),
                        date("d.m.Y H:i", $profile->getFinalBegin())
                    ];
                    $subject_mail = str_ireplace($templates, $replacement, (string) $subject);
                    $body_mail = str_ireplace($templates, $replacement, (string) $body);

                    $messaging->insert_message(
                        $body_mail,
                        get_username($teacher_id),
                        '____%system%____',
                        '',
                        '',
                        '',
                        '',
                        $subject_mail,
                        true,
                        'normal',
                        ["Evaluation"],
                        false
                    );
                    restoreLanguage();
                    $sent_messages++;
                }

                URLHelper::setBaseURL($oldbase);
            }
        }


        //Send reminder to students:
        $fetch_profiles = DBManager::get()->prepare("
            SELECT `evasys_course_profiles`.*
            FROM `evasys_course_profiles`
                LEFT JOIN seminare ON (`evasys_course_profiles`.`seminar_id` = `seminare`.`Seminar_id`)
                LEFT JOIN evasys_institute_profiles ON (`evasys_institute_profiles`.`institut_id` = `seminare`.`Institut_id`
                        AND evasys_institute_profiles.semester_id = `evasys_course_profiles`.`semester_id`)
                LEFT JOIN `Institute` ON (`seminare`.`Institut_id` = `Institute`.`Institut_id`)
                LEFT JOIN `evasys_institute_profiles` AS `evasys_fakultaet_profiles` ON (`evasys_fakultaet_profiles`.`institut_id` = `Institute`.`fakultaets_id`
                        AND `evasys_fakultaet_profiles`.`semester_id` = `evasys_course_profiles`.`semester_id`)
                LEFT JOIN evasys_global_profiles ON (`evasys_global_profiles`.`semester_id` = `evasys_course_profiles`.`semester_id`)
            WHERE `evasys_course_profiles`.`applied` = '1'
                AND `evasys_course_profiles`.`transferred` = '1'
                AND IFNULL(`evasys_course_profiles`.`begin`, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) < :now
                AND IFNULL(`evasys_course_profiles`.`begin`, IFNULL(evasys_institute_profiles.begin, IFNULL(evasys_fakultaet_profiles.begin, evasys_global_profiles.begin))) >= :last_execution
                AND IFNULL(`evasys_course_profiles`.`mode`, IFNULL(evasys_institute_profiles.`mode`, IFNULL(evasys_fakultaet_profiles.`mode`, evasys_global_profiles.`mode`))) = 'online'
            GROUP BY `evasys_course_profiles`.`seminar_id`
        ");
        $fetch_profiles->execute([
            'now' => time(),
            'last_execution' => $last_execution
        ]);

        $user_permissions = ['autor', 'tutor'];
        if (EvasysPlugin::useLowerPermissionLevels()) {
            $user_permissions[] = 'user';
        }


        while ($course_data = $fetch_profiles->fetch(PDO::FETCH_ASSOC)) {
            $profile = EvasysCourseProfile::buildExisting($course_data);

            $courses_count++;

            $fetch_members = DBManager::get()->prepare("
                SELECT seminar_user.user_id
                FROM seminar_user
                WHERE seminar_user.Seminar_id = :course_id
                    AND seminar_user.status IN (:perms)
                    AND seminar_user.mkdate <= :lastchance
            ");
            $fetch_members->execute([
                'perms' => $user_permissions,
                'course_id' => $profile['seminar_id'],
                'lastchance' => $profile['chdate']
            ]);

            $user_ids = $fetch_members->fetchAll(PDO::FETCH_COLUMN, 0);

            $course = Course::find($profile['seminar_id']);

            foreach ($user_ids as $user_id) {
                $user = User::find($user_id);
                if ($user) {
                    setTempLanguage($user['user_id']);

                    $oldbase = URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
                    $url = URLHelper::getURL("plugins.php/evasysplugin/evaluation/show", [
                        'cid' => $profile['seminar_id']
                    ]);

                    $message = (string) $profile->getPresetAttribute('mail_reminder_body');
                    $message = str_ireplace(
                        ["{{url}}", "{{coursename}}", "{{evaluationsende}}", "{{evaluationsbeginn}}"],
                        [$url, $course->name, date("d.m.Y H:i", $profile->getFinalEnd()), date("d.m.Y H:i", $profile->getFinalBegin())],
                        $message
                    );

                    $subject = $profile->getPresetAttribute('mail_reminder_subject');
                    $subject = str_ireplace(
                        ["{{url}}", "{{coursename}}", "{{evaluationsende}}", "{{evaluationsbeginn}}"],
                        [$url, $course->name, date("d.m.Y H:i", $profile->getFinalEnd()), date("d.m.Y H:i", $profile->getFinalBegin())],
                        $subject
                    );

                    $messaging->insert_message(
                        $message,
                        $user['username'],
                        '____%system%____',
                        '',
                        '',
                        '',
                        '',
                        $subject,
                        true,
                        'normal',
                        ["Evaluation"],
                        false
                    );
                    restoreLanguage();
                    URLHelper::setBaseURL($oldbase);
                    $sent_messages++;
                }
            }
        }

        echo "Courses started: ".$courses_count."\n";
        echo "Courses will start in 24 hours: ".$next_courses_count."\n";
        echo "Messages sent: ".$sent_messages;
    }
}
