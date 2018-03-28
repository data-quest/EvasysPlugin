<form action="<?= PluginEngine::getLink($plugin, array(), $con."/edit") ?>"
      method="post"
      class="default">

    <fieldset>
        <legend>
            <?= _("Standarddaten der Evaluationen") ?>
        </legend>
        <label>
            <?= _("Beginn") ?>
            <input type="text" name="data[begin]" value="<?= $profile['begin'] ? date("d.m.Y H:i", $profile['begin']) : "" ?>" class="datepicker">
        </label>

        <label>
            <?= _("Ende") ?>
            <input type="text" name="data[end]" value="<?= $profile['end'] ? date("d.m.Y H:i", $profile['end']) : "" ?>" class="datepicker">
        </label>

        <label>
            <?= _("Standardfragebogen (nur aktive werden angezeigt)") ?>
            <select name="data[form_id]" class="select2">
                <option value=""></option>
                <? foreach (EvasysForm::findBySQL("active = '1' ORDER BY name ASC") as $form) : ?>
                    <option value="<?= htmlReady($form->getId()) ?>"<?= $form->getId() == $profile['form_id'] ? " selected" : "" ?>>
                        <?= htmlReady($form['name']) ?>
                    </option>
                <? endforeach ?>
            </select>
        </label>

        <label>
            <?= _("Art der Evaluation") ?>
            <select name="data[mode]">
                <option value=""></option>
                <option value="paper"<?= $profile['mode'] === "paper" ? " selected" : "" ?>>
                    <?= _("Papierbasierte Evaluation") ?>
                </option>
                <option value="online"<?= $profile['mode'] === "online" ? " selected" : "" ?>>
                    <?= _("Online-Evaluation") ?>
                </option>
            </select>
        </label>

        <label>
            <?= _("Adresse für den Versand der Fragebögen") ?>
            <textarea name="data[address]"><?= htmlReady($profile['address']) ?></textarea>
        </label>

    </fieldset>

    <fieldset>
        <legend>
            <?= _("Standardfragebögen nach Veranstaltungstypen") ?>
        </legend>

        <table class="default">
            <tbody>
                <? foreach (SemType::getTypes() as $sem_type) : ?>
                <tr>
                    <td>
                        <?= htmlReady($GLOBALS['SEM_CLASS'][$sem_type['class']]['name']) ?>: <?= htmlReady($sem_type['name']) ?>
                    </td>
                    <td>
                        <label>
                            <div>
                                <?= _("Standardfragebogen") ?>
                            </div>
                            <select name="forms_by_type[<?= $sem_type['id'] ?>]" class="select2">
                                <option value=""></option>
                                <? foreach (EvasysForm::findBySQL("active = '1' ORDER BY name ASC") as $form) : ?>
                                    <option value="<?= htmlReady($form->getId()) ?>"<?= $forms_by_type[$sem_type['id']][0] == $form->getId() ? " selected" : "" ?>>
                                        <?= htmlReady($form['name']) ?>
                                    </option>
                                <? endforeach ?>
                            </select>
                        </label>

                        <label>
                            <div>
                                <?= _("Verfügbar") ?>
                            </div>
                            <select name="available_forms_by_type[<?= $sem_type['id'] ?>][]" multiple class="select2">
                                <option value=""></option>
                                <? foreach (EvasysForm::findBySQL("active = '1' ORDER BY name ASC") as $form) : ?>
                                    <option value="<?= htmlReady($form->getId()) ?>"<?= in_array($form->getId(), (array) $available_forms_by_type[$sem_type['id']]) ? " selected" : "" ?>>
                                        <?= htmlReady($form['name']) ?>
                                    </option>
                                <? endforeach ?>
                            </select>
                        </label>
                    </td>
                </tr>
                <? endforeach ?>
            </tbody>
        </table>
    </fieldset>

    <script>
        jQuery(function () {
            jQuery("input.datepicker").datetimepicker();
            jQuery(".select2").select2();
            jQuery(".select2").select2();
        });
    </script>
    <style>
        .ui-widget, #layout_wrapper #barBottomContainer {
            z-index: 100000 !important;
        }
    </style>

    <div style="text-align: center;">
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>

</form>