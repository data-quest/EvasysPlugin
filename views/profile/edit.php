<form action="<?= PluginEngine::getLink($plugin, array(), "profile/edit/".$profile['seminar_id']) ?>"
      method="post"
      data-dialog
      class="default">

    <fieldset>
        <legend>
            <?= _("Evaluationsdaten") ?>
        </legend>


        <label>
            <input type="checkbox"
                   name="data[applied]"
                   value="1"
                   onChange="jQuery('#evasys_evaldata').toggle();"
                    <?= $profile['applied'] ? " checked" : "" ?>>
            <?= _("Veranstaltung soll evaluiert werden.") ?>
        </label>

        <div<?= $profile['applied'] ? '' : ' style="display: none;"' ?> id="evasys_evaldata">
            <? $seminar = new Seminar($profile['seminar_id']) ?>
            <? $teachers = $seminar->getMembers("dozent") ?>
            <?= _("Wer wird evaluiert?") ?>
            <ul class="clean evasys_teachers">
                <?
                $active = array_flip($profile['teachers'] ? $profile['teachers']->getArrayCopy() : array());
                usort($teachers, function ($a, $b) use ($active) {
                    if (!isset($active[$a['user_id']])) {
                        return 1;
                    }
                    if (!isset($active[$b['user_id']])) {
                        return -1;
                    }
                    return $active[$a['user_id']] < $active[$b['user_id']] ? -1 : 1;
                }) ?>
                <? foreach ($teachers as $teacher) : ?>
                <li>
                    <label>
                        <span class="avatar" style="background-image: url('<?= Avatar::getAvatar($teacher['user_id'])->getURL(Avatar::MEDIUM) ?>');"></span>
                        <?= htmlReady($teacher['fullname']) ?>
                        <input type="checkbox"
                               name="data[teachers][]"
                               value="<?= htmlReady($teacher['user_id']) ?>"
                               <?= count($teachers) === 1 || !$profile['teachers'] || ($profile['teachers'] && in_array($teacher['user_id'], $profile['teachers']->getArrayCopy())) ? " checked" : "" ?>>
                        <?= Icon::create("radiobutton-unchecked", "clickable")->asImg(20) ?>
                        <?= Icon::create("check-circle", "clickable")->asImg(20) ?>
                        <span class="note">(<?= _("Wird auf dem Fragebogen genannt.") ?>)</span>
                    </label>
                </li>
                <? endforeach ?>
            </ul>

            <? if (Config::get()->EVASYS_ENABLE_SPLITTING_COURSES) : ?>
            <label>
                <input type="checkbox"
                       name="data[split]"
                       value="1"
                       <?= $profile['split'] ? " checked" : "" ?>>
                <?= _("Lehrende einzeln evaluieren") ?>
            </label>
            <? endif ?>

            <label>
                <?= _("Weitere Emails, an die die Ergebnisse gesendet werden sollen (mit Leerzeichen getrennt)") ?>
                <input type="text" name="data[results_email]" value="<?= htmlReady($profile['results_email']) ?>">
            </label>

            <label>
                <?= _("Evaluationsbeginn") ?>
                <? $begin = $profile->getFinalBegin() ?>
                <input type="text" name="data[begin]" value="<?= $begin ? date("d.m.Y H:i", $begin) : "" ?>" class="datepicker evasys_begin">
            </label>

            <label>
                <?= _("Evaluationsende") ?>
                <? $end = $profile->getFinalEnd() ?>
                <input type="text" name="data[end]" value="<?= $end ? date("d.m.Y H:i", $end) : "" ?>" class="datepicker evasys_end">
            </label>

            <? if (!$profile->hasDatesInEvalTimespan()) : ?>
                <?= MessageBox::error(_("Kein Veranstaltungstermin befindet sich in dem vorgesehenen Evaluationszeitraum!")) ?>
                <? if (count($profile->course->dates)) : ?>
                <?= _("Anderen Termin aussuchen") ?>
                <div class="evasys_propose_dates">
                    <table class="default nohover">
                        <tbody>
                        <? foreach ($profile->course->dates as $date) : ?>
                            <? if (($date['end_time'] > time()) && ($date['date'] < Semester::findCurrent()->ende)) : ?>
                            <? $found = true ?>
                            <tr>
                                <td>
                                    <? if (date("d.m.Y", $date['date']) !== date("d.m.Y", $date['end_time'])) : ?>
                                    <?= date("d.m.Y H:i", $date['date']) ?> - <?= date("d.m.Y H:i", $date['end_time']) ?>
                                    <? else : ?>
                                        <?= date("d.m.Y H:i", $date['date']) ?> - <?= date("H:i", $date['end_time']) ?>
                                    <? endif ?>
                                </td>
                                <td>
                                    <? if (count($profile->course->statusgruppen) != count($date->statusgruppen)) : ?>
                                        <? foreach ($date->statusgruppen as $i => $statusgruppe) : ?>
                                        <? if ($i > 0) : ?>
                                            ,
                                        <? endif ?>
                                        <?= htmlReady($statusgruppe['name']) ?>
                                        <? endforeach ?>
                                    <? endif ?>
                                </td>
                                <td class="actions">
                                    <a href="#" onClick="console.log(jQuery('.evasys_begin')); jQuery('.evasys_begin').val('<?= date("d.m.Y H:i", $date['date']) ?>'); jQuery('.evasys_end').val('<?= date("d.m.Y H:i", $date['end_time']) ?>'); return false;"
                                       title="<?= _("Termin als Evaluationszeitraum auswählen") ?>">
                                        <?= Icon::create("date+move_up", "clickable")->asImg(20) ?>
                                    </a>
                                </td>
                            </tr>
                            <? endif ?>
                        <? endforeach ?>
                        <? if (!$found) : ?>
                        <tr>
                            <td style="text-align: center;">
                                <?= _("Keine möglichen Termine gefunden.") ?>
                            </td>
                        </tr>
                        <? endif ?>
                        </tbody>
                    </table>
                    <? endif ?>
                </div>
            <? endif ?>

            <div style="margin-top: 10px;">
                <table class="default nohover">
                    <thead>
                        <tr>
                            <th><?= _("Ausgewähler Fragebogen") ?></th>
                            <th class="actions"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <? $standard_form_id = $profile->getPresetFormId() ?>
                        <? $form = EvasysForm::find($standard_form_id) ?>
                        <? if ($form) : ?>
                            <tr>
                                <td>
                                    <label>
                                        <input type="radio" name="data[form_id]" value="<?= htmlReady($form->getId()) ?>"<?= (!$profile['form_id'] || ($profile['form_id'] === $form->getId())) ? " checked" : "" ?>>
                                        <?= htmlReady($form['name'].": ".$form['description']) ?>
                                    </label>
                                </td>
                                <td class="actions">
                                    <? if ($form['link']) : ?>
                                    <a href="<?= htmlReady($form['link']) ?>" target="_blank">
                                        <?= Icon::create("info-circle", "clickable")->asImg(20) ?>
                                    </a>
                                    <? endif ?>
                                </td>
                            </tr>
                        <? endif ?>
                        <? foreach ($profile->getAvailableFormIds() as $form_id) : ?>
                            <? if ($form_id != $standard_form_id) : ?>
                                <? $form = EvasysForm::find($form_id) ?>
                                <tr>
                                    <td>
                                        <label>
                                            <input type="radio" name="data[form_id]" value="<?= htmlReady($form->getId()) ?>"<?= $profile['form_id'] === $form->getId() ? " checked" : "" ?>>
                                            <?= htmlReady($form['name'].": ".$form['description']) ?>
                                        </label>
                                    </td>
                                    <td class="actions">
                                        <? if ($form['link']) : ?>
                                            <a href="<?= htmlReady($form['link']) ?>" target="_blank">
                                                <?= Icon::create("info-circle", "clickable")->asImg(20) ?>
                                            </a>
                                        <? endif ?>
                                    </td>
                                </tr>
                            <? endif ?>
                        <? endforeach ?>
                    </tbody>
                </table>
            </div>

            <label>
                <?= _("Art der Evaluation") ?>
                <select name="data[mode]" onClick="jQuery('.evasys_paper').toggle(this.value === 'paper');">
                    <option value=""></option>
                    <option value="online"<?= $profile->getFinalMode() === "online" ? " selected" : "" ?>>
                        <?= _("Online-Evaluation") ?>
                    </option>
                    <option value="paper"<?= $profile->getFinalMode() === "paper" ? " selected" : "" ?>>
                        <?= _("Papierbasierte Evaluation") ?>
                    </option>
                </select>
            </label>

            <div class="evasys_paper" style="<?= $profile->getFinalMode() !== "paper" ? "display: none;" : "" ?>">
                <label>
                    <?= _("Adresse für den Versand der Fragebögen") ?>
                    <textarea name="data[address]"><?= htmlReady($profile['address']) ?></textarea>
                </label>

                <label>
                    <?= _("Sprache") ?>
                    <textarea name="data[language]"><?= htmlReady($profile['language']) ?></textarea>
                </label>

                <label>
                    <?= _("Anzahl gedruckter Fragebögen") ?>
                    <input type="text" name="data[number_of_sheets]" value="<?= htmlReady($profile['number_of_sheets']) ?>">
                </label>
            </div>
        </div>

    </fieldset>

    <? if (!$profile->isNew()) : ?>
    <fieldset>
        <legend><?= _("Log") ?></legend>

        <?= MessageBox::info(sprintf(_("Letzte Bearbeitung von %s am %s Uhr"), get_fullname($profile['user_id']), date("d.m.Y H:i", $profile['chdate'])) ) ?>

        <? if ($profile['transferred']) : ?>
            <?= MessageBox::info(_("Diese Veranstaltung wurde bereits an den Evaluationsserver übertragen.")) ?>
        <? endif ?>
    </fieldset>
    <? endif ?>

    <script>
        jQuery(function () {
            jQuery("input.datepicker").datetimepicker();
            jQuery(".evasys_teachers").sortable({
                "axis": "y",
                "handle": ".avatar"
            });
        });
    </script>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>