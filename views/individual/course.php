<tr id="course-<?= htmlReady($profile['seminar_id']) ?>">
    <td><?= htmlReady($profile->course['veranstaltungsnummer']) ?></td>
    <td><?= htmlReady($profile->course['name']) ?></td>
    <td>
        <?
        $seminar = new Seminar($profile['seminar_id']);
        $dozenten = $seminar->getMembers('dozent');
        ?>
        <ul class="clean">
            <? $teachers = $profile['teachers']->getArrayCopy() ?>
            <? foreach ($dozenten as $dozent) : ?>
            <li>
                <?= htmlReady($dozent['fullname']) ?>
                <? if (!$teachers || in_array($dozent['user_id'], $teachers)) : ?>
                    <?= Icon::create("check-circle", "info")->asImg(16, array('class' => "text-bottom", 'title' => _("Dieser Lehrende soll evaluiert werden."))) ?>
                <? else : ?>
                    <?= Icon::create("radiobutton-unchecked", "info")->asImg(16, array('class' => "text-bottom", 'title' => _("Dieser Lehrende ist nicht für eine Evaluierung vorgesehen."))) ?>
                <? endif ?>
            </li>
            <? endforeach ?>
        </ul>
    </td>
    <td>
        <? $begin = $profile->getFinalBegin() ?>
        <? $end = $profile->getFinalEnd() ?>
        <?= date("d.m.Y H:i", $begin)." - ".date(floor($begin / 86400) === floor($end / 86400) ? "H.i" : "d.m.Y H:i", $end) ?>
    </td>
    <? if (!Config::get()->EVASYS_FORCE_ONLINE) : ?>
        <td><?= $profile['mode'] === "paper" ? _("Papier") : _("Online")  ?></td>
    <? endif ?>
    <td class="actions">
        <?= $this->render_partial("admin/_admin_checkbox", array("profile" => $profile, 'checkbox' => false)) ?>
    </td>
</tr>