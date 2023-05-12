<? if (count($profiles) === 0) : ?>
    <h3><?= dgettext("evasys", "Keine verfügbaren Evaluationen") ?></h3>
<? endif ?>

<? $split = false ?>
<? foreach ($profiles as $profile) : ?>
    <? if ($profile['split']) {
        $split = true;
    } ?>

    <? $student_infotext = trim($profile->getPresetAttribute("student_infotext")) ?>
    <? if ($student_infotext) : ?>
        <div class="evasysmessagebox">
            <? if ($GLOBALS['perm']->have_studip_perm('dozent', Context::get()->id)) : ?>
                <div class="legend">
                    <?= dgettext("evasys", "Infotext für Studierende") ?>
                </div>
            <? endif ?>
            <?= formatReady($student_infotext) ?>
        </div>
    <? endif ?>

    <? if ($profile['split']) : ?>
        <? $tabs = [] ?>
        <div id="evasys_tabs">
            <ul>
                <? foreach ($profile['teachers'] as $user_id) : ?>
                    <li>
                        <a href="<?= PluginEngine::getLink($plugin, [], "evaluation/show#tab-".$user_id) ?>">
                            <?= htmlReady(get_fullname($user_id)) ?>
                        </a>
                    </li>
                <? endforeach ?>
            </ul>
            <? foreach ($profile['teachers'] as $i => $user_id) : ?>
                <div id="tab-<?= htmlReady($user_id) ?>">
                    <? if ($GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id)) : ?>
                        <?= $this->render_partial("evaluation/_dozent.php", [
                            'profile' => $profile,
                            'dozent_ids' => [$user_id],
                            'dozent_id' => $user_id
                        ]) ?>
                    <? else : ?>
                        <?= $this->render_partial("evaluation/_student.php", [
                            'profile' => $profile,
                            'dozent_id' => $user_id
                        ]) ?>
                    <? endif ?>
                </div>
                <? $tabs['#tab-'.$user_id] = $i ?>
            <? endforeach ?>
        </div>

        <script>
            jQuery(function () {
                var tabs = <?= json_encode($tabs) ?>;
                jQuery("#evasys_tabs").tabs({
                    "active": typeof tabs[window.location.hash] !== "undefined"
                        ? tabs[window.location.hash]
                        : 0
                });
            });
        </script>
    <? else : ?>
        <? if ($GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id)) : ?>
            <?= $this->render_partial("evaluation/_dozent.php", [
                'profile' => $profile,
                'dozent_ids' => $profile['teachers'] ? $profile['teachers']->getArrayCopy() : []
            ]) ?>
        <? else : ?>
            <?= $this->render_partial("evaluation/_student.php", [
                'profile' => $profile
            ]) ?>
        <? endif ?>

    <? endif ?>

<? endforeach ?>



<?

if ($GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id)) {
    $actions = new ActionsWidget();
    $base_url = URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']);
    $actions->addLink(
        dgettext("evasys", "QR-Code für Studierende anzeigen"),
        PluginEngine::getURL($plugin, [], 'evaluation/show'.($split && $GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id) && !$GLOBALS['perm']->have_perm("admin") ? '#tab-'.$GLOBALS['user']->id : '')),
        Icon::create("code-qr", "clickable"),
        ['data-qr-code' => sprintf(dgettext('evasys', 'Evaluation zur Veranstaltung %s'), Context::get()->getFullname('number-name'))]
    );
    URLHelper::setBaseURL($base_url);
    Sidebar::Get()->addWidget($actions);

    if (Config::get()->EVASYS_PUBLISH_RESULTS && !$GLOBALS['perm']->have_perm("admin")) {
        $publish = $evasys_seminar && $evasys_seminar->publishingAllowed($GLOBALS['user']->id);
        $option = new OptionsWidget();
        $option->addCheckbox(
            dgettext("evasys", "Veröffentlichung der Ergebnisse an Studenten erlauben."),
            $publish,
            PluginEngine::getURL($plugin, ['dozent_vote' => "y"], "evaluation/toggle_publishing"),
            PluginEngine::getURL($plugin, ['dozent_vote' => "n"], "evaluation/toggle_publishing")
        );
        Sidebar::Get()->addWidget($option);
    }
}

