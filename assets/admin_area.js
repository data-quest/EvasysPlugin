STUDIP.Evasys = {
    refreshCourseInOverview: function (course_id) {
        jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/evasysplugin/admin/course_tr/" + course_id, function(data) {
            jQuery("#course-" + course_id).replaceWith(data);
        });
    }
};