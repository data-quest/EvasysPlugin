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
        $this->limit = 200;
    }

    public function index_action()
    {
        if (Request::option("function")) {
            $this->logs = EvasysSoapLog::findBySQL("`function` = ? ORDER BY id DESC LIMIT ".($this->limit + 1), [
                Request::option("function")
            ]);
        } else {
            $this->logs = EvasysSoapLog::findBySQL("1 ORDER BY id DESC LIMIT ".($this->limit + 1));
        }
        if (count($this->logs) > $this->limit) {
            $this->more = true;
            array_pop($this->logs);
        } else {
            $this->more = false;
        }
    }

    public function details_action($id)
    {
        PageLayout::setTitle(_("Logeintrag anzeigen"));
        $this->log = EvasysSoapLog::find($id);
    }

    public function more_action()
    {
        if (Request::option("function")) {
            $this->logs = EvasysSoapLog::findBySQL("`function` = :function AND id < :id ORDER BY id DESC LIMIT ".($this->limit + 1), [
                'function' => Request::option("function"),
                'id' => Request::option("earliest")
            ]);
        } else {
            $this->logs = EvasysSoapLog::findBySQL("id < :id ORDER BY id DESC LIMIT ".($this->limit + 1), [
                'id' => Request::option("earliest")
            ]);
        }
        if (count($this->logs) > $this->limit) {
            $this->more = true;
            array_pop($this->logs);
        } else {
            $this->more = false;
        }
        $output = [
            'rows' => [],
            'more' => $this->more
        ];
        foreach ($this->logs as $log) {
            $this->log = $log;
            $output['rows'][] = $this->render_template_as_string("logs/_row");
        }
        $this->render_json($output);
    }
}
