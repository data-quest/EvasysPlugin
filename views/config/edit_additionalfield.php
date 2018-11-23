<form class="default"
      method="post"
      action="<?= PluginEngine::getLink($plugin, array(), "config/edit_additionalfield/".$field->getId()) ?>">

    <fieldset>
        <legend><?= _("Daten des Feldes") ?></legend>

        <label>
            <?= _("Name") ?>
            <input type="text" name="data[name]" value="<?= htmlReady($field['name']) ?>">
        </label>

        <label>
            <input type="hidden" name="data[paper]" value="0">
            <input type="checkbox" name="data[paper]" value="1"<?= $field['paper'] ? " checked" : "" ?>>
            <?= _("Nur für papierbasierte Evaluationen") ?>
        </label>

        <label>
            <input type="radio" name="data[type]" value="TEXT"<?= $field->isNew() || ($field['type'] === "TEXT") ? " checked" : "" ?>>
            <?= _("Text") ?>
        </label>
        <label>
            <input type="radio" name="data[type]" value="TEXTAREA"<?= $field['type'] === "TEXTAREA" ? " checked" : "" ?>>
            <?= _("Großes Textfeld") ?>
        </label>

        <label>
            <?= _("Reihenfolge") ?>
            <input type="number" name="data[position]" value="<?= htmlReady($field['position'] ?: 1) ?>">

        </label>
    </fieldset>
    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>