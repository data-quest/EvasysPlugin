<table class="default evasys_individuelle_liste">
    <thead>
        <tr>
            <th><?= _("Nummer") ?></th>
            <th><?= _("Veranstaltung") ?></th>
            <th><?= _("Dozenten") ?></th>
            <th><?= _("Evaluationszeitraum") ?></th>
            <? if (!Config::get()->EVASYS_FORCE_ONLINE) : ?>
                <th><?= _("Art") ?></th>
            <? endif ?>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <? foreach ($profiles as $profile) : ?>
            <?= $this->render_partial("individual/course", compact("profile")) ?>
        <? endforeach ?>
        <? if ($more) : ?>
            <tr class="more" data-offset="<?= count($profiles) ?>">
                <td style="text-align: center;" colspan="100">
                    <?= Assets::img("ajax-indicator-black.svg") ?>
                </td>
            </tr>
        <? endif ?>
    </tbody>
</table>

<script>
    jQuery(function () {

        /*********** infinity-scroll in the overview ***********/
        if (jQuery(".evasys_individuelle_liste tbody tr.more").length > 0) {
            jQuery(window.document).on('scroll', _.throttle(function (event) {

                if ((jQuery(window).scrollTop() + jQuery(window).height() > jQuery(window.document).height() - 500)
                    && (jQuery(".evasys_individuelle_liste tbody tr.more").length > 0)) {
                    //nachladen
                    jQuery.ajax({
                        url: STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/evasysplugin/individual/more",
                        data: {
                            'offset': jQuery(".evasys_individuelle_liste > tbody > tr").length - 1
                        },
                        dataType: "json",
                        success: function (response) {
                            jQuery.each(response.profiles, function (index, profile) {
                                jQuery(profile).insertBefore(".evasys_individuelle_liste > tbody > tr.more");
                            });

                            if (!response.more) {
                                jQuery(".evasys_individuelle_liste tbody tr.more").remove();
                            } else {
                                jQuery(".evasys_individuelle_liste tbody tr.more").data(
                                    "offset",
                                    parseInt(jQuery(".evasys_individuelle_liste tbody tr.more").data("offset"), 10)
                                        + response.profiles.length
                                );
                            }
                        }
                    });
                }
            }, 30));
        }
        STUDIP.Evasys = {
            refreshCourseInOverview: function (course_id) {
                jQuery.get(STUDIP.ABSOLUTE_URI_STUDIP + "plugins.php/evasysplugin/individual/course/" + course_id, function(data) {
                    console.log("#course-" + course_id);
                    jQuery("#course-" + course_id).replaceWith(data);
                });
            }
        };
    });

</script>

<?
$actions = new ActionsWidget();
$actions->addLink(
    _("Export als CSV"),
    PluginEngine::getURL($plugin, array(), "individual/csv"),
    Icon::create("file-excel", "clickable")
);
Sidebar::Get()->addWidget($actions);
