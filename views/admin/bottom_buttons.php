<?= \Studip\Button::create(
    dgettext("evasys", "Bearbeiten"),
    'bulkedit',
    ['formaction' => PluginEngine::getURL($plugin, [], "profile/bulkedit")]
)?>
<? if ($GLOBALS['perm']->have_perm(Config::get()->EVASYS_TRANSFER_PERMISSION) && ($GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE && $GLOBALS['user']->cfg->MY_COURSES_SELECTED_CYCLE !== "all")) : ?>
    <?= \Studip\Button::create(
        dgettext("evasys", "Übertragen"),
        'upload',
        ['formaction' => PluginEngine::getURL($plugin, [], "admin/upload_courses")]
    )?>
<? endif ?>
