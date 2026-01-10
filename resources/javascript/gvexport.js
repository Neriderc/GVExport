const ERROR_CHAR = "E:";
const ID_MAIN_SETTINGS = "_MAIN_";
const ID_ALL_SETTINGS = "_ALL_";
const SETTINGS_ID_LIST_NAME = 'GVE_settings_id_list';
const REQUEST_TYPE_GET_TREE_NAME = "get_tree_name";
const REQUEST_TYPE_DELETE_SETTINGS = "delete_settings";
const REQUEST_TYPE_RENAME_SETTINGS = "rename_settings";
const REQUEST_TYPE_SAVE_SETTINGS = "save_settings";
const REQUEST_TYPE_GET_SETTINGS = "get_settings";
const REQUEST_TYPE_IS_LOGGED_IN = "is_logged_in";
const REQUEST_TYPE_GET_SAVED_SETTINGS_LINK = "get_saved_settings_link";
const REQUEST_TYPE_REVOKE_SAVED_SETTINGS_LINK = "revoke_saved_settings_link";
const REQUEST_TYPE_LOAD_SETTINGS_TOKEN = "load_settings_token";
const REQUEST_TYPE_ADD_MY_FAVORITE = "add_my_favorite";
const REQUEST_TYPE_ADD_TREE_FAVORITE = "add_tree_favorite";
const REQUEST_TYPE_GET_HELP = "get_help";
const REQUEST_TYPE_GET_SHARED_NOTE_FORM = "get_shared_note_form";
const REQUEST_TYPE_GET_RECORD_COUNT = "get_record_count";
const REQUEST_TYPE_ADD_CLIPPINGS_CART = "add_clippings_cart";
const REQUEST_TYPE_ADD_ALL_CLIPPINGS_CART = "add_all_clippings_cart";
const REQUEST_TYPE_COUNT_XREFS_CLIPPINGS_CART = "count_xrefs_clippings_cart";
const REQUEST_TYPE_GET_XREFS_CLIPPINGS_CART = "get_xrefs_clippings_cart";
const REQUEST_TYPE_IS_XREF_IN_CLIPPINGS_CART = "is_xref_in_clippings_cart";
const REQUEST_TYPE_DUMP_SETTINGS = "dump_settings";
let xrefCount = [];
let messageHistory = []; 



function loadXrefList(url, xrefListId, indiListId) {
    if (url === '') return false;

    let xrefListEl = document.getElementById(xrefListId);
    let xref_list = xrefListEl.value.trim();
    xrefListEl.value = xref_list;

    let promises = [];
    let xrefs = xref_list.split(',');
    for (let i=0; i<xrefs.length; i++) {
        if (xrefs[i].trim() !== "") {
            promises.push(Form.indiList.loadIndividualDetails(url, xrefs[i], indiListId));
        }
    }
    Promise.all(promises).then(function () {
        updateClearAll();
    }).catch(function(error) {
        UI.showToast("Error");
        console.log(error);
    });
}

function appendXrefToList(xref, elementId) {
    const list = document.getElementById(elementId);
    if (list.value.replace(',',"").trim() === "") {
        list.value = xref;
    } else {
        list.value += ',' + xref;
        list.value = list.value.replaceAll(',,',',');
    }
}
function toggleUpdateButton() {
    const updateBtn = document.getElementById('update-browser');
    const autoSettingBox = document.getElementById('auto_update');

    const visible = autoSettingBox.checked;
    Form.showHide(updateBtn, !visible);
    autoUpdate = visible;
    if (autoUpdate) updateRender();
}


