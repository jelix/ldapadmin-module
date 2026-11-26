<h2>{@ldapadmin~main.index.title@}</h2>

{if $hasProvider === false}
<p>{@ldapadmin~main.message.no.ldap.provider@}</p>
{else}

    {if $hasConfiguration === false}
    <p>{@ldapadmin~main.index.no.configuration@}</p>
    <p>{@ldapadmin~main.index.create.choose.servertype@}</p>
    <div class="ldapadmin-create-buttons">
        <a href="{jurl 'ldapadmin~ldap:config'}" class="ui-button ui-widget ui-corner-all">{@ldapadmin~main.button.create@}</a>
        <a href="{jurl 'ldapadmin~ldap:config', array('servertype'=>'openldap')}" class="ui-button ui-widget ui-corner-all">{@ldapadmin~main.button.create.openldap@}</a>
        <a href="{jurl 'ldapadmin~ldap:config', array('servertype'=>'activedirectory')}" class="ui-button ui-widget ui-corner-all">{@ldapadmin~main.button.create.activedirectory@}</a>
    </div>
    {else}
    <div id="ldapadmin-index">

        <div class="ldapadmin-status">
            {if $status == 1}
            <span class="ldapadmin-status-badge ldapadmin-status-enabled">{@ldapadmin~main.index.status.enabled@}</span>
            {else}
            <span class="ldapadmin-status-badge ldapadmin-status-disabled">{@ldapadmin~main.index.status.disabled@}</span>
            {/if}
        </div>

        <table class="ldapadmin-summary">
            <tr>
                <th>{@ldapadmin~main.form.ldap_host@}</th>
                <td>{$hostname|eschtml}{if $port != 389}:{$port}{/if}</td>
            </tr>
            <tr>
                <th>{@ldapadmin~main.form.ldap_tls@}</th>
                <td>
                    {if $tlsMode == 'starttls'}{@ldapadmin~main.form.ldap_tls.starttls@}
                    {elseif $tlsMode == 'ldaps'}{@ldapadmin~main.form.ldap_tls.ldaps@}
                    {else}{@ldapadmin~main.form.ldap_tls.none@}
                    {/if}
                </td>
            </tr>
            <tr>
                <th>{@ldapadmin~main.form.ldap_admin_dn@}</th>
                <td>
                    {if $adminUserDn}{$adminUserDn|eschtml}{else}<em>{@ldapadmin~main.index.anonymous.bind@}</em>{/if}
                </td>
            </tr>
            <tr>
                <th>{@ldapadmin~main.form.ldap_admin_password@}</th>
                <td>
                    {if $hasAdminPassword}{@ldapadmin~main.index.password.set@}{else}{@ldapadmin~main.index.password.notset@}{/if}
                </td>
            </tr>
            <tr>
                <th>{@ldapadmin~main.section.userSearches@}</th>
                <td>{jlocale 'ldapadmin~main.index.userSearches.count', array($userSearchCount)}</td>
            </tr>
            <tr>
                <th>{@ldapadmin~main.index.groupSync@}</th>
                <td>
                    {if $groupSyncEnabled}{@ldapadmin~main.index.groupSync.enabled@}{else}{@ldapadmin~main.index.groupSync.disabled@}{/if}
                </td>
            </tr>
        </table>

        <div class="ldapadmin-actions">
            <a href="{jurl 'ldapadmin~ldap:config'}" class="ui-button ui-widget ui-corner-all">{@ldapadmin~main.button.modify@}</a>

            {if $canStatusBeChanged}
                {if $status == 1}
                <form method="post" action="{jurl 'ldapadmin~ldap:setStatus'}" style="display:inline">
                    <input type="hidden" name="status" value="0" />
                    <button type="submit" class="ui-button ui-widget ui-corner-all ldapadmin-btn-disable">{@ldapadmin~main.button.disable@}</button>
                </form>
                {else}
                <form method="post" action="{jurl 'ldapadmin~ldap:setStatus'}" style="display:inline">
                    <input type="hidden" name="status" value="1" />
                    <button type="submit" class="ui-button ui-widget ui-corner-all ldapadmin-btn-enable">{@ldapadmin~main.button.enable@}</button>
                </form>
                {/if}
            {/if}
        </div>
    </div>
    {/if}

{/if}
