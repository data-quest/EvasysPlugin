<form class="default"
      method="post"
      action="<?= PluginEngine::getLink($plugin, array(), "config/edit_additionalfield/".$field->getId()) ?>">

    <fieldset>
        <legend><?= dgettext("evasys", "Daten des Feldes") ?></legend>

        <label>
            <?= dgettext("evasys", "Name") ?>
            <?= I18N::input("name", $field['name']) ?>
        </label>

        <label>
            <input type="hidden" name="data[paper]" value="0">
            <input type="checkbox" name="data[paper]" value="1"<?= $field['paper'] ? " checked" : "" ?>>
            <?= dgettext("evasys", "Nur für papierbasierte Evaluationen") ?>
        </label>

        <label>
            <input type="radio" name="data[type]" value="TEXT"<?= $field->isNew() || ($field['type'] === "TEXT") ? " checked" : "" ?>>
            <?= dgettext("evasys", "Text") ?>
        </label>
        <label>
            <input type="radio" name="data[type]" value="TEXTAREA"<?= $field['type'] === "TEXTAREA" ? " checked" : "" ?>>
            <?= dgettext("evasys", "Großes Textfeld") ?>
        </label>

        <label>
            <?= dgettext("evasys", "Reihenfolge") ?>
            <input type="number" name="data[position]" value="<?= htmlReady($field['position'] ?: 1) ?>">

        </label>
    </fieldset>
    <div data-dialog-button>
        <?= \Studip\Button::create(dgettext("evasys", "Speichern")) ?>
    </div>
</form>