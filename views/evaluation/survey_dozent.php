<?php

/*
 *  Copyright (c) 2012  Rasmus Fuhse <fuhse@data-quest.de>
 *
 *  This program is free software; you can redistribute it and/or
 *  modify it under the terms of the GNU General Public License as
 *  published by the Free Software Foundation; either version 2 of
 *  the License, or (at your option) any later version.
 */
?>
<? if (!$surveys && !count($surveys)) : ?>
<h3><?= _("Es gibt keine aktuellen Evaluationen zu dieser Veranstaltung.") ?></h3>
<? else : ?>
<style>
    table.active_table {
        border-collapse: collapse;
        margin: 10px;
    }
    table.active_table > thead > tr > th {
        padding: 5px;
        border: 1px solid lightgrey;
        border-bottom: 1px solid grey;
        background-image: none;
    }
    table.active_table > tbody > tr > td {
        padding: 5px;
        border: 1px solid lightgrey;
    }
    table.active_table > tbody > tr:hover > td {
        background-color: #eeeeee;
    }

    #qr_code {
        display: none;
    }
    #qr_code:-moz-full-screen {
         display: flex;
     }
    #qr_code:-webkit-full-screen {
         display: flex;
     }
    #qr_code:-ms-fullscreen {
         display: flex;
     }
    #qr_code:fullscreen {
         display: flex;
    }

</style>
<div style="padding: 15px; font-size: 1.2em; text-align: center;">
    <? if (count($surveys) < 2) : ?>
    <h3><?= _("Es gibt eine laufende Evaluation zu dieser Veranstaltung") ?></h3>
    <? elseif(!count($surveys)) : ?>
    <h3><?= _("Keine verfügbaren Evaluationen") ?></h3>
    <? else : ?>
    <h3><?= _("Evaluationen zu dieser Veranstaltung") ?></h3>
    <? endif ?>
    
    <? foreach ($surveys as $survey) : ?>
    
        <table class="active_table" style="margin-left: auto; margin-right: auto;">
            <tbody>
                <tr>
                    <td><?= _("Semester") ?></td>
                    <td><?= htmlReady(Semester::findByTimestamp(strtotime($survey->m_oPeriod->m_sEndDate))->name ?: $survey->m_oPeriod->m_sTitel) ?></td>
                </tr>
                <tr>
                    <td><?= _("Papier/Online-Umfrage") ?></td>
                    <td><?= $survey->m_cType == "o" ? _("Onlineumfrage") : _("Papierumfrage") ?></td>
                </tr>
                <tr>
                    <td><?= _("Aktueller Rücklauf") ?></td>
                    <td>
                        <? if ($survey->m_nFormCount == 0) : ?>
                            <?= _("Noch keine Antworten abgegeben") ?>
                        <? elseif ($survey->m_nFormCount == 1) : ?>
                            <?= _("Ein abgegebener Fragebogen") ?>
                        <? else : ?>
                            <?= htmlReady(sprintf("%s abgegebene Fragebögen", $survey->m_nFormCount)) ?>
                        <? endif ?>
                    </td>
                </tr>
                <tr>
                    <td><?= _("Status") ?></td>
                    <td><? switch($survey->m_nState) {
                        case 0:
                            echo _("Bereit / Keine Daten vorhanden");
                            break;
                        case 1:
                            echo _("Daten vorhanden / Bereit zur Auswertung");
                            break;
                        case 4:
                            echo _("Daten unter Mindestrücklauf");
                            break;
                        case 5:
                            echo _("Validierung / Datenerfassungskraft");
                            break;
                        case 6:
                            echo _("Verifikation");
                    } ?></td>
                </tr>
                <? $pdf_link = $evasys_seminar->getPDFLink($survey->m_nSurveyId) ?>
                <? if ($pdf_link) : ?>
                <tr>
                    <td><?= _("Auswertung der Evaluation") ?></td>
                    <td>
                        <a href="<?= htmlReady($pdf_link) ?>" target="_blank">
                            <img src="<?= htmlReady($GLOBALS['ABSOLUTE_URI_STUDIP'].$plugin->getPluginPath()."/file-pdf.png") ?>" title="<?= _("Testergebnisse als PDF") ?>">
                            <?= _("Ergebnisse als PDF") ?>
                        </a>
                    </td>
                </tr>
                <? endif ?>
            </tbody>
        </table>
    <? endforeach ?>
    
    <? if ($GLOBALS['perm']->have_studip_perm("dozent", $_SESSION['SessionSeminar'])) : ?>
    <?= _("Zum Administrieren dieser Evaluation melden Sie sich bitte in EvaSys an oder wenden Sie sich an Ihren Administrator.") ?>
    <? endif ?>

</div>
<? endif ?>

<? if ($GLOBALS['perm']->have_studip_perm("dozent", $_SESSION['SessionSeminar'])) : ?>
    <div style="background-color: white; width: 100%; height: 100%; justify-content: center; align-items: center;"
         id="qr_code">
        <img style="width: 90vh; height: 90vh;">
    </div>
    <script>
        jQuery(function () {
            <? URLHelper::setBaseURL($GLOBALS['ABSOLUTE_URI_STUDIP']) ?>
            var qrcode = new QRCode("<?= PluginEngine::getLink($plugin, array(), "show") ?>");
            var svg = qrcode.svg();
            console.log("data:image/svg+xml;base64," + btoa(svg));
            jQuery("#qr_code img").attr("src", "data:image/svg+xml;base64," + btoa(svg));
        });
        STUDIP.EvaSys = {
            showQR: function () {
                var qr = jQuery("#qr_code")[0];
                //jQuery("#qr_code").css("display", "flex");
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
$publish = $evasys_seminar->publishingAllowed();
if ($GLOBALS['perm']->have_studip_perm("dozent", $_SESSION['SessionSeminar'])) {
    $actions = new ActionsWidget();
    if (get_config("EVASYS_PUBLISH_RESULTS")) {
        if ($publish) {
            $actions->addLink(
                _("Veröffentlichung der Ergebnisse an Studenten verbieten."),
                URLHelper::getURL("?", array('dozent_vote' => "n")),
                Assets::image_path("icons/16/blue/lock-locked")
            );
        } else {
            $actions->addLink(
                _("Veröffentlichung der Ergebnisse an Studenten erlauben."),
                URLHelper::getURL("?", array('dozent_vote' => "y")),
                Assets::image_path("icons/16/blue/lock-unlocked")
            );
        }
    }
    $actions->addLink(
        _("QR-Code für Studierende anzeigen"),
        "#",
        Assets::image_path("icons/16/blue/code-qr"),
        array('onClick' => "STUDIP.EvaSys.showQR(); return false;")
    );
    Sidebar::Get()->addWidget($actions);
}

