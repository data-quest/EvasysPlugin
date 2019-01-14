<? if ($GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id)) : ?>
    <?= $this->render_partial("evaluation/_survey_dozent.php", array(
        'surveys' => $surveys,
        'evasys_seminar' => $evasys_seminars[0],
        'dozent_ids' => Config::get()->EVASYS_ENABLE_PROFILES ? $profile->teachers->getArrayCopy() : array()
    )) ?>
<? else : ?>
    <?= $this->render_partial("evaluation/_survey_student.php", array(
        'surveys' => $surveys,
        'evasys_seminar' => $evasys_seminars[0]
    )) ?>
<? endif ?>



<? if ($GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id)) : ?>
    <? /* Ab der 4.1 bräuchten wir das nicht mehr */ ?>
    <div style="background-color: white; width: 100%; height: 100%; justify-content: center; align-items: center;"
         id="qr_code_evasys">
        <img style="width: 90vh; height: 90vh;">
    </div>
    <script>
        jQuery(function () {
            <? URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']) ?>
            var qrcode = new QRCode("<?= PluginEngine::getLink($plugin, array(), "evaluation/show") ?>");
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
        "#",
        Icon::create("code-qr", "clickable"),
        array('onClick' => "STUDIP.EvaSys.showQR(); return false;")
    );
    Sidebar::Get()->addWidget($actions);

    if (Config::get()->EVASYS_PUBLISH_RESULTS) {
        $publish = $evasys_seminars[0]->publishingAllowed();
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