// clear options from the dropdown if they are already in our list
function removeSearchOptions() {
    // Remove option when searching for starting indi if already in list
    document.getElementById('xref_list').value.split(',').forEach(function (xref) {
        removeSearchOptionFromList(xref, 'pid')
    });
    // Remove option when searching for stopping indi if already in list
    document.getElementById('stop_xref_list').value.split(',').forEach(function (xref) {
        removeSearchOptionFromList(xref, 'stop_pid')
    });
    // Remove option for shared note if already in list
    let notes = document.getElementById('sharednote_col_data').value;
    if (notes !== '') {
        try {
            let json = JSON.parse(notes);
            json.forEach(item => {
                removeSearchOptionFromList('@' + item.xref + '@', 'sharednote_col_add');
            });
        } catch (e) {
            document.getElementById('sharednote_col_data').value = '';
        }
    }

    // Remove option when searching diagram if indi not in diagram
    let dropdown = document.getElementById('diagram_search_box');
    if (dropdown.tomselect != null) {
        Object.keys(dropdown.tomselect.options).forEach(function (option) {
            if (!xrefCount.has(option)) {
                removeSearchOptionFromList(option, 'diagram_search_box');
            }
        });
    }
}
// clear options from the dropdown if they are already in our list
function removeSearchOptionFromList(xref, listId) {
    xref = xref.trim();
    if (xref !== "") {
        let dropdown = document.getElementById(listId);
        if (typeof dropdown.tomselect !== 'undefined') {
            dropdown.tomselect.removeOption(xref);
        }
    }
}

// Refresh the lists of people
function refreshIndisFromXREFS(onchange) {
    // If triggered from onchange event, only proceed if auto-update enabled
    if (!onchange || autoUpdate) {
        document.getElementById('indi_list').innerHTML = "";
        loadXrefList(TOMSELECT_URL, 'xref_list', 'indi_list');
        document.getElementById('stop_indi_list').innerHTML = "";
        loadXrefList(TOMSELECT_URL, 'stop_xref_list', 'stop_indi_list');
        Form.indiList.refreshIndisFromJson('highlight_custom_json', 'highlight_list');
        Form.famList.refreshFamsFromJson('highlight_custom_fams_json', 'highlight_fams_list');
    }
}

// Trigger clearAll update for each instance
function updateClearAll() {
    updateClearAllElements('clear_list', 'indi_list');
    updateClearAllElements('clear_stop_list', 'stop_indi_list');
}

// Show or hide Clear All options based on check
function updateClearAllElements(clearElementId, listItemElementId) {
    let clearElement = document.getElementById(clearElementId);
    let listItemElement = document.getElementById(listItemElementId);
    let listItems = listItemElement.getElementsByClassName('indi_list_item');
    if (listItems.length > 1) {
        Form.showHide(clearElement, true);
    } else {
        Form.showHide(clearElement, false);
    }
}

// Get the computed property of an element
function getComputedProperty(element, property) {
    const style = getComputedStyle(element);
    return (parseFloat(style.getPropertyValue(property)));
}

function removeSettingsEllipsisMenu(menuElement) {
    document.querySelectorAll('.settings_ellipsis_menu').forEach(e => {
        if (e !== menuElement) e.remove();
    });
}

function showGraphvizUnsupportedMessage() {
    if (graphvizAvailable && document.getElementById('photo_shape')?.value !== '0') UI.showToast(TRANSLATE["Diagram will be rendered in browser as server doesn't support photo shapes"]);
}

// Function to show a help message
// item - the help item identifier
function showHelp(item) {
    let helpText = '';
    if (item === 'message_history') {
        messageHistory.forEach((msg) => {
           helpText = '<div class="settings_list_item">' + msg[0].toLocaleString() + ": " + msg[1] + '</div>' + helpText; // most recent first
        });
        helpText = '<h3>' + TRANSLATE['Message history']+ '</h3>' + helpText;
    } else if (item === 'enable_debug_mode') {
        helpText = '<textarea cols=50 rows=20 onclick=\"this.select()\">' + debug_string + '</textarea>';
    }
    let content = "<p>" + helpText + "</p>";
    UI.showModal(content);
    return false;
}

/**
 * Loads settings from uploaded file
 */
function uploadSettingsFile(input) {
    if (input.files.length === 0) {
        return;
    }
    const file = input.files[0];
    let reader = new FileReader();
    reader.onload = (e) => {
        Form.settings.load(e.target.result);
    };
    reader.onerror = (e) => UI.showToast(e.target.error.name);
    reader.readAsText(file);
}

