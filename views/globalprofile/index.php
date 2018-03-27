<form action="<?= PluginEngine::getLink($plugin, array(), "globalprofile/edit") ?>"
      method="post"
      class="default">

    <fieldset>
        <legend>
            <?= _("Administration") ?>
        </legend>

        <label>
            <input type="checkbox" name="data[institut_profile_locked]" value="1">
            <?= _("Institutsprofile für Admins gesperrt") ?>
        </label>

    </fieldset>
    <fieldset>
        <legend>
            <?= _("Standarddaten der Evaluationen") ?>
        </legend>
        <label>
            <?= _("Beginn") ?>
            <input type="text" name="data[begin]" value="<?= $profile['begin'] ? date("d.m.Y", $profile['begin']) : "" ?>" class="datepicker">
        </label>

        <label>
            <?= _("Ende") ?>
            <input type="text" name="data[end]" value="<?= $profile['end'] ? date("d.m.Y", $profile['end']) : "" ?>" class="datepicker">
        </label>

        <label>
            <?= _("Standardfragebogen (nur aktive werden angezeigt)") ?>
            <select name="data[form_id]">
                <option value=""></option>
                <? foreach (EvasysForm::findBySQL("active = '1' ORDER BY name ASC") as $form) : ?>
                    <option value="<?= htmlReady($form->getId()) ?>"<?= $form->getId() == $profile['form_id'] ? " selected" : "" ?>>
                        <?= htmlReady($form['name']) ?>
                    </option>
                <? endforeach ?>
            </select>
        </label>
    </fieldset>

    <script>
        jQuery(function () {
            jQuery("input.datepicker").datepicker();
        });
    </script>

    <div style="text-align: center;">
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>

</form>