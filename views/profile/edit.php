<form action="<?= PluginEngine::getLink($plugin, array(), "profile/edit/".$profile['seminar_id']) ?>"
      method="post"
      data-dialog
      class="default">

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

    <script>
        jQuery(function () {
            jQuery("input.datepicker").datetimepicker();
        });
    </script>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
    </div>
</form>