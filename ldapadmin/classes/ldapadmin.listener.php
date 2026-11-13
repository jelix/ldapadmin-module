<?php
/**
 * @author    Laurent Jouanneau
 * @copyright 2023 Laurent Jouanneau
 * @licence   MIT
 */
class ldapadminListener extends jEventListener
{
    /**
     * @param mixed $event
     */
    public function onmasteradminGetMenuContent($event)
    {
        if (jAcl2::check('ldapadmin.configuration')) {
            $item = new masterAdminMenuItem('ldap',
                jLocale::get('ldapadmin~main.acl.menu.item'),
                jUrl::get('ldapadmin~ldap:config'), 10, 'system');
            //$item->icon = jApp::urlJelixWWWPath().'design/images/user.png';
            $event->add($item);
        }
    }
}
