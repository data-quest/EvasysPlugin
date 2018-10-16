<div style="padding: 10px;">
    <table class="default">
        <thead>
            <tr>
                <th>
                    <?= _("Veranstaltung") ?>
                </th>
                <th>
                    <?= _("Evaluation bis") ?>
                </th>
                <th>
                    <?= _("Rücklaufquote") ?>
                </th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <? if (count($courses)) : ?>
                <? foreach ($courses as $course) : ?>
                    <? $profile = EvasysCourseProfile::findBySemester($course->getId()) ?>
                    <? foreach ($surveys[$course->getId()] as $survey) : ?>
                        <tr>
                            <td>
                                <a href="<?= URLHelper::getLink("plugins.php/evasysplugin/evaluation/show", array('cid' => $course->getId()), true) ?>">
                                    <?= htmlReady($course['name']) ?>
                                </a>
                            </td>
                            <td><?= date("d.m.Y G:i", $profile->getFinalEnd()) ?></td>
                            <? $return = round(100 * $survey->m_nFormCount / ($survey->m_nPswdCount ?: 1)) ?>
                            <? $color = $return >= 80 ? '#8bbd40' : ($return >= 30 ? '#a1aec7' : '#d60000') ?>
                            <td style="background-image: linear-gradient(0deg, <?= $color ?>, <?= $color ?>); background-repeat: no-repeat; background-size: <?= (int) $return ?>% 100%; width: 150px;">
                                <?= (int) $return ?>%
                            </td>
                            <td>
                                <? $evasys_seminar = new EvasysSeminar($course->getId()) ?>
                                <? $pdf_link = $evasys_seminar->getPDFLink($survey->m_nSurveyId) ?>
                                <? if ($pdf_link) : ?>
                                    <a href="<?= htmlReady($pdf_link) ?>" target="_blank" title="<?= _("Ergebnisse ansehen") ?>">
                                        <?= Icon::create("file-pdf", "clickable")->asImg(20) ?>
                                    </a>
                                <? else : ?>
                                    <?= Icon::create("file-pdf", "inactive")->asImg(20, array('title' => _("Es liegen noch keine Ergebnisse vor."))) ?>
                                <? endif ?>
                            </td>
                        </tr>
                    <? endforeach ?>
                <? endforeach ?>
            <? else : ?>
                <tr>
                    <td colspan="100" style="text-align: center;">
                        <?= _("Keine laufenden Evaluationen") ?>
                    </td>
                </tr>
            <? endif ?>
        </tbody>
    </table>
</div>