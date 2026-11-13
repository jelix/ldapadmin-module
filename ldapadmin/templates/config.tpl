<h2>{@ldapadmin~main.config.title@}</h2>

{if $hasProvider === false}
<p>{@ldapadmin~main.message.no.ldap.provider@}</p>
{else}
<div id="ldapadmin-config-form">
    {form $form, 'ldapadmin~ldap:save'}
    <div id="tabs" class="ui-tabs ui-corner-all ui-widget ui-widget-content">
        <ul>
            <li><a href="#tabs-connect">{@ldapadmin~main.tabs.connect@}</a></li>
            <li><a href="#tabs-mapping">{@ldapadmin~main.tabs.mapping@}</a></li>
            <li><a href="#tabs-user">{@ldapadmin~main.tabs.user@}</a></li>
            <li><a href="#tabs-groups">{@ldapadmin~main.tabs.groups@}</a></li>
        </ul>

        <div id="tabs-connect">
            <p>
                {ctrl_label 'ldap_host'}
                {ctrl_control 'ldap_host'}
            </p>
            <p>
                {ctrl_label 'ldap_port'}
                {ctrl_control 'ldap_port'}
            </p>
            <p>
                {ctrl_label 'ldap_tls'}
                {ctrl_control 'ldap_tls'}
            </p>
            <p>
                {ctrl_label 'ldap_admin_dn'}
                {ctrl_control 'ldap_admin_dn'}
                <br/>
                <span class="jelix-help">{@ldapadmin~main.form.ldap_admin_dn.help@}</span>
            </p>
            <p>
                {ctrl_label 'ldap_admin_password'}
                {ctrl_control 'ldap_admin_password'}
                <br/>
                <span class="jelix-help">
                    {if $hasAdminPassword}{@ldapadmin~main.form.ldap_admin_password.help.haspassword@}{else}{@ldapadmin~main.form.ldap_admin_password.help.nopassword@}{/if}
                </span>
            </p>
            <p>
                <button type="button" class="ldap-test-button" data-test="connection"
                    data-url="{jurl 'ldapadmin~ldap:testConnection'}">{@ldapadmin~main.button.test.connection@}</button>
                <span class="ldap-test-inline-result" data-test-result="connection"
                    data-label-ok="{@ldapadmin~main.test.result.ok@}"
                    data-label-fail="{@ldapadmin~main.test.result.fail@}"></span>
            </p>
        </div>

        <div id="tabs-mapping">
            <p>
                {ctrl_label 'searchUserBaseDN'}
                {ctrl_control 'searchUserBaseDN'}
            </p>

            <fieldset>
                <legend>{@ldapadmin~main.section.searchUserFilter@}</legend>
                <div class="repeat-list" id="searchUserFilter-list" data-input-name="searchUserFilter">
                    <div class="repeat-list-items">
                        {foreach $searchUserFilters as $filter}
                        <div class="repeat-list-item">
                            <input type="text" name="searchUserFilter[]" value="{$filter|eschtml}" size="60" />
                            <button type="button" class="repeat-list-remove">{@ldapadmin~main.button.remove@}</button>
                        </div>
                        {/foreach}
                    </div>
                    <button type="button" class="repeat-list-add">{@ldapadmin~main.button.add@}</button>
                </div>
            </fieldset>

            <fieldset>
                <legend>{@ldapadmin~main.section.searchAttributesMap@}</legend>
                <table class="ldap-mapping-table">
                    {foreach $mappableFields as $field}
                    <tr>
                        <td>{$field['label']|eschtml}</td>
                        <td><input type="text" name="searchAttributesMap[{$field['ref']|eschtml}]" value="{$field['ldapAttr']|eschtml}" /></td>
                    </tr>
                    {/foreach}
                </table>
            </fieldset>

            <fieldset>
                <legend>{@ldapadmin~main.section.extraLdapAttributes@}</legend>
                <div class="repeat-list" id="extraLdapAttributes-list" data-input-name="extraLdapAttributes">
                    <div class="repeat-list-items">
                        {foreach $extraLdapAttributes as $attr}
                        <div class="repeat-list-item">
                            <input type="text" name="extraLdapAttributes[]" value="{$attr|eschtml}" />
                            <button type="button" class="repeat-list-remove">{@ldapadmin~main.button.remove@}</button>
                        </div>
                        {/foreach}
                    </div>
                    <button type="button" class="repeat-list-add">{@ldapadmin~main.button.add@}</button>
                </div>
            </fieldset>

            <p>
                <button type="button" class="ldap-test-button" data-test="mapping" data-need-password="0"
                    data-url="{jurl 'ldapadmin~ldap:testMapping'}"
                    data-dialog-title="{@ldapadmin~main.dialog.title.mapping@}"
                    >{@ldapadmin~main.button.test.mapping@}</button>
            </p>
        </div>

        <div id="tabs-user">
            <p>
                <button type="button" class="ldap-test-button" data-test="identification" data-need-password="1"
                    data-url="{jurl 'ldapadmin~ldap:testIdentification'}"
                    data-dialog-title="{@ldapadmin~main.dialog.title.identification@}"
                    >{@ldapadmin~main.button.test.identification@}</button>
            </p>
        </div>

        <div id="tabs-groups">
            <p>
                {ctrl_label 'searchGroupEnabled'}
                {ctrl_control 'searchGroupEnabled'}
            </p>
            <p>
                {ctrl_label 'searchGroupFilter'}
                {ctrl_control 'searchGroupFilter'}
            </p>
            <p>
                {ctrl_label 'searchGroupProperty'}
                {ctrl_control 'searchGroupProperty'}
            </p>
            <p>
                {ctrl_label 'searchGroupBaseDN'}
                {ctrl_control 'searchGroupBaseDN'}
            </p>
            <p>
                {ctrl_label 'searchGroupKeepUserInDefaultGroups'}
                {ctrl_control 'searchGroupKeepUserInDefaultGroups'}
            </p>
            <p>
                <button type="button" class="ldap-test-button" data-test="groups" data-need-password="1"
                    data-url="{jurl 'ldapadmin~ldap:testGroups'}"
                    data-dialog-title="{@ldapadmin~main.dialog.title.groups@}"
                    data-groups-none="{@ldapadmin~main.test.groups.none@}"
                    data-groups-disabled="{@ldapadmin~main.test.groups.disabled@}"
                    >{@ldapadmin~main.button.test.groups@}</button>
            </p>
        </div>
    </div>
    <p class="ldap-form-submit">
        {formsubmit '_submit'}
    </p>

    {/form}
