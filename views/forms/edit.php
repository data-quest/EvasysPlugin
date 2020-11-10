<form action="<?= PluginEngine::getLink($plugin, array(), "forms/edit/".$form->getId()) ?>"
      method="post"
      data-dialog
      class="default">
    <label>
        <input type="checkbox" name="data[active]" value="1" <?= $form['active'] ? " checked" : "" ?>>
        <?= dgettext("evasys", "Aktiv in Stud.IP") ?>
    </label>

    <label>
        <?= dgettext("evasys", "Info-Link") ?>
        <input type="text" name="data[link]" value="<?= htmlReady($form['link']) ?>">
    </label>

    <div data-dialog-button>
        <?= \Studip\Button::create(dgettext("evasys", "Speichern")) ?>
    </div>
</form>