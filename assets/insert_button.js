jQuery(function () {
    var buttonhtml = "<button class='button' type='submit' formaction='" + STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/evasysplugin/profile/bulkedit'>Bearbeiten</button>";
    jQuery("table.course-admin > tfoot > tr > td").prepend(jQuery(buttonhtml));
    if (jQuery("table.course-admin > thead > tr").length > 2) {
        jQuery("table.course-admin > thead > tr:last-of-type > th").prepend(jQuery(buttonhtml));
    }
});