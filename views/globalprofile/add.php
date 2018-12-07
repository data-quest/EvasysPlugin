<form action="<?= PluginEngine::getLink($plugin, array(), "globalprofile/add") ?>" method="post" class="default">

    <fieldset>
        <legend>
            <?= _("Standardwerte für ein neues Semester") ?>
        </legend>

        <label>
            <?= _("Semester auswählen") ?>
            <select name="semester_id">
                <? foreach ($semesters as $semester) : ?>
                    <? if (!EvasysGlobalProfile::find($semester->getId())) : ?>
                    <option value="<?= htmlReady($semester->getId()) ?>">
                        <?= htmlReady($semester['name']) ?>
                    </option>
                    <? endif ?>
                <? endforeach ?>
            </select>
        </label>

        <label>
            <?= _("Standardwerte aus welchem Semester übernehmen") ?>
            <select name="copy_from">
                <option value=""><?= _("Keine Werte kopieren") ?></option>
                <? foreach (EvasysGlobalProfile::findBySQL("1=1 ORDER BY begin DESC") as $profile) : ?>
                    <option value="<?= htmlReady($profile->getId()) ?>">
                        <?= htmlReady($profile->semester['name']) ?>
                    </option>
                <? endforeach ?>
            </select>
        </label>

    </fieldset>

    <div data-dialog-button>
        <?= \Studip\Button::create(_("Erstellen")) ?>
    </div>

</form>