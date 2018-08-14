<form action="<?= PluginEngine::getLink($plugin, array(), "forms/edit/".$form->getId()) ?>"
      method="post"
      data-dialog
      class="default">
    <label>
        <input type="checkbox" name="data[active]" value="1" <?= $form['active'] ? " checked" : "" ?>>
        <?= _("Aktiv in Stud.IP") ?>
    </label>

    <label>
        <?= _("Info-Link") ?>
        <input type="text" name="data[link]" value="<?= htmlReady($form['link']) ?>">
    </label>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>