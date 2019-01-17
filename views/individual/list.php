<form action="<?= PluginEngine::getURL($plugin, array('individual' => 1), "profile/bulkedit") ?>" method="post" data-dialog>
    <table class="default evasys_individuelle_liste">
        <caption><?= htmlReady(ucfirst(EvasysMatching::wording("freiwillige Evaluationen"))) ?></caption>
        <thead>
            <tr>
                <th><?= _("Nummer") ?></th>
                <th><?= _("Veranstaltung") ?></th>
                <th><?= _("Dozenten") ?></th>
                <th><?= _("Evaluationszeitraum") ?></th>
                <? if (!Config::get()->EVASYS_FORCE_ONLINE) : ?>
                    <th><?= _("Art") ?></th>
                <? endif ?>
                <th class="actions">
                    <?= _("Aktion") ?>
                    <input type="checkbox" data-proxyfor=".evasys_individuelle_liste > tbody :checkbox">
                </th>
            </tr>
        </thead>

        <tbody>
            <? if (count($profiles)) : ?>
            <? foreach ($profiles as $profile) : ?>
                <?= $this->render_partial("individual/course", array(
                        'profile' => $profile,
                        'semesters' => array($semester)
                    )) ?>
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
        <tfoot>
            <tr>
                <td colspan="100" style="text-align: right;">
                    <?= \Studip\Button::create(_("Bearbeiten")) ?>
                </td>
            </tr>
        </tfoot>
    </table>
</form>
<input type="hidden" id="semester_id" value="<?= htmlReady($semester->getId()) ?>">

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
    PluginEngine::getURL($plugin, array('semester_id' => $semester->getId()), "individual/csv"),
    Icon::create("file-excel", "clickable")
);
Sidebar::Get()->addWidget($actions);

$semester_select = new SelectWidget(
    _("Semesterauswahl"),
    PluginEngine::getURL($plugin, array(), "individual/list"),
    'semester_id'
);
foreach (array_reverse(Semester::getAll()) as $s) {
    $element = new SelectElement($s->getId(), $s['name'], $s->getId() === $semester->getId());
    $semester_select->addElement($element);
}
Sidebar::Get()->addWidget($semester_select);