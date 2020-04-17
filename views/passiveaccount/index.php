<? /*var_dump($link)*/ ?>

<style>
    #layout-sidebar {
        display: none;
    }
</style>

<? if ($link['Token']) : ?>
    <form method="POST"
          action="<?= htmlReady($link['ServerIp'] . "public/forward") ?>"
          class="default">
        <input type="hidden" name="forwardinfo" value="<?= htmlReady($link['Token']) ?>">

        <div style="text-align: center">
            <?= \Studip\Button::create(_("Nach EvaSys (öffnet in neuem Reiter)")) ?>
        </div>

    </form>
<? else : ?>
    <iframe style="width:100%; height: 90vh; border: none;"
            src="<?= htmlReady($link['ServerIp'] . $link['UserStartPage'] . "&PHPSESSID=" . urlencode($link['SessionId'])) ?>"></iframe>
<? endif ?>