</div>


<div id="ldap-test-dialog" title="" style="display:none"
    data-label-ok="{@ldapadmin~main.test.result.ok@}"
    data-label-fail="{@ldapadmin~main.test.result.fail@}">
    <form id="ldap-test-dialog-form">
        <p class="ldap-test-field-login">
            <label>{@ldapadmin~main.field.login@}</label>
            <input type="text" name="login" class="ldap-test-login" />
        </p>
        <p class="ldap-test-field-password">
            <label>{@ldapadmin~main.field.password@}</label>
            <input type="password" name="password" class="ldap-test-password" />
        </p>
        <p>
            <button type="button" class="ldap-test-dialog-run">{@ldapadmin~main.button.dialog.run@}</button>
        </p>
    </form>
    <div class="ldap-test-dialog-result">
        <div class="ldap-test-ok">{@ldapadmin~main.test.result.ok@}</div>
        <div class="ldap-test-fail">{@ldapadmin~main.test.result.fail@}</div>
        <div class="ldap-test-reason"></div>
        <div class="ldap-test-result-mapping">
            <table class="ldap-test-result-table ldap-test-result-fields">
                <caption>{@ldapadmin~main.test.mapping.fields.header@}</caption>
                <tbody></tbody>
            </table>

            <table class="ldap-test-result-table ldap-test-result-extra">
                <caption>{@ldapadmin~main.test.mapping.extra.header@}</caption>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{/if}
