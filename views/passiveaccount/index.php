<iframe style="width:100%; height: 90vh; border: none;"
        src="<?= htmlReady($link['ServerIp'] . $link['UserStartPage'] . "&PHPSESSID=" . urlencode($link['SessionId'])) ?>"></iframe>


<style>
    #layout-sidebar {
        display: none;
    }
</style>