function toBool(value) {
    if (typeof value === 'string') {
        return (value === 'true');
    } else {
        return value;
    }
}

function setCheckStatus(el, checked) {
        el.checked = checked;
}

function setGraphvizAvailable(available) {
    graphvizAvailable = available;
}

function saveSettingsServer(main = true, id = null) {
    let request = {
        "type": REQUEST_TYPE_SAVE_SETTINGS,
        "main": main,
        "settings_id": id
    };
    let json = JSON.stringify(request);
    return Data.api.sendRequest(json);
}

function getSettingsServer(id = ID_ALL_SETTINGS) {
    let request = {
        "type": REQUEST_TYPE_GET_SETTINGS,
        "settings_id": id
    };
    let json = JSON.stringify(request);
    return Data.api.sendRequest(json).then((response) => {
        try {
            let json = JSON.parse(response);
            if (json.success) {
                return json.settings;
            } else {
                return ERROR_CHAR + json.errorMessage;
            }
        } catch(e) {
            UI.showToast(ERROR_CHAR + e);
        }
        return false;
    });
}

function getSettings(id = ID_ALL_SETTINGS) {
    return Data.api.isUserLoggedIn().then((loggedIn) => {
        if (loggedIn || id === ID_MAIN_SETTINGS) {
            return getSettingsServer(id);
        } else {
            return Data.storeSettings.getSettingsClient(id).then((obj) => {
                return JSON.stringify(obj);
            });
        }
    }).catch((error) => {
        UI.showToast(ERROR_CHAR + error);
    });
}

function loadSettingsDetails() {
    getSettings(ID_ALL_SETTINGS).then((settings) => {
        let settingsList;
        try {
            settingsList = JSON.parse(settings);
        } catch (e) {
            return TRANSLATE['Invalid JSON'] + e;
        }

        settingsList = Data.savedSettings.sortSettings(settingsList);

        const listElement = document.getElementById('settings_list');
        const simpleSettingsListEl = document.getElementById('simple_settings_list');
        if (simpleSettingsListEl !== null) {
            simpleSettingsListEl.innerHTML = "<option value=\"-\">-</option>";
        }
        listElement.innerHTML = "";
        Object.keys(settingsList).forEach (function(key) {
            const newLinkWrapper = document.createElement("a");
            newLinkWrapper.setAttribute("class", "pointer");
            const newListItem = document.createElement("div");
            newListItem.className = "settings_list_item";
            newListItem.setAttribute("data-settings", settingsList[key]['settings']);
            newListItem.setAttribute("data-id", settingsList[key]['id']);
            newListItem.setAttribute("data-token", settingsList[key]['token'] || "");
            newListItem.setAttribute("data-name", settingsList[key]['name']);
            newListItem.setAttribute("onclick", "Form.settings.load(this.getAttribute('data-settings'), true)");
            newListItem.innerHTML = "<a class='pointer'>" + settingsList[key]['name'] + "<div class=\"saved-settings-ellipsis pointer\" onclick='UI.savedSettings.showSavedSettingsItemMenu(event)'><a class='pointer'>…</a></div></a>";
            newLinkWrapper.appendChild(newListItem);
            listElement.appendChild(newLinkWrapper);

            if (simpleSettingsListEl !== null) {
                let option = document.createElement("option");
                option.value = settingsList[key]['id'];
                option.text = settingsList[key]['name'];
                simpleSettingsListEl.appendChild(option);
            }
        });
    }).catch(
        error => UI.showToast(error)
    );
}

