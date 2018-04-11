jQuery(function () {
    var button = jQuery("<button class='button' type='submit' formaction='" + STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/evasysplugin/admin/bulkedit'>Bearbeiten</button>");
    jQuery("table.course-admin > tfoot > tr > td").prepend(button);
});