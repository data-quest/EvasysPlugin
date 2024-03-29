<?php

class LogsController extends PluginController
{
    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);
        if (!EvasysPlugin::isRoot()) {
            throw new AccessDeniedException();
        }
        Navigation::activateItem("/admin/evasys/logs");
        PageLayout::setTitle($this->plugin->getDisplayName());
        $this->limit = 200;
    }

    public function index_action()
    {
        if (Request::option("function")) {
            if (Request::get('search')) {
                $this->logs = EvasysSoapLog::findBySQL("LEFT JOIN auth_user_md5 ON (evasys_soap_logs.user_id = auth_user_md5.user_id) WHERE (`arguments` LIKE :search OR `result` LIKE :search OR CONCAT(auth_user_md5.`Vorname`, ' ', auth_user_md5.`Nachname`, ' ', auth_user_md5.`username`) LIKE :search) AND `function` = :func ORDER BY id DESC LIMIT ".($this->limit + 1), [
                    'search' => '%'.Request::get('search').'%',
                    'func' => Request::option("function")
                ]);
            } else {
                $this->logs = EvasysSoapLog::findBySQL("`function` = :func ORDER BY id DESC LIMIT ".($this->limit + 1), [
                    'func' => Request::option("function")
                ]);
            }
        } else {
            if (Request::get('search')) {
                $this->logs = EvasysSoapLog::findBySQL("LEFT JOIN auth_user_md5 ON (evasys_soap_logs.user_id = auth_user_md5.user_id) WHERE (`arguments` LIKE :search OR `result` LIKE :search OR CONCAT(auth_user_md5.`Vorname`, ' ', auth_user_md5.`Nachname`, ' ', auth_user_md5.`username`) LIKE :search) ORDER BY id DESC LIMIT ".($this->limit + 1), [
                    'search' => '%'.Request::get('search').'%'
                ]);
            } else {
                $this->logs = EvasysSoapLog::findBySQL("1 ORDER BY id DESC LIMIT " . ($this->limit + 1));
            }
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
        PageLayout::setTitle(dgettext("evasys", "Logeintrag anzeigen"));
        $this->log = EvasysSoapLog::find($id);
    }

    public function more_action()
    {
        if (Request::option("function")) {
            if (Request::get('search')) {
                $this->logs = EvasysSoapLog::findBySQL("LEFT JOIN auth_user_md5 ON (evasys_soap_logs.user_id = auth_user_md5.user_id) WHERE (`arguments` LIKE :search OR `result` LIKE :search OR CONCAT(auth_user_md5.`Vorname`, ' ', auth_user_md5.`Nachname`, ' ', auth_user_md5.`username`) LIKE :search) AND `function` = :function AND `id` < :id ORDER BY id DESC LIMIT " . ($this->limit + 1), [
                    'search' => '%'.Request::get('search').'%',
                    'function' => Request::option("function"),
                    'id' => Request::option("earliest")
                ]);
            } else {
                $this->logs = EvasysSoapLog::findBySQL("`function` = :function AND `id` < :id ORDER BY id DESC LIMIT " . ($this->limit + 1), [
                    'function' => Request::option("function"),
                    'id' => Request::option("earliest")
                ]);
            }
        } else {
            if (Request::get('search')) {
                $this->logs = EvasysSoapLog::findBySQL("LEFT JOIN auth_user_md5 ON (evasys_soap_logs.user_id = auth_user_md5.user_id) WHERE (`arguments` LIKE :search OR `result` LIKE :search OR CONCAT(auth_user_md5.`Vorname`, ' ', auth_user_md5.`Nachname`, ' ', auth_user_md5.`username`) LIKE :search) AND `id` < :id ORDER BY id DESC LIMIT " . ($this->limit + 1), [
                    'search' => '%'.Request::get('search').'%',
                    'id' => Request::option("earliest")
                ]);
            } else {
                $this->logs = EvasysSoapLog::findBySQL("`id` < :id ORDER BY id DESC LIMIT " . ($this->limit + 1), [
                    'id' => Request::option("earliest")
                ]);
            }
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
