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
                <select name="data[mode]">
                    <option value=""></option>
                    <option value="paper"<?= $profile->getFinalMode() === "paper" ? " selected" : "" ?>>
                        <?= _("Papierbasierte Evaluation") ?>
                    </option>
                    <option value="online"<?= $profile->getFinalMode() === "online" ? " selected" : "" ?>>
                        <?= _("Online-Evaluation") ?>
                    </option>
                </select>
            </label>

            <label>
                <?= _("Adresse für den Versand der Fragebögen") ?>
                <textarea name="data[address]"><?= htmlReady($profile['address']) ?></textarea>
            </label>
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