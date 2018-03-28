<?php

class EvasysCourseProfile extends SimpleORMap {

    protected static function configure($config = array())
    {
        $config['db_table'] = 'evasys_course_profiles';
        $config['belongs_to']['course'] = array(
            'class_name' => 'Course',
            'foreign_key' => 'seminar_id'
        );
        $config['belongs_to']['semester'] = array(
            'class_name' => 'Semester',
            'foreign_key' => 'semester_id'
        );
        $config['additional_fields']['final_form_id'] = array(
            'get' => 'getFinalFormId'
        );
        $config['additional_fields']['final_begin'] = array(
            'get' => 'getFinalBegin'
        );
        $config['additional_fields']['final_end'] = array(
            'get' => 'getFinalEnd'
        );
        $config['additional_fields']['final_mode'] = array(
            'get' => 'getFinalmode'
        );
        $config['additional_fields']['final_address'] = array(
            'get' => 'getFinalAddress'
        );
        parent::configure($config);
    }

    public function getFinalFormId()
    {
        return $this->getFinishedAttribute("form_id");
    }

    public function getFinalBegin()
    {
        return $this->getFinishedAttribute("begin");
    }

    public function getFinalEnd()
    {
        return $this->getFinishedAttribute("end");
    }

    public function getFinalMode()
    {
        return $this->getFinishedAttribute("mode");
    }

    public function getFinalAddress()
    {
        return $this->getFinishedAttribute("address");
    }

    /**
     * Returns the given attribute finalized, which means that if the course-profile doesn't have this attribute set
     * we try to look at the institute-profile or the faculty-profile or the global profile and return the attribute
     * from there.
     * @param string $attribute
     * @return mixed|null
     */
    protected function getFinalAttribute($attribute)
    {
        if ($this[$attribute]) {
            return $this[$attribute];
        }
        $institut_id = $this->course['institut_id'];
        $inst_profile = EvasysInstituteProfile::findByInstitute($institut_id);
        if ($inst_profile[$attribute]) {
            return $inst_profile[$attribute];
        }
        $fakultaet_id = $this->course->home_institut->fakultaets_id;
        if ($fakultaet_id !== $institut_id) {
            $inst_profile = EvasysInstituteProfile::findByInstitute($fakultaet_id);
            if ($inst_profile[$attribute]) {
                return $inst_profile[$attribute];
            }
        }
        $global_profile = EvasysGlobalProfile::findCurrent();
        if ($global_profile[$attribute]) {
            return $global_profile[$attribute];
        }
        // else ...
        return null;
    }
}