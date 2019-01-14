<? if (!$surveys && !count($surveys)) : ?>
    <h3><?= _("Es gibt keine aktuellen Evaluationen zu dieser Veranstaltung.") ?></h3>
<? else : ?>
    <div style="padding: 15px; font-size: 1.2em; text-align: center;">
        <? if (count($surveys) < 2) : ?>
            <h3><?= _("Es gibt eine laufende Evaluation zu dieser Veranstaltung") ?></h3>
        <? elseif(!count($surveys)) : ?>
            <h3><?= _("Keine verfügbaren Evaluationen") ?></h3>
        <? else : ?>
            <h3><?= _("Evaluationen zu dieser Veranstaltung") ?></h3>
        <? endif ?>

        <? foreach ($surveys as $survey) : ?>

            <table class="default">
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
                <? if (in_array($GLOBALS['user']->id, $dozent_ids) || $evasys_seminar->publishingAllowed($dozent_id)) : ?>
                    <? $pdf_link = $evasys_seminar->getPDFLink($survey->m_nSurveyId) ?>
                    <? if ($pdf_link) : ?>
                        <tr>
                            <td><?= _("Auswertung der Evaluation") ?></td>
                            <td>
                                <a href="<?= htmlReady($pdf_link) ?>" target="_blank">
                                    <?= Icon::create("file-pdf", "clickable")->asImg(48) ?>
                                    <?= _("Ergebnisse als PDF") ?>
                                </a>
                            </td>
                        </tr>
                    <? endif ?>
                <? endif ?>
                </tbody>
            </table>
        <? endforeach ?>

        <? if ($GLOBALS['perm']->have_studip_perm("dozent", Context::get()->id)) : ?>
            <?= _("Zum Administrieren dieser Evaluation melden Sie sich bitte in EvaSys an oder wenden Sie sich an Ihren Administrator.") ?>
        <? endif ?>

    </div>
<? endif ?>


