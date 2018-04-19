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
                    </label>
                </li>
                <? endforeach ?>
            </ul>

            <label>
                <input type="checkbox"
                       name="data[split]"
                       value="1"
                       <?= $profile['split'] ? " checked" : "" ?>
                       onChange="jQuery('.evasys_teachers_results').toggle(!this.checked);">
                <?= _("Lehrende einzeln evaluieren") ?>
            </label>

            <div class="evasys_teachers_results" style="margin-top: 10px; <?= $profile['split'] ? "display: none; " : "" ?>">
                <?= _("Wer bekommt die Evaluationsergebnisse?") ?>
                <ul class="clean evasys_teachers">
                    <? foreach ($teachers as $teacher) : ?>
                        <li>
                            <label>
                                <span class="avatar" style="background-image: url('<?= Avatar::getAvatar($teacher['user_id'])->getURL(Avatar::MEDIUM) ?>');"></span>
                                <?= htmlReady($teacher['fullname']) ?>
                                <input type="checkbox"
                                       name="data[teachers_results][]"
                                       value="<?= htmlReady($teacher['user_id']) ?>"
                                    <?= count($teachers) === 1 || !$profile['teachers_results'] || ($profile['teachers_results'] && in_array($teacher['user_id'], $profile['teachers_results']->getArrayCopy())) ? " checked" : "" ?>>
                                <?= Icon::create("radiobutton-unchecked", "clickable")->asImg(20) ?>
                                <?= Icon::create("check-circle", "clickable")->asImg(20) ?>
                            </label>
                        </li>
                    <? endforeach ?>
                </ul>
            </div>

            <label>
                <?= _("Weitere Emails, an die die Ergebnisse gesendet werden sollen (mit Leerzeichen getrennt)") ?>
                <input type="text" name="data[results_email]" value="<?= htmlReady($profile['results_email']) ?>">
            </label>

            <label>
                <?= _("Evaluationsbeginn") ?>
                <? $begin = $profile->getFinalBegin() ?>
                <input type="text" name="data[begin]" value="<?= $begin ? date("d.m.Y H:i", $begin) : "" ?>" class="datepicker">
            </label>

            <label>
                <?= _("Evaluationsende") ?>
                <? $end = $profile->getFinalEnd() ?>
                <input type="text" name="data[end]" value="<?= $end ? date("d.m.Y H:i", $end) : "" ?>" class="datepicker">
            </label>

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
                                        <?= htmlReady($form['name']) ?>
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
                                            <?= htmlReady($form['name']) ?>
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
                    <option value="paper"<?= $profile->getFinalMode() === "paper" ? " selected" : "" ?>>
                        <?= _("Papierbasierte Evaluation") ?>
                    </option>
                    <option value="online"<?= $profile->getFinalMode() === "online" ? " selected" : "" ?>>
                        <?= _("Online-Evaluation") ?>
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
                    <select name="data[language]">
                        <? foreach ($GLOBALS['INSTALLED_LANGUAGES'] as $key => $language) : ?>
                        <option value="<?= htmlReady($key) ?>"<?= $profile['language'] === $key ? " selected" : "" ?>>
                            <?= htmlReady($language['name']) ?>
                        </option>
                        <? endforeach ?>
                    </select>
                </label>

                <label>
                    <?= _("Anzahl gedruckter Fragebögen") ?>
                    <input type="text" name="data[number_of_sheets]" value="<?= htmlReady($profile['number_of_sheets']) ?>">
                </label>
            </div>
        </div>

    </fieldset>

    <script>
        jQuery(function () {
            jQuery("input.datepicker").datetimepicker();
        });
    </script>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>