function loadUrlToken(Url) {
    const token = Data.url.getURLParameter("t");
    if (token !== '') {
        let request = {
            "type": REQUEST_TYPE_LOAD_SETTINGS_TOKEN,
            "token": token
        };
        let json = JSON.stringify(request);
        Data.api.sendRequest(json).then((response) => {
            try {
                let json = JSON.parse(response);
                if (json.success) {
                    let settingsString = JSON.stringify(json.settings);
                    Form.settings.load(settingsString);
                    if(json.settings['auto_update']) {
                        UI.hideSidebar();
                    }
                } else {
                    UI.showToast(ERROR_CHAR + json.errorMessage);
                }
            } catch (e) {
                UI.showToast("Failed to load response: " + e);
                return false;
            }
        });
    }
} 

function getIdLocal() {
    return Data.api.getTreeName().then((treeName) => {
        let next_id;
        let settings_list = localStorage.getItem(SETTINGS_ID_LIST_NAME + "_" + treeName);
        if (settings_list) {
            let ids = settings_list.split(',');
            let last_id = ids[ids.length - 1];
            next_id = (parseInt(last_id, 36) + 1).toString(36);
            settings_list = ids.join(',') + ',' + next_id;
        } else {
            next_id = "0";
            settings_list = next_id;
        }

        localStorage.setItem(SETTINGS_ID_LIST_NAME + "_" + treeName, settings_list);
        return next_id;
    });
}

function deleteIdLocal(id) {
    Data.api.getTreeName().then((treeName) => {
        let settings_list;
        if (localStorage.getItem(SETTINGS_ID_LIST_NAME + "_" + treeName) != null) {
            settings_list = localStorage.getItem(SETTINGS_ID_LIST_NAME + "_" + treeName);
            settings_list = settings_list.split(',').filter(item => item !== id).join(',')
            localStorage.setItem(SETTINGS_ID_LIST_NAME + "_" + treeName, settings_list);
        }
    });
}

function setSavedDiagramsPanel() {
    const checkbox = document.getElementById('show_diagram_panel');
    const el = document.getElementById('saved_diagrams_panel');
    Form.showHide(el, checkbox.checked);
}

function toggleHighlightCheckbox(e) {
    let xref = e.target.getAttribute('data-xref');
    if (e.target.checked) {
        removeFromXrefList(xref, 'no_highlight_xref_list');
    } else {
        addToXrefList(xref, 'no_highlight_xref_list');
    }
    Form.handleFormChange();
}

function addToXrefList(value, listElName) {
    let xrefExcludeEl = document.getElementById(listElName);
    let xrefExcludeList = xrefExcludeEl.value;
    if (xrefExcludeList === "") {
        xrefExcludeEl.value = value;
    } else {
        let xrefExcludeArray = xrefExcludeEl.value.split(',');
        if (!xrefExcludeArray.includes(value)) {
            xrefExcludeArray[xrefExcludeArray.length] = value;
            xrefExcludeEl.value = xrefExcludeArray.join(',');
        }
    }
}
function removeFromXrefList(value, listElName) {
    let xrefExcludeEl = document.getElementById(listElName);
    let xrefExcludeArray = xrefExcludeEl.value.split(',');
    if (xrefExcludeArray.includes(value)) {
        const index = xrefExcludeArray.indexOf(value);
        xrefExcludeArray.splice(index, 1);
        xrefExcludeEl.value = xrefExcludeArray.join(',');
    }
}

// Simple diagram option was removed, but if settings are loaded that use it, we need to handle it.
// This function sets the display settings to mimic the simple diagram style
function handleSimpleDiagram() {
    // Disable photos - these weren't available in simple mode
    document.getElementById("show_photos").checked = false;
    // Set "details" font size to the same as the "Name" font size, as this is the only one used in simple mode
    document.getElementById("font_size").value = document.getElementById("font_size_name").value;
    // Set "details" font colour to the same as the "Name" font colour, as this is the only one used in simple mode
    document.getElementById("font_colour_details").value = document.getElementById("font_colour_name").value;
    // Set "Individual background colour" to "Based on individual's sex", to match style in simple mode
    document.getElementById("bg_col_type").value = 210;
    // Set diagram type to separated (referred to as decorated in code) as simple doesn't exist anymore
    document.getElementById("diagtype_decorated").checked = true;
}
