<?php

class EvasysSendMessagesJob extends CronJob
{
    /**
     * Returns the name of the cronjob.
     */
    public static function getName()
    {
        return _('EvaSys: Nachrichten verschicken über neue Evaluationen');
    }

    /**
     * Returns the description of the cronjob.
     */
    public static function getDescription()
    {
        return _('Sendet an die Studierenden eine Nachricht über eine neu geöffnete Evaluation, an der sie teilnehmen können.');
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
        return array();
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
    public function execute($last_result, $parameters = array())
    {
        $last_execution = Config::get()->EVASYS_SEND_MESSAGES_LAST_EXECUTION;

        if (!$last_execution || $last_execution < time() - 86400) {
            $last_execution = time() - 86400;
        }

        Config::get()->store("EVASYS_SEND_MESSAGES_LAST_EXECUTION", time());

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

        $messaging = new messaging();

        $sent_messages = 0;
        $coures_count = 0;

        while ($course_data = $fetch_profiles->fetch(PDO::FETCH_ASSOC)) {
            $profile = EvasysCourseProfile::buildExisting($course_data);

            $coures_count++;

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

                    $message = Config::get()->EVASYS_REMINDER_MESSAGE;
                    $message = str_ireplace(
                        ["{{url}}", "{{coursename}}"],
                        [$url, $course->name],
                        $message
                    );

                    $subject = Config::get()->EVASYS_REMINDER_MESSAGE_SUBJECT;
                    $subject = str_ireplace(
                        ["{{url}}", "{{coursename}}"],
                        [$url, $course->name],
                        $subject
                    );

                    $subject = dgettext(
                        "evasys",
                        "Neue Evaluation: %s"
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

        echo "Courses started: ".$coures_count."\n";
        echo "Mesages sent: ".$sent_messages;
    }
}
