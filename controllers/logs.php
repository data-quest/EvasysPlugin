<?php

class LogsController extends PluginController
{

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!EvasysPlugin::isRoot() || !Config::get()->EVASYS_ENABLE_PROFILES) {
            throw new AccessDeniedException();
        }
        Navigation::activateItem("/admin/evasys/logs");
        PageLayout::setTitle($this->plugin->getDisplayName());
    }

    public function index_action()
    {
        if (Request::option("function")) {
            $this->logs = EvasysSoapLog::findBySQL("`function` = ? ORDER BY id DESC", [Request::option("function")]);
        } else {
            $this->logs = EvasysSoapLog::findBySQL("1 ORDER BY id DESC");
        }
    }

    public function details_action($id)
    {
        PageLayout::setTitle(_("Logeintrag anzeigen"));
        $this->log = EvasysSoapLog::find($id);
    }
}
