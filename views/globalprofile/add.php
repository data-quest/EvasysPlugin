<?
$current_semester_id = Semester::findCurrent() ? Semester::findCurrent()->id : null;
$next_semester_id = Semester::findNext() ? Semester::findNext()->id : null;
?>
<form action="<?= PluginEngine::getLink($plugin, array(), "globalprofile/add") ?>" method="post" class="default">

    <fieldset>
        <legend>
            <?= dgettext("evasys", "Standardwerte für ein neues Semester") ?>
        </legend>

        <label>
            <?= dgettext("evasys", "Semester auswählen") ?>
            <select name="semester_id">
                <? foreach ($semesters as $semester) : ?>
                    <? if (!EvasysGlobalProfile::find($semester->getId())) : ?>
                    <option value="<?= htmlReady($semester->getId()) ?>"<?= ($semester->getId() === $next_semester_id) ? " selected" : "" ?>>
                        <?= htmlReady($semester['name']) ?>
                    </option>
                    <? endif ?>
                <? endforeach ?>
            </select>
        </label>

        <label>
            <?= dgettext("evasys", "Standardwerte aus welchem Semester übernehmen") ?>
            <select name="copy_from">
                <option value=""><?= dgettext("evasys", "Keine Werte kopieren") ?></option>
                <? foreach (EvasysGlobalProfile::findBySQL("1=1 ORDER BY begin DESC") as $profile) : ?>
                    <option value="<?= htmlReady($profile->getId()) ?>"<?= ($profile->getId() === $current_semester_id) ? " selected" : "" ?>>
                        <?= htmlReady($profile->semester['name']) ?>
                    </option>
                <? endforeach ?>
            </select>
        </label>

    </fieldset>

    <div data-dialog-button>
        <?= \Studip\Button::create(dgettext("evasys", "Erstellen")) ?>
    </div>

</form>