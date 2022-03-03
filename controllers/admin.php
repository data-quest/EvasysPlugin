<?php

require_once "lib/archiv.inc.php";

class AdminController extends PluginController
{

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!EvasysPlugin::isRoot() && !EvasysPlugin::isAdmin()) {
            throw new AccessDeniedException();
        }
    }

    public function course_tr_action($course_id)
    {
        // Get the view filter
        $this->view_filter = $this->getFilterConfig();

        $courses = $this->getCourses([
            'sortby'      => $this->sortby,
            'sortFlag'    => $this->sortFlag,
            'view_filter' => $this->view_filter,
            'typeFilter'  => $GLOBALS['user']->cfg->MY_COURSES_TYPE_FILTER,
            'datafields' => $this->getDatafieldFilters()
        ]);

        $this->semid = $course_id;
        $this->selected_action = $GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA;
        $this->values = $courses[$course_id];


        $tf = new Flexi_TemplateFactory(__DIR__."/../../../../../app/views");
        $template = $tf->open("admin/courses/_course.php");
        $template->view_filter = $this->getFilterConfig();
        $template->values = $courses[$course_id];
        $template->selected_action = $GLOBALS['user']->cfg->MY_COURSES_ACTION_AREA;
        $template->semid = $course_id;
        $template->controller = $this;

        $this->render_text($template->render());
    }

    public function upload_courses_action()
    {
        if (Request::isPost() && $GLOBALS['perm']->have_perm(Config::get()->EVASYS_TRANSFER_PERMISSION)) {
            $activate = Request::getArray("c");
            $evasys_seminar = [];

            $courses = array_map(function ($i) {
                $id = explode("_", $i); return $id[0];
                }, array_keys(Request::getArray("c")));
            $courses = array_unique($courses);
            foreach ($courses as $course_id) {
                $evasys_evaluation = EvasysSeminar::findBySeminar($course_id);
                if ($evasys_evaluation) {
                    $evasys_evaluation['activated'] = $activate[$course_id] ? 1 : 0;
                    if (!$evasys_evaluation['activated']) {
                        $evasys_evaluation->store();
                        unset($evasys_seminar[$course_id]);
                    } else {
                        $evasys_seminar[$course_id] = $evasys_evaluation;
                    }
                } else {
                    $evasys_seminar[$course_id] = new EvasysSeminar($course_id);
                    $evasys_seminar[$course_id]['activated'] = $activate[$course_id] ? 1 : 0;
                }
                if ($evasys_seminar[$course_id]) {
                    $evasys_seminar[$course_id]->store();
                }
            }

            if (!empty($evasys_seminar)) {
                $success = EvasysSeminar::UploadSessions($evasys_seminar);
                if ($success === true) {
                    foreach ($courses as $course_id) {
                        if (isset($evasys_seminar[$course_id])) {
                            $evasys_seminar[$course_id]->store();
                        }
                        try {
                            StudipLog::log(
                                'EVASYS_EVAL_TRANSFER',
                                $GLOBALS['user']->id,
                                $evasys_seminar[$course_id]['seminar_id'],
                                $evasys_seminar[$course_id]['seminar_id']
                            );
                        } catch (Exception $e) {
                            var_dump($evasys_seminar[$course_id]['seminar_id']);
                            die();
                        }
                    }
                    PageLayout::postMessage(MessageBox::success(sprintf(dgettext("evasys", "%s Veranstaltungen mit EvaSys synchronisiert."), count($activate))));
                } else {
                    PageLayout::postMessage(MessageBox::error(dgettext("evasys", "Fehler beim Synchronisieren mit EvaSys. ").$success));
                }
            } else {
                PageLayout::postMessage(MessageBox::info(dgettext("evasys", "Veranstaltungen abgewählt. Keine Synchronisation erfolgt.")));
            }
        }
        $this->redirect(URLHelper::getURL("dispatch.php/admin/courses/index"));
    }






    /**
     * Copied from app/admin/courses
     * @return array|mixed
     */
    protected function getFilterConfig()
    {
        $available_filters = array_keys($this->getViewFilters());

        $temp = $GLOBALS['user']->cfg->MY_COURSES_ADMIN_VIEW_FILTER_ARGS;
        if ($temp) {
            $config = json_decode($temp, true);
            $config = array_intersect($config, $available_filters);
        } else {
            $config = [];
        }

        return $config;
    }

    protected function getViewFilters()
    {
        $views = [
            'number'        => dgettext("evasys", 'Nr.'),
            'name'          => dgettext("evasys", 'Name'),
            'type'          => dgettext("evasys", 'Veranstaltungstyp'),
            'room_time'     => dgettext("evasys", 'Raum/Zeit'),
            'semester'      => dgettext("evasys", 'Semester'),
            'teachers'      => dgettext("evasys", 'Lehrende'),
            'members'       => dgettext("evasys", 'Teilnehmende'),
            'waiting'       => dgettext("evasys", 'Personen auf Warteliste'),
            'preliminary'   => dgettext("evasys", 'Vorläufige Anmeldungen'),
            'contents'      => dgettext("evasys", 'Inhalt'),
            'last_activity' => dgettext("evasys", 'Letzte Aktivität'),
        ];
        foreach (PluginManager::getInstance()->getPlugins("AdminCourseContents") as $plugin) {
            foreach ($plugin->adminAvailableContents() as $index => $label) {
                $views[$plugin->getPluginId() . "_" . $index] = $label;
            }
        }
        return $views;
    }

    protected function getCourses($params = [])
    {
        // Init
        if ($GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT === "all") {
            $inst = new SimpleCollection(Institute::getMyInstitutes($GLOBALS['user']->id));

            $inst->filter(function ($a) use (&$inst_ids) {
                $inst_ids[] = $a->Institut_id;
            });

        } else {
            //We must check, if the institute ID belongs to a faculty
            //and has the string _i appended to it.
            //In that case we must display the courses of the faculty
            //and all its institutes.
            //Otherwise we just display the courses of the faculty.

            $inst_id = $GLOBALS['user']->cfg->MY_INSTITUTES_DEFAULT;

            $institut = new Institute($inst_id);

            if (!$institut->isFaculty() || $GLOBALS['user']->cfg->MY_INSTITUTES_INCLUDE_CHILDREN) {
                // If the institute is not a faculty or the child insts are included,
                // pick the institute IDs of the faculty/institute and of all sub-institutes.
                $inst_ids[] = $inst_id;
                if ($institut->isFaculty()) {
                    foreach ($institut->sub_institutes->pluck("Institut_id") as $institut_id) {
                        $inst_ids[] = $institut_id;
                    }
                }
            } else {
                // If the institute is a faculty and the child insts are not included,
                // pick only the institute id of the faculty:
                $inst_ids[] = $inst_id;
            }
        }

        $filter = AdminCourseFilter::get(true);

        if ($params['datafields']) {
            //enable filtering by datafield values:
            $filter->settings['query']['joins']['datafields_entries'] = [
                'join' => "INNER JOIN",
                'on' => "seminare.seminar_id = datafields_entries.range_id"
            ];

            //and use the where-clause for each datafield:

            foreach ($params['datafields'] as $fieldId => $fieldValue) {
                $filter->where("datafields_entries.datafield_id = :fieldId "
                    . "AND datafields_entries.content = :fieldValue",
                    [
                        'fieldId' => $fieldId,
                        'fieldValue' => $fieldValue
                    ]
                );
            }

        }

        $filter->where("sem_classes.studygroup_mode = '0'");

        // Get only children of given course
        if ($params['parent_course']) {
            $filter->where("parent_course = :parent",
                [
                    'parent' => $params['parent_course']
                ]
            );
        }

        if (is_object($this->semester)) {
            $filter->filterBySemester($this->semester->getId());
        }
        if ($params['typeFilter'] && $params['typeFilter'] !== "all") {
            list($class_filter,$type_filter) = explode('_', $params['typeFilter']);
            if (!$type_filter && !empty($GLOBALS['SEM_CLASS'][$class_filter])) {
                $type_filter = array_keys($GLOBALS['SEM_CLASS'][$class_filter]->getSemTypes());
            }
            $filter->filterByType($type_filter);
        }
        if ($GLOBALS['user']->cfg->ADMIN_COURSES_SEARCHTEXT) {
            $filter->filterBySearchString($GLOBALS['user']->cfg->ADMIN_COURSES_SEARCHTEXT);
        }
        if ($GLOBALS['user']->cfg->ADMIN_COURSES_TEACHERFILTER && ($GLOBALS['user']->cfg->ADMIN_COURSES_TEACHERFILTER !== "all")) {
            $filter->filterByDozent($GLOBALS['user']->cfg->ADMIN_COURSES_TEACHERFILTER);
        }
        $filter->filterByInstitute($inst_ids);
        if ($params['sortby'] === "status") {
            $filter->orderBy(sprintf('sem_classes.name %s, sem_types.name %s, VeranstaltungsNummer', $params['sortFlag'], $params['sortFlag'], $params['sortFlag']), $params['sortFlag']);
        } elseif ($params['sortby'] === 'completion') {
            $filter->orderBy('is_complete', $params['sortFlag']);
        } elseif ($params['sortby']) {
            $filter->orderBy($params['sortby'], $params['sortFlag']);
        }
        $filter->storeSettings();
        $this->count_courses = $filter->countCourses();
        if ($this->count_courses && $this->count_courses <= $filter->max_show_courses) {
            $courses = $filter->getCourses();
        } else {
            return [];
        }

        if (in_array('contents', $params['view_filter'])) {
            $sem_types = SemType::getTypes();
            $modules = new Modules();
        }

        $seminars = array_map('reset', $courses);

        if (!empty($seminars)) {
            foreach ($seminars as $seminar_id => $seminar) {
                $seminars[$seminar_id]['seminar_id'] = $seminar_id;
                $seminars[$seminar_id]['obj_type'] = 'sem';
                $dozenten = $this->getTeacher($seminar_id);
                $seminars[$seminar_id]['dozenten'] = $dozenten;

                if (in_array('contents', $params['view_filter'])) {
                    $seminars[$seminar_id]['sem_class'] = $sem_types[$seminar['status']]->getClass();
                    $seminars[$seminar_id]['modules'] = $modules->getLocalModules($seminar_id, 'sem', $seminar['modules'], $seminar['status']);
                    $seminars[$seminar_id]['navigation'] = MyRealmModel::getAdditionalNavigations($seminar_id, $seminars[$seminar_id], $seminars[$seminar_id]['sem_class'], $GLOBALS['user']->id);
                }
                //add last activity column:
                if (in_array('last_activity', $params['view_filter'])) {
                    $seminars[$seminar_id]['last_activity'] = lastActivity($seminar_id);
                }
                if ($this->selected_action == 17) {
                    $seminars[$seminar_id]['admission_locked'] = false;
                    if ($seminar['course_set']) {
                        $set = new CourseSet($seminar['course_set']);
                        if (!is_null($set) && $set->hasAdmissionRule('LockedAdmission')) {
                            $seminars[$seminar_id]['admission_locked'] = 'locked';
                        } else {
                            $seminars[$seminar_id]['admission_locked'] = 'disable';
                        }
                        unset($set);
                    }
                }
            }
        }

        return $seminars;
    }

    protected function getDatafieldFilters()
    {
        //first get the active datafields of the user:
        $userConfig = UserConfig::get(User::findCurrent()->id);
        $userSelectedElements = json_decode($userConfig->getValue('ADMIN_COURSES_SIDEBAR_ACTIVE_ELEMENTS'), true);

        $activeDatafields = $userSelectedElements['datafields'];

        if (!$activeDatafields) {
            return [];
        }

        //Ok, we have a list of active datafields whose value may be searched for.
        //We must check for the request parameters (df_$DATAFIELD_ID)
        //and return their IDs with a value.

        $searchedDatafields = [];

        foreach ($activeDatafields as $activeField) {
            $requestParamValue = Request::get('df_'.$activeField);
            if ($requestParamValue) {
                $searchedDatafields[$activeField] = $requestParamValue;
            }
        }

        return $searchedDatafields;
    }

    protected function getTeacher($course_id)
    {
        $teachers   = CourseMember::findByCourseAndStatus($course_id, 'dozent');
        $collection = SimpleCollection::createFromArray($teachers);
        return $collection->map(function (CourseMember $teacher) {
            return [
                'user_id'  => $teacher->user_id,
                'username' => $teacher->username,
                'Nachname' => $teacher->nachname,
                'fullname' => $teacher->getUserFullname('no_title_rev'),
            ];
        });
    }

}
