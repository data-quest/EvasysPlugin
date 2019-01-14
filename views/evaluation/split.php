<? $tabs = array() ?>
<div id="evasys_tabs">
    <ul>
        <? foreach ($profile['teachers'] as $user_id) : ?>
        <li>
            <a href="<?= PluginEngine::getLink($plugin, array(), "evaluation/split#tab-".$user_id) ?>">
                <?= htmlReady(get_fullname($user_id)) ?>
            </a>
        </li>
        <? endforeach ?>
    </ul>
    <? foreach ($profile['teachers'] as $i => $user_id) : ?>
        <div id="tab-<?= htmlReady($user_id) ?>">
            <? if ($user_id === $GLOBALS['user']->id) : ?>
                <?= $this->render_partial("evaluation/_survey_dozent.php", array(
                    'surveys' => $surveys[$user_id],
                    'evasys_seminar' => $evasys_seminars[$user_id],
                    'dozent_ids' => array($user_id)
                )) ?>
            <? else : ?>
                <?= $this->render_partial("evaluation/_survey_student.php", array(
                    'surveys' => $surveys[$user_id],
                    'evasys_seminar' => $evasys_seminars[$user_id],
                    'dozent_id' => $user_id
                )) ?>
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


<? if ($GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id)) : ?>
    <? /* Ab der 4.1 bräuchten wir das nicht mehr */ ?>
    <div style="background-color: white; width: 100%; height: 100%; justify-content: center; align-items: center;"
         id="qr_code_evasys">
        <img style="width: 90vh; height: 90vh;">
    </div>
    <script>
        jQuery(function () {
            <? URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']) ?>
            var qrcode = new QRCode("<?= PluginEngine::getLink($plugin, array(), "show#tab-".$GLOBALS['user']->id) ?>");
            var svg = qrcode.svg();
            jQuery("#qr_code_evasys img").attr("src", "data:image/svg+xml;base64," + btoa(svg));
        });
        STUDIP.EvaSys = {
            showQR: function () {
                var qr = jQuery("#qr_code_evasys")[0];
                if (qr.requestFullscreen) {
                    qr.requestFullscreen();
                } else if (qr.msRequestFullscreen) {
                    qr.msRequestFullscreen();
                } else if (qr.mozRequestFullScreen) {
                    qr.mozRequestFullScreen();
                } else if (qr.webkitRequestFullscreen) {
                    qr.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
                }
            }
        };
    </script>
<? endif ?>


<?
Sidebar::Get()->setImage("sidebar/evaluation-sidebar.png");

if ($GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id)) {
    $actions = new ActionsWidget();
    $actions->addLink(
        _("QR-Code für Studierende anzeigen"),
        PluginEngine::getLink($plugin, array(), "show#tab-".$GLOBALS['user']->id),
        Icon::create("code-qr", "clickable"),
        array('onClick' => "STUDIP.EvaSys.showQR(); return false;")
    );
    Sidebar::Get()->addWidget($actions);

    if (Config::get()->EVASYS_PUBLISH_RESULTS && !$GLOBALS['perm']->have_perm("admin")) {
        $publish = $evasys_seminar->publishingAllowed($GLOBALS['user']->id);
        $option = new OptionsWidget();
        $option->addCheckbox(
            _("Veröffentlichung der Ergebnisse an Studenten erlauben."),
            $publish,
            PluginEngine::getURL($plugin, array('dozent_vote' => "y"), "evaluation/toggle_publishing"),
            PluginEngine::getURL($plugin, array('dozent_vote' => "n"), "evaluation/toggle_publishing")
        );
        Sidebar::Get()->addWidget($option);
    }
}

