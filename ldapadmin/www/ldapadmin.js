/**
 * @author      Laurent Jouanneau
 * @copyright   2026 Laurent Jouanneau
 * @license MIT
 */
(function () {
    "use strict";

    /**
     * Repeatable lists: each .repeat-list has a .repeat-list-items container,
     * a <template class="repeat-list-template"> containing an empty item,
     * and a .repeat-list-add button. Items have a .repeat-list-remove button
     * and may have a .ldap-row-number cell, renumbered on each change.
     */
    function renumberRepeatItems(itemsContainer) {
        itemsContainer.querySelectorAll(".repeat-list-item").forEach(function (item, index) {
            var numberEl = item.querySelector(".ldap-row-number");
            if (numberEl) {
                numberEl.textContent = String(index + 1);
            }
        });
    }

    function initRepeatLists() {
        document.querySelectorAll(".repeat-list").forEach(function (list) {
            var itemsContainer = list.querySelector(".repeat-list-items");
            var template = list.querySelector("template.repeat-list-template");

            list.addEventListener("click", function (evt) {
                if (evt.target.classList.contains("repeat-list-remove")) {
                    var item = evt.target.closest(".repeat-list-item");
                    if (item) {
                        item.remove();
                    }
                } else if (evt.target.classList.contains("repeat-list-add") && template) {
                    itemsContainer.append(template.content.cloneNode(true));
                    var inputs = itemsContainer.querySelectorAll(".repeat-list-item:last-child input");
                    if (inputs.length) {
                        inputs[0].focus();
                    }
                } else {
                    return;
                }
                renumberRepeatItems(itemsContainer);
            });
        });
    }

    /**
     * A user search needs both a base DN and a filter. Rows having only one
     * of them are highlighted, and the form is not submitted, else the
     * values typed in these plain inputs would be lost after the server-side
     * check.
     */
    function checkUserSearches() {
        var list = document.getElementById("userSearches-list");
        if (!list) {
            return true;
        }
        var ok = true;
        list.querySelectorAll(".ldap-user-search").forEach(function (row) {
            var values = Array.prototype.map.call(row.querySelectorAll("input"), function (input) {
                return input.value.trim();
            });
            var filled = values.filter(function (v) { return v !== ""; }).length;
            var incomplete = filled > 0 && filled < values.length;
            row.classList.toggle("ldap-row-error", incomplete);
            if (incomplete) {
                ok = false;
            }
        });
        var errorEl = list.querySelector(".ldap-user-searches-error");
        if (ok) {
            dlgEltHide(errorEl);
        } else {
            dlgEltShow(errorEl);
        }
        return ok;
    }

    function initUserSearchesCheck() {
        var form = getMainForm();
        var list = document.getElementById("userSearches-list");
        if (!form || !list) {
            return;
        }
        form.addEventListener("submit", function (evt) {
            if (!checkUserSearches()) {
                evt.preventDefault();
                evt.stopImmediatePropagation();
                $("#tabs").tabs("option", "active", 1);
            }
        }, true);
        // update the error display while the user fixes the rows
        list.addEventListener("input", function () {
            if (list.querySelector(".ldap-row-error")) {
                checkUserSearches();
            }
        });
    }

    function getMainForm() {
        return document.querySelector("#ldapadmin-config-form form");
    }

    function postForm(url, extraFields) {
        var form = getMainForm();
        var data = new FormData(form);
        if (extraFields) {
            Object.keys(extraFields).forEach(function (key) {
                data.set(key, extraFields[key]);
            });
        }
        return fetch(url, {
            method: "POST",
            body: data,
            credentials: "same-origin"
        }).then(function (response) {
            return response.json();
        });
    }

    function renderInlineResult(el, result) {
        var labelOk = el.getAttribute("data-label-ok") || "OK";
        var labelFail = el.getAttribute("data-label-fail") || "Failed";
        el.textContent = result.ok ? labelOk : (labelFail + (result.error ? " : " + result.error : ""));
        el.className = "ldap-test-inline-result " + (result.ok ? "ldap-test-ok" : "ldap-test-fail");
    }

    function initConnectionTest() {
        var button = document.querySelector('.ldap-test-button[data-test="connection"]');
        if (!button) {
            return;
        }
        var resultEl = document.querySelector('.ldap-test-inline-result[data-test-result="connection"]');
        button.addEventListener("click", function () {
            resultEl.textContent = "...";
            postForm(button.getAttribute("data-url")).then(function (result) {
                renderInlineResult(resultEl, result);
            });
        });
    }

    function renderMappingResult(container, result, button) {
        if (!result.ok) {
            dlgEltShow(container.failEl);
            container.reasonEl.textContent = (result.error || "");
            dlgEltShow(container.reasonEl);
            return;
        }
        dlgEltShow(container.okEl);
        dlgEltShow(container.mappingElt);
        let html = '';

        Object.keys(result.fields || {}).forEach(function (field) {
            html += "<tr><th>" + escapeHtml(field) + "</th><td>" + escapeHtml(result.fields[field]) + "</td></tr>";
        });
        container.mappingFieldsBody.innerHTML = html;

        let extraKeys = Object.keys(result.extraAttributes || {});
        if (extraKeys.length) {
            html = '';
            extraKeys.forEach(function (attr) {
                html += "<tr><th>" + escapeHtml(attr) + "</th><td>" + escapeHtml(result.extraAttributes[attr]) + "</td></tr>";
            });
            container.mappingExtraBody.innerHTML = html;
            dlgEltShow(container.mappingExtraBody);
        }
        else {
            container.mappingExtraBody.innerHTML = '';
            dlgEltHide(container.mappingExtraBody);
        }

        container.innerHTML = html;
    }

    function renderIdentificationResult(container, result) {
        if (!result.ok) {
            dlgEltShow(container.failEl);
            container.reasonEl.textContent = (result.error || "");
            dlgEltShow(container.reasonEl);
            return;
        }

        dlgEltShow(container.okEl);
        container.reasonEl.textContent = (result.dn || "");
        dlgEltShow(container.reasonEl);
    }

    function renderGroupsResult(container, result, button) {

        if (!result.ok) {
            dlgEltShow(container.failEl);
            container.reasonEl.textContent = (result.error || "");
            dlgEltShow(container.reasonEl);
            return;
        }

        if (result.message) {
            container.textContent = result.message;
            dlgEltShow(container.reasonEl);
            return;
        }

        dlgEltShow(container.okEl);
        let html = '';
        if (!result.groups || !result.groups.length) {
            container.reasonEl.innerHTML = '<p>'+(button.getAttribute("data-groups-none") || "")+"</p>";
            dlgEltShow(container.reasonEl);
            return;
        }

        html += "<ul>";
        result.groups.forEach(function (group) {
            html += "<li>" + escapeHtml(group) + "</li>";
        });
        html += "</ul>";
        container.reasonEl.innerHTML = html;
        dlgEltShow(container.reasonEl);
    }

    var dialogLabels = {};

    function dialogLabel(key) {
        return dialogLabels[key] || key;
    }

    function escapeHtml(value) {
        let span = document.createElement("span");
        span.textContent = String(value === undefined || value === null ? "" : value);
        return span.innerHTML;
    }

    function dlgEltHide(/** HtmlElement */ elt) {
        elt.classList.add('ldap-result-hide');
    }

    function dlgEltShow(/** HtmlElement */ elt) {
        elt.classList.remove('ldap-result-hide');
    }


    function initDialogTests() {
        var dialogEl = document.getElementById("ldap-test-dialog");
        if (!dialogEl || typeof jQuery === "undefined" || !jQuery.fn.dialog) {
            return;
        }
        var $dialog = jQuery(dialogEl);
        var resultUI = {
            resultEl : dialogEl.querySelector(".ldap-test-dialog-result"),
            okEl : dialogEl.querySelector(".ldap-test-ok"),
            failEl : dialogEl.querySelector(".ldap-test-fail"),
            reasonEl : dialogEl.querySelector(".ldap-test-reason"),
            mappingElt : dialogEl.querySelector(".ldap-test-result-mapping"),
            mappingFieldsBody : dialogEl.querySelector(".ldap-test-result-fields tbody"),
            mappingExtraBody : dialogEl.querySelector(".ldap-test-result-extra tbody"),
        }

        var loginField = dialogEl.querySelector(".ldap-test-login");
        var passwordField = dialogEl.querySelector(".ldap-test-password");
        var passwordRow = dialogEl.querySelector(".ldap-test-field-password");

        dialogLabels.ok = dialogEl.getAttribute("data-label-ok") || "OK";
        dialogLabels.fail = dialogEl.getAttribute("data-label-fail") || "Failed";

        $dialog.dialog({
            autoOpen: false,
            modal: true,
            width: 500
        });

        var currentButton = null;

        document.querySelectorAll(".ldap-test-button[data-dialog-title]").forEach(function (button) {
            button.addEventListener("click", function () {
                currentButton = button;
                dlgEltHide(resultUI.okEl);
                dlgEltHide(resultUI.failEl);
                dlgEltHide(resultUI.reasonEl);
                dlgEltHide(resultUI.mappingElt);
                loginField.value = "";
                passwordField.value = "";
                passwordRow.style.display = button.getAttribute("data-need-password") === "1" ? "" : "none";
                $dialog.dialog("option", "title", button.getAttribute("data-dialog-title"));
                $dialog.dialog("open");
            });
        });

        dialogEl.querySelector(".ldap-test-dialog-run").addEventListener("click", function () {
            if (!currentButton) {
                return;
            }
            dlgEltHide(resultUI.okEl);
            dlgEltHide(resultUI.failEl);
            dlgEltHide(resultUI.reasonEl);
            dlgEltHide(resultUI.mappingElt);
            resultUI.reasonEl.textContent = "...";
            var extra = { login: loginField.value };
            if (currentButton.getAttribute("data-need-password") === "1") {
                extra.password = passwordField.value;
            }
            postForm(currentButton.getAttribute("data-url"), extra).then(function (result) {

                let test = currentButton.getAttribute("data-test");
                if (test === "mapping") {
                    renderMappingResult(resultUI, result, currentButton);
                } else if (test === "identification") {
                    renderIdentificationResult(resultUI, result);
                } else if (test === "groups") {
                    renderGroupsResult(resultUI, result, currentButton);
                }
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        $( "#tabs" ).tabs();
        initRepeatLists();
        initUserSearchesCheck();
        initConnectionTest();
        initDialogTests();
    });
})();
