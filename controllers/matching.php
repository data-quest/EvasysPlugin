<?php

class MatchingController extends PluginController
{

    public function institutes_action()
    {
        Navigation::activateItem("/admin/evasys/matchinginstitutes");
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
            PageLayout::postSuccess(_("Daten wurden gespeichert."));
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
            PageLayout::postSuccess(_("Daten wurden gespeichert."));
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

}