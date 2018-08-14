<table class="default evasys_individuelle_liste">
    <thead>
        <caption><?= htmlReady(ucfirst(EvasysMatching::wording("freiwillige Evaluationen"))) ?></caption>
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
        <? if (count($profiles)) : ?>
        <? foreach ($profiles as $profile) : ?>
            <?= $this->render_partial("individual/course", compact("profile")) ?>
        <? endforeach ?>
        <? else : ?>
        <tr>
            <td colspan="100" style="text-align: center;">
                <?= sprintf(_("Noch keine %s in diesem Semester."), EvasysMatching::wording("freiwillige Evaluationen")) ?>
            </td>
        </tr>
        <? endif ?>
        <? if ($more) : ?>
            <tr class="more" data-offset="<?= count($profiles) ?>">
                <td style="text-align: center;" colspan="100">
                    <?= Assets::img("ajax-indicator-black.svg") ?>
                </td>
            </tr>
        <? endif ?>
    </tbody>
</table>
<input type="hidden" id="semester_id" value="<?= htmlReady($semester_id) ?>">

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
                            'offset': jQuery(".evasys_individuelle_liste > tbody > tr").length - 1,
                            'semester_id': jQuery("#semester_id").val()
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
    PluginEngine::getURL($plugin, array('semester_id' => $semester_id), "individual/csv"),
    Icon::create("file-excel", "clickable")
);
Sidebar::Get()->addWidget($actions);

$semester_select = new SelectWidget(
    _("Semesterauswahl"),
    PluginEngine::getURL($plugin, array(), "individual/list"),
    'semester_id'
);
foreach (array_reverse(Semester::getAll()) as $semester) {
    $element = new SelectElement($semester->getId(), $semester['name'], $semester->getId() === $semester_id);
    $semester_select->addElement($element);
}
Sidebar::Get()->addWidget($semester_select);