<form action="<?= PluginEngine::getLink($plugin, array(), "forms/sort/".$profile_type."/".$sem_type."/".$profile_id) ?>"
      method="post"
      class="default"
      data-dialog>

    <fieldset>

        <legend>
            <?= dgettext("evasys", "Reihenfolge per Drag & Drop festlegen") ?>
        </legend>

        <div style="padding-left: 6px;">
            <?= Icon::create("checkbox-checked", "info") ?>
            &nbsp;
            <?= htmlReady($standardform->form['name']) ?>:
            <span style="opacity: 0.7;"><?= htmlReady($standardform->form['description']) ?></span>
        </div>

        <ul class="clean sortforms">
            <? foreach ($forms as $form) : ?>
            <li>
                <a class="mover">
                    <?= Assets::img("anfasser_24.png") ?>
                </a>
                <?= htmlReady($form->form['name']) ?>:
                <span class="description">
                    <?= htmlReady($form->form['description']) ?>
                </span>
                <input type="hidden" name="form[]" value="<?= htmlReady($form['form_id']) ?>">
            </li>
            <? endforeach ?>
        </ul>

    </fieldset>

    <div data-dialog-button>
        <?= \Studip\Button::create(dgettext("evasys", "Speichern")) ?>
    </div>
</form>

<script>
    jQuery(function () {
        jQuery("ul.sortforms").sortable({
            "axis": "y",
            "handle": ".mover"
        });
    });
</script>