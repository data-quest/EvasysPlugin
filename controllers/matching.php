<?php

class MatchingController extends PluginController
{

    function before_filter(&$action, &$args)
    {
        parent::before_filter($action, $args);

        if (!EvasysPlugin::isRoot()) {
            throw new AccessDeniedException();
        }
    }

    public function institutes_action()
    {
        Navigation::activateItem("/admin/evasys/matchinginstitutes");
        PageLayout::setTitle(dgettext("evasys", "Matching Einrichtungen"));
        $this->action = "institutes";

        if (Request::isPost()) {
            foreach (Request::getArray("matching") as $id => $name) {
                $matching = EvasysMatching::findOneBySQL("item_id = ? AND item_type = 'institute'", array($id));
                if (trim($name) === Institute::find($id)->name) {
                    if ($matching) {
                        $matching->delete();
                    }
                } else {
                    if (!$matching) {
                        $matching = new EvasysMatching();
                        $matching['item_id'] = $id;
                        $matching['item_type'] = "institute";
                    }
                    $matching['name'] = $name;
                    $matching->store();
                }
            }
            PageLayout::postSuccess(dgettext("evasys", "Daten wurden gespeichert."));
            $this->redirect("matching/".$this->action);
            return;
        }

        $this->items = array();
        foreach (Institute::getInstitutes() as $institute) {
            $this->items[$institute['Institut_id']] = array(
                'id' => $institute['Institut_id'],
                'long_name' => $institute['Name'],
                'name' => $institute['Name'],
                'matching' => EvasysMatching::findOneBySQL("item_id = ? AND item_type = 'institute'", array($institute['Institut_id']))
            );
        }
    }

    public function seminartypes_action()
    {
        Navigation::activateItem("/admin/evasys/matchingtypes");
        PageLayout::setTitle(dgettext("evasys", "Matching Veranstaltungstypen"));
        $this->action = "seminartypes";

        if (Request::isPost()) {
            foreach (Request::getArray("matching") as $id => $name) {
                $matching = EvasysMatching::findOneBySQL("item_id = ? AND item_type = 'semtype'", array($id));
                if (trim($name) === $GLOBALS['SEM_TYPE'][$id]['name']) {
                    if ($matching) {
                        $matching->delete();
                    }
                } else {
                    if (!$matching) {
                        $matching = new EvasysMatching();
                        $matching['item_id'] = $id;
                        $matching['item_type'] = "semtype";
                    }
                    $matching['name'] = $name;
                    $matching->store();
                }
            }
            PageLayout::postSuccess(dgettext("evasys", "Daten wurden gespeichert."));
            $this->redirect("matching/".$this->action);
            return;
        }

        $this->items = array();
        foreach (SemType::getTypes() as $type) {
            $this->items[$type['id']] = array(
                'id' => $type['id'],
                'long_name' => $GLOBALS['SEM_CLASS'][$type['class']]['name'] . ": ".$type['name'],
                'name' => $type['name'],
                'matching' => EvasysMatching::findOneBySQL("item_id = ? AND item_type = 'semtype'", array($type['id']))
            );
        }

        $this->render_template("matching/institutes", $this->layout);
    }

    public function wording_action()
    {
        Navigation::activateItem("/admin/evasys/wording");
        PageLayout::setTitle(dgettext("evasys", "Begrifflichkeiten"));
        $this->action = "wording";
        $this->i18n = true;

        $words_raw = array(
            "Einrichtung", "Einrichtungen", "Fakultät", "Fakultäten",
            "freiwillige Evaluation", "freiwillige Evaluationen"
        );
        $words = array();
        foreach ($words_raw as $word) {
            $words[md5($word)] = $word;
        }
        unset($words_raw);

        if (Request::isPost()) {
            foreach ($words as $id => $word) {
                $name = Request::i18n("matching__".$id."__");
                $matching = EvasysMatching::findOneBySQL("item_id = ? AND item_type = 'wording'", array($id));
                if ($name === $words[$id]) {
                    if ($matching) {
                        $matching->delete();
                    }
                } else {
                    if (!$matching) {
                        $matching = new EvasysMatching();
                        $matching['item_id'] = $id;
                        $matching['item_type'] = "wording";
                    }
                    $matching['name'] = $name;
                    $matching->store();
                }
            }
            PageLayout::postSuccess(dgettext("evasys", "Daten wurden gespeichert."));
            $this->redirect("matching/".$this->action);
            return;
        }

        $this->items = array();
        foreach ($words as $id => $word) {
            $this->items[$id] = array(
                'id' => $id,
                'long_name' => $word,
                'name' => $word,
                'matching' => EvasysMatching::findOneBySQL("item_id = ? AND item_type = 'wording'", array($id))
            );
        }

        $this->render_template("matching/institutes", $this->layout);
    }

}