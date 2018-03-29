<form action="<?= PluginEngine::getLink($plugin, array(), "forms/sort/".$profile_type."/".$sem_type."/".$profile_id) ?>"
      method="post"
      class="default"
      data-dialog>

    <?= _("Standardfragebogen") ?>: <?= htmlReady($standardform->form['name']) ?>

    <ul class="clean sortforms">
        <? foreach ($forms as $form) : ?>
        <li>
            <a class="mover">
                <?= Assets::img("anfasser_24.png", array('class' => "text-bottom")) ?>
            </a>
            <?= htmlReady($form->form['name']) ?>
            <input type="hidden" name="form[]" value="<?= htmlReady($form['form_id']) ?>">
        </li>
        <? endforeach ?>
    </ul>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Speichern")) ?>
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