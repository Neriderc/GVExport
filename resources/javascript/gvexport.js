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
let treeName = null;
let loggedIn = null;
let xrefList = [];
let messageHistory = [];

function loadURLXref(Url) {
    const xref = Url.getURLParameter("xref");
    if (xref !== '') {
        const el = document.getElementById('xref_list');
        if (el.value.replace(',', "").trim() === "") {
            el.value = xref;
        } else if (!el.value.split(',').includes(xref)) {
            const xrefs = el.value.split(',');
            if (url_xref_treatment === 'default' && xrefs.length === 1 || url_xref_treatment === 'overwrite') {
                el.value = "";
            }
            if (url_xref_treatment !== 'nothing') {
                let startValue = el.value;
                addIndiToList(xref);
                if (url_xref_treatment === 'default' && xrefs.length === 1 ) {
                    setTimeout(function () {UI.showToast(TRANSLATE['Source individual has replaced existing individual'].replace('%s', xrefs.length.toString()))}, 100);
                } else if (startValue !== el.value && (url_xref_treatment === 'default' || url_xref_treatment === 'add')) {
                    setTimeout(function () {UI.showToast(TRANSLATE['One new source individual added to %s existing individuals'].replace('%s', xrefs.length.toString()))}, 100);
                }
            }
        }
    }
}

function indiSelectChanged() {
    let xref = document.getElementById('pid').value.trim();
    if (xref !== "") {
        addIndiToList(xref);
        mainPage.Url.changeURLXref(xref);
        if (autoUpdate) {
            updateRender();
        }
    }
}
function stopIndiSelectChanged() {
    let stopXref = document.getElementById('stop_pid').value.trim();
    if (stopXref !== "") {
        addIndiToStopList(stopXref);
    }
    if (autoUpdate) {
        updateRender();
    }
}

function loadXrefList(url, xrefListId, indiListId) {
    let xrefListEl = document.getElementById(xrefListId);
    let xref_list = xrefListEl.value.trim();
    xrefListEl.value = xref_list;

    let promises = [];
    let xrefs = xref_list.split(',');
    for (let i=0; i<xrefs.length; i++) {
        if (xrefs[i].trim() !== "") {
            promises.push(loadIndividualDetails(url, xrefs[i], indiListId));
        }
    }
    Promise.all(promises).then(function () {
        updateClearAll();
        toggleHighlightStartPersons(document.getElementById('highlight_start_indis').checked);
    }).catch(function(error) {
        UI.showToast("Error");
        console.log(error);
    });
}

function loadIndividualDetails(url, xref, list) {
    return fetch(url + xref.trim()).then(async (response) => {
            const data = await response.json();
            let contents;
            let otherXrefId;
            if (list === "indi_list") {
                otherXrefId = "xref_list";
            } else {
                otherXrefId = "stop_xref_list";
            }
            if (data["data"].length !== 0) {
                for (let i=0; i< data['data'].length; i++) {
                    if (xref.toUpperCase() === data['data'][i].value.toUpperCase()) {
                        contents = data["data"][i]["text"];
                        // Fix case if mismatched
                        if (xref !== data['data'][i].value) {
                            let listEl = document.getElementById(otherXrefId);
                            let indiList = listEl.value.split(',');
                            for (let j = indiList.length-1; j>=0; j--) {
                                if (indiList[j].trim() === xref.trim()) {
                                    indiList[j] = data["data"][i].value;
                                    break;
                                }
                            }
                            listEl.value = indiList.join(',');
                            setTimeout(()=>{refreshIndisFromXREFS(false)}, 100);
                            handleFormChange();
                        }
                    }
                }
            } else {
                contents = xref;
            }
            const listElement = document.getElementById(list);
            const newListItem = document.createElement("div");
            newListItem.className = "indi_list_item";
            newListItem.setAttribute("data-xref", xref);
            newListItem.setAttribute("onclick", "scrollToRecord('"+xref+"')");
            newListItem.innerHTML = contents + "<div class=\"saved-settings-ellipsis\" onclick=\"removeItem(event, this.parentElement, '" + otherXrefId + "')\"><a class='pointer'>×</a></div>";
            // Multiple promises can be for the same xref - don't add if a duplicate
            let item = listElement.querySelector(`[data-xref="${xref}"]`);
            if (item == null) {
                listElement.appendChild(newListItem);
            } else {
                newListItem.remove();
            }
        updateClearAll();
    })
}

function addIndiToList(xref) {
    let list = document.getElementById('xref_list');
    const regex = new RegExp(`(?<=,|^)(${xref})(?=,|$)`);
    if (!regex.test(list.value.replaceAll(" ','"))) {
        appendXrefToList(xref, 'xref_list');
        loadIndividualDetails(TOMSELECT_URL, xref, 'indi_list').then(() => {
            toggleHighlightStartPersons(document.getElementById('highlight_start_indis').checked);
        })

    }
    clearIndiSelect('pid');
}

function addIndiToStopList(xref) {
    let list = document.getElementById('stop_xref_list');
    const regex = new RegExp(`(?<=,|^)(${xref})(?=,|$)`);
    if (!regex.test(list.value.replaceAll(" ','"))) {
        appendXrefToList(xref, 'stop_xref_list');
        loadIndividualDetails(TOMSELECT_URL, xref, 'stop_indi_list');
    }
    clearIndiSelect('stop_pid');
}

function appendXrefToList(xref, elementId) {
    const list = document.getElementById(elementId);
    if (list.value.replace(',',"").trim() === "") {
        list.value = xref;
    } else {
        list.value += ',' + xref;
        list.value = list.value.replaceAll(",,',',");
    }
}

function clearIndiSelect(selectId) {
    let dropdown = document.getElementById(selectId);
    if (typeof dropdown.tomselect !== 'undefined') {
        dropdown.tomselect.clear();
    } else {
        setTimeout(function () {
            clearIndiSelect(selectId);
        }, 100);
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

function removeItem(e, element, xrefListId) {
    e.stopPropagation();
    let xref = element.getAttribute("data-xref").trim();
    let list = document.getElementById(xrefListId);
    const regex = new RegExp(`(?<=,|^)(${xref})(?=,|$)`);
    list.value = list.value.replaceAll(" ','").replace(regex, "");
    list.value = list.value.replace(",,", ',');
    if (list.value.substring(0,1) === ',') {
        list.value = list.value.substring(1);
    }
    if (list.value.substring(list.value.length-1) === ',') {
        list.value = list.value.substring(0, list.value.length-1);
    }
    element.remove();
    mainPage.Url.changeURLXref(list.value.split(',')[0].trim());
    updateClearAll();
    removeFromXrefList(xref, 'no_highlight_xref_list');
    toggleHighlightStartPersons(document.getElementById('highlight_start_indis').checked);
    if (autoUpdate) {
        updateRender();
    }
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
    // Remove option when searching diagram if indi not in diagram
    let dropdown = document.getElementById('diagram_search_box');
    if (dropdown.tomselect != null) {
        Object.keys(dropdown.tomselect.options).forEach(function (option) {
            if (!xrefList.includes(option)) {
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

// Clear the list of starting individuals
function clearIndiList() {
    document.getElementById('xref_list').value = "";
    document.getElementById('indi_list').innerHTML = "";
    updateClearAll();
    if (autoUpdate) updateRender();
}
// Clear the list of starting individuals
function clearStopIndiList() {
    document.getElementById('stop_xref_list').value = "";
    document.getElementById('stop_indi_list').innerHTML = "";
    updateClearAll();
    if (autoUpdate) updateRender();
}

// Refresh the list of starting and stopping individuals
function refreshIndisFromXREFS(onchange) {
    // If triggered from onchange event, only proceed if auto-update enabled
    if (!onchange || autoUpdate) {
        document.getElementById('indi_list').innerHTML = "";
        loadXrefList(TOMSELECT_URL, 'xref_list', 'indi_list');
        document.getElementById('stop_indi_list').innerHTML = "";
        loadXrefList(TOMSELECT_URL, 'stop_xref_list', 'stop_indi_list');
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

// Toggle full screen for element
// Modified from https://stackoverflow.com/questions/7130397/how-do-i-make-a-div-full-screen
function toggleFullscreen() {
    // If already fullscreen, exit fullscreen
    if (
        document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.mozFullScreenElement ||
        document.msFullscreenElement
    ) {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    } else { // Not full screen, so go fullscreen
        const element = document.getElementById('render-container');
        if (element.requestFullscreen) {
            element.requestFullscreen();
        } else if (element.mozRequestFullScreen) {
            element.mozRequestFullScreen();
        } else if (element.webkitRequestFullscreen) {
            element.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
        } else if (element.msRequestFullscreen) {
            element.msRequestFullscreen();
        }
    }
}

// Add a listener to trigger when the user goes fullscreen or exits fullscreen
function handleFullscreen() {
    if (document.addEventListener)
    {
        document.addEventListener('fullscreenchange', handleFullscreenExit, false);
        document.addEventListener('mozfullscreenchange', handleFullscreenExit, false);
        document.addEventListener('MSFullscreenChange', handleFullscreenExit, false);
        document.addEventListener('webkitfullscreenchange', handleFullscreenExit, false);
    }
}

// This function is run when the fullscreen state is changed
function handleFullscreenExit()
{
    if (!document.webkitIsFullScreen && !document.mozFullScreen && !document.msFullscreenElement)
    {
        Form.showHide(document.getElementById("fullscreenButton"), true);
        Form.showHide(document.getElementById("fullscreenClose"), false);
    } else {
        Form.showHide(document.getElementById("fullscreenButton"), false);
        Form.showHide(document.getElementById("fullscreenClose"), true);
    }
}

// Get the computed property of an element
function getComputedProperty(element, property) {
    const style = getComputedStyle(element);
    return (parseFloat(style.getPropertyValue(property)));
}


// If the browser render is available, scroll to the xref provided (if it exists)
function scrollToRecord(xref) {
    const rendering = document.getElementById('rendering');
    const svg = rendering.getElementsByTagName('svg')[0].cloneNode(true);
    let titles = svg.getElementsByTagName('title');
    for (let i=0; i<titles.length; i++) {
        let xrefs = titles[i].innerHTML.split("_");
        for (let j=0; j<xrefs.length; j++) {
            if (xrefs[j] === xref) {
                let minX = null;
                let minY = null;
                let maxX = null;
                let maxY = null;
                let x = null;
                let y = null;
                const group = titles[i].parentElement;
                // We need to locate the element within the SVG. We use "polygon" here because it is the
                // only element that will always exist and that also has position information
                // (other elements like text, image, etc. can be disabled by the user)
                const polygonList = group.getElementsByTagName('polygon');
                let points;
                if (polygonList.length !== 0) {
                    points = polygonList[0].getAttribute('points').split(" ");
                    // Find largest and smallest X and Y value out of all the points of the polygon
                    for (let k = 0; k < points.length; k++) {
                        // If path instructions, ignore
                        if (points[k].replace(/[a-z]/gi, '') !== points[k]) break;
                        const x = parseFloat(points[k].split(',')[0]);
                        const y = parseFloat(points[k].split(',')[1]);
                        if (minX === null || x < minX) {
                            minX = x;
                        }
                        if (minY === null || y < minY) {
                            minY = y;
                        }
                        if (maxX === null || x > maxX) {
                            maxX = x;
                        }
                        if (maxY === null || y > maxY) {
                            maxY = y;
                        }
                    }

                    // Get the average of the largest and smallest, so we can position the element in the middle
                    x = (minX + maxX) / 2;
                    y = (minY + maxY) / 2;
                } else {
                    x = group.getElementsByTagName('text')[0].getAttribute('x');
                    y = group.getElementsByTagName('text')[0].getAttribute('y')
                }

                // Why do we multiply the scale by 1 and 1/3?
                let zoombase = panzoomInst.getTransform().scale * (1 + 1 / 3);
                let zoom = zoombase * parseFloat(document.getElementById("dpi").value)/72;
                panzoomInst.smoothMoveTo((rendering.offsetWidth / 2) - x * zoom, (rendering.offsetHeight / 2) - parseFloat(svg.getAttribute('height')) * zoombase - y * zoom);
                return true;
            }
        }
    }
    return false;
}

// Return distance between two points
function getDistance(x1, y1, x2, y2){
    let x = x2 - x1;
    let y = y2 - y1;
    return Math.sqrt(x * x + y * y);
}

function handleTileClick() {
    const MIN_DRAG = 100;
    let startx;
    let starty;

    let linkElements = document.querySelectorAll("svg a");
    for (let i = 0; i < linkElements.length; i++) {
        linkElements[i].addEventListener("mousedown", function(e) {
            startx = e.clientX;
            starty = e.clientY;
        });
        // Only trigger links if not dragging
        linkElements[i].addEventListener("click", function(e) {
            if (getDistance(startx, starty, e.clientX, e.clientY) >= MIN_DRAG) {
                e.preventDefault();
            }
        });
    }
}

function handleFormChange() {
    if (autoUpdate) updateRender();
}

function removeSettingsEllipsisMenu(menuElement) {
    document.querySelectorAll('.settings_ellipsis_menu').forEach(e => {
        if (e !== menuElement) e.remove();
    });
}

function showGraphvizUnsupportedMessage() {
    if (graphvizAvailable && document.getElementById('photo_shape')?.value !== '0') UI.showToast(TRANSLATE["Diagram will be rendered in browser as server doesn't support photo shapes"]);
}

// This function is run when the page is loaded
function pageLoaded(Url) {
    TOMSELECT_URL = document.getElementById('pid').getAttribute("data-url") + "&query=";
    loadURLXref(Url);
    loadUrlToken(Url);
    loadXrefList(TOMSELECT_URL, 'xref_list', 'indi_list');
    loadXrefList(TOMSELECT_URL, 'stop_xref_list', 'stop_indi_list');
    loadSettingsDetails();
    // Remove reset parameter from URL when page loaded, to prevent
    // further resets when page reloaded
    Url.removeURLParameter("reset");
    // Remove options from selection list if already selected
    setInterval(function () {removeSearchOptions()}, 100);
    // Listen for fullscreen change
    handleFullscreen();

    if (document.getElementById("diagtype_simple") != null) {
        handleSimpleDiagram();
        document.getElementById("diagtype_simple").remove();
    }

    // Load browser render when page has loaded
    if (autoUpdate) updateRender();
    // Handle sidebars
    document.querySelector(".hide-form").addEventListener("click", UI.hideSidebar);
    document.querySelector(".sidebar_toggle a").addEventListener("click", UI.showSidebar);
    UI.helpPanel.init();
    UI.fixTheme();

    // Form change events
    const form = document.getElementById('gvexport');
    let checkboxElems = form.querySelectorAll("input:not([type='file']):not(#save_settings_name):not(#stop_pid):not(.highlight_check), select:not(#simple_settings_list):not(#pid)");
    for (let i = 0; i < checkboxElems.length; i++) {
        checkboxElems[i].addEventListener("change", handleFormChange);
    }
    let indiSelectEl = form.querySelector("#pid");
    indiSelectEl.addEventListener('change', indiSelectChanged);

    let stopIndiSelectEl = form.querySelector("#stop_pid");
    stopIndiSelectEl.addEventListener('change', stopIndiSelectChanged);

    let simpleSettingsEl = form.querySelector("#simple_settings_list");
    simpleSettingsEl.addEventListener('change', function(e) {
        let element = document.querySelector('.settings_list_item[data-id="' + e.target.value + '"]');
        if (element !== null) {
            loadSettings(element.getAttribute('data-settings'), true);
        } else if (e.target.value !== '-') {
            UI.showToast(ERROR_CHAR + 'Settings not found')
        }
    })
    document.addEventListener("keydown", function(e) {
        if (e.key === "Esc" || e.key === "Escape") {
            document.querySelector(".sidebar").hidden ? UI.showSidebar(e) : UI.hideSidebar(e);
            UI.helpPanel.hideHelpSidebar(e);
        }
    });
    document.addEventListener("click", function(event) {
        removeSettingsEllipsisMenu(event.target);
        if (!document.getElementById('searchButton').contains(event.target) && !document.getElementById('diagram_search_box_container').contains(event.target)) {
            Form.showHideSearchBox(event, false);
        }
    });
    document.querySelector("#diagram_search_box_container").addEventListener('change', diagramSearchBoxChange);
    document.querySelector('#searchButton').addEventListener('click', Form.showHideSearchBox);
    document.querySelector('#photo_shape')?.addEventListener('change', showGraphvizUnsupportedMessage);
}

// Function to show a help message
// item - the help item identifier
function showModal(content) {
    const modal = document.createElement("div");
    modal.className = "modal";
    modal.id = "modal";
    modal.innerHTML = "<div class=\"modal-content\">\n" +
        '<span class="close" onclick="document.getElementById(' + "'modal'" + ').remove()">&times;</span>\n' +
        content + "\n" +
        "</div>"
    document.body.appendChild(modal);
    // When the user clicks anywhere outside the modal, close it
    window.onclick = function(event) {
        if (event.target === modal) {
            modal.remove();
        }
    }
    return false;
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
    } else {
        helpText =  '<textarea cols=50 rows=20 onclick=\"this.select()\">' + debug_string + '</textarea>';
    }
    let content = "<p>" + helpText + "</p>";
    showModal(content);
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
        loadSettings(e.target.result);
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
function loadSettings(data, isNamedSetting = false) {
    let settings;
    try {
        settings = JSON.parse(data);
    } catch (e) {
        UI.showToast("Failed to load settings: " + e);
        return false;
    }
    Object.keys(settings).forEach(function(key){
        let el = document.getElementById(key);
        if (el == null) {
            switch (key) {
                case 'diagram_type':
                    if (settings[key] === 'simple') {
                        setTimeout(() => {
                            handleSimpleDiagram();
                            if (autoUpdate) updateRender();
                            },1);
                    } else {
                        setCheckStatus(document.getElementById('diagtype_decorated'), settings[key] === 'decorated');
                        setCheckStatus(document.getElementById('diagtype_combined'), settings[key] === 'combined');
                    }
                    break;
                case 'combined_layout_type':
                    setCheckStatus(document.getElementById('cl_type_ss'), settings[key] === 'SS');
                    setCheckStatus(document.getElementById('cl_type_ou'), settings[key] === 'OU');
                    break;
                case 'birthdate_year_only':
                    setCheckStatus(document.getElementById('bd_type_y'), toBool(settings[key]));
                    setCheckStatus(document.getElementById('bd_type_gedcom'), !toBool(settings[key]));
                    break;
                case 'death_date_year_only':
                    setCheckStatus(document.getElementById('dd_type_y'), toBool(settings[key]));
                    setCheckStatus(document.getElementById('dd_type_gedcom'), !toBool(settings[key]));
                    break;
                case 'marr_date_year_only':
                    setCheckStatus(document.getElementById('md_type_y'), toBool(settings[key]));
                    setCheckStatus(document.getElementById('md_type_gedcom'), !toBool(settings[key]));
                    break;
                case 'show_adv_people':
                    Form.toggleAdvanced(document.getElementById('people-advanced-button'), 'people-advanced', toBool(settings[key]));
                    break;
                case 'show_adv_appear':
                    Form.toggleAdvanced(document.getElementById('appearance-advanced-button'), 'appearance-advanced', toBool(settings[key]));
                    break;
                case 'show_adv_files':
                    Form.toggleAdvanced(document.getElementById('files-advanced-button'), 'files-advanced', toBool(settings[key]));
                    break;
                // If option to use cart is not showing, don't load, but also don't show error
                case 'use_cart':
                // These options only exist if debug panel active - don't show error if not found
                case 'enable_debug_mode':
                case 'enable_graphviz':
                // Token is not loaded as an option
                case 'token':
                    break;
                default:
                    UI.showToast(ERROR_CHAR + TRANSLATE['Unable to load setting'] + " " + key);
            }
        } else {
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (!isNamedSetting || key !== 'show_diagram_panel') {
                    setCheckStatus(el, toBool(settings[key]));
                }
            } else {
                el.value = settings[key];
            }
        }

        // Update show/hide of JPG quality option
        Form.showHideMatchDropdown('output_type', 'server_pdf_subgroup', 'pdf|svg|jpg')
    });
    Form.showHideMatchCheckbox('mark_not_related', 'mark_related_subgroup');
    Form.showHideMatchCheckbox('show_birthdate', 'birth_date_subgroup');
    Form.showHideMatchCheckbox('show_death_date', 'death_date_subgroup');
    setSavedDiagramsPanel();
    Form.showHide(document.getElementById('arrow_group'),document.getElementById('colour_arrow_related').checked)
    Form.showHide(document.getElementById('startcol_option'),document.getElementById('highlight_start_indis').checked)

    if (autoUpdate) {
        updateRender();
    }
    refreshIndisFromXREFS(false);
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
    return sendRequest(json);
}

function getSettingsServer(id = ID_ALL_SETTINGS) {
    let request = {
        "type": REQUEST_TYPE_GET_SETTINGS,
        "settings_id": id
    };
    let json = JSON.stringify(request);
    return sendRequest(json).then((response) => {
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


function getSettingsClient(id = ID_ALL_SETTINGS) {
    return getTreeName().then(async (treeName) => {
        try {
            if (id === ID_ALL_SETTINGS) {
                if (localStorage.getItem(SETTINGS_ID_LIST_NAME + "_" + treeName)) {
                    let settings_list = localStorage.getItem(SETTINGS_ID_LIST_NAME + "_" + treeName);
                    let ids = settings_list.split(',');
                    let promises = ids.map(id_value => getSettingsClient(id_value))
                    let results = await Promise.all(promises);
                    let settings = {};
                    for (let i = 0; i < ids.length; i++) {
                        let id_value = ids[i];
                        let userSettings = results[i];
                        if (userSettings === null) {
                            return Promise.reject('User settings null');
                        } else {
                        settings[id_value] = {};
                        settings[id_value]['name'] = userSettings['save_settings_name'];
                        settings[id_value]['id'] = id_value;
                        settings[id_value]['settings'] = JSON.stringify(userSettings);}
                    }
                    return settings;
                } else {
                    return {};
                }
            } else {
                let settings_id = id === ID_MAIN_SETTINGS ? "" : id;
                try {
                    return JSON.parse(localStorage.getItem("GVE_Settings_" + treeName + "_" + settings_id));
                } catch(e) {
                    return Promise.reject(e);
                }
            }

        } catch(e) {
            return Promise.reject(e);
        }
    }).catch((e) => {
        UI.showToast(ERROR_CHAR + e);
    });
}

function getSettings(id = ID_ALL_SETTINGS) {
    return isUserLoggedIn().then((loggedIn) => {
        if (loggedIn || id === ID_MAIN_SETTINGS) {
            return getSettingsServer(id);
        } else {
            return getSettingsClient(id).then((obj) => {
                return JSON.stringify(obj);
            });
        }
    }).catch((error) => {
        UI.showToast(ERROR_CHAR + error);
    });
}
function sendRequest(json) {
    return new Promise((resolve, reject) => {
        const form = document.getElementById('gvexport');
        const el = document.createElement("input");
        el.name = "json_data";
        el.value = json;
        form.appendChild(el);
        document.getElementById("browser").value = "true";
        let data = jQuery(form).serialize();
        document.getElementById("browser").value = "false";
        el.remove();
        window.fetch(form.getAttribute('action'), {
            method: form.getAttribute('method'),
            credentials: 'same-origin', // include, *same-origin, omit
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: data
        }).then(function (response) {
            if (!response.ok) {
                return response.text().then(function (errorText) {
                    return reject(errorText)
                });
            }
            resolve(response.text());
        }).catch((e) => {
            reject(e);
        });
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
            newListItem.setAttribute("onclick", "loadSettings(this.getAttribute('data-settings'), true)");
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

function saveSettingsAdvanced(userPrompted = false) {
    let settingsList = document.getElementsByClassName('settings_list_item');
    let settingsName = document.getElementById('save_settings_name').value;
    if (settingsName === '') settingsName = "Settings";
    let id = null;
    for (let i=0; i<settingsList.length; i++) {
        if (settingsList[i].getAttribute('data-name') === settingsName) {
            id = settingsList[i].getAttribute('data-id');
        }
    }
    if (id !== null) {
        if (userPrompted) {
            document.getElementById('modal').remove();
        } else {
            let message = TRANSLATE["Overwrite settings '%s'?"].replace('%s', settingsName);
            let buttons = '<div class="modal-button-container"><button class="btn btn-secondary modal-button" onclick="document.getElementById(' + "'modal'" + ').remove()">' + TRANSLATE['Cancel'] + '</button><button class="btn btn-primary modal-button" onclick="saveSettingsAdvanced(true)">' + TRANSLATE['Overwrite'] + '</button></div>';
            showModal('<div class="modal-container">' + message + '<br>' + buttons + '</div>');
            return false;
        }
    }

    isUserLoggedIn().then((loggedIn) => {
        if (loggedIn) {
            return saveSettingsServer(false, id).then((response)=>{
                try {
                    let json = JSON.parse(response);
                    if (json.success) {
                        return response;
                    } else {
                        return Promise.reject(ERROR_CHAR + json.errorMessage);
                    }
                } catch (e) {
                    return Promise.reject("Failed to load response: " + e);
                }
            });
        } else {
            if (id === null) {
                return getIdLocal().then((newId) => {
                    return saveSettingsClient(newId);
                });
            } else {
                return saveSettingsClient(id);
            }
        }
    }).then(() => {
        loadSettingsDetails();
        document.getElementById('save_settings_name').value = "";
    }).catch(
        error => UI.showToast(error)
    );

}

function loadUrlToken(Url) {
    const token = Url.getURLParameter("t");
    if (token !== '') {
        let request = {
            "type": REQUEST_TYPE_LOAD_SETTINGS_TOKEN,
            "token": token
        };
        let json = JSON.stringify(request);
        sendRequest(json).then((response) => {
            try {
                let json = JSON.parse(response);
                if (json.success) {
                    let settingsString = JSON.stringify(json.settings);
                    loadSettings(settingsString);
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

function isUserLoggedIn() {
    if (loggedIn != null)  {
        return Promise.resolve(loggedIn);
    } else {
        let request = {
            "type": REQUEST_TYPE_IS_LOGGED_IN
        };
        let json = JSON.stringify(request);
        return sendRequest(json).then((response) => {
            try {
                let json = JSON.parse(response);
                if (json.success) {
                    loggedIn = json.loggedIn;
                    return json.loggedIn;
                } else {
                    return Promise.reject(ERROR_CHAR + json.errorMessage);
                }
            } catch (e) {
                return Promise.reject("Failed to load response: " + e);
            }
        });
    }
}

function getTreeName() {
    if (treeName != null)  {
        return Promise.resolve(treeName);
    } else {
        let request = {
            "type": REQUEST_TYPE_GET_TREE_NAME
        };
        let json = JSON.stringify(request);
        return sendRequest(json).then((response) => {
            try {
                let json = JSON.parse(response);
                if (json.success) {
                    treeName = json.treeName.replace(/[^a-zA-Z0-9_]/g, ""); // Only allow characters that play nice
                    return treeName;
                } else {
                    return Promise.reject(ERROR_CHAR + json.errorMessage);
                }
            } catch (e) {
                return Promise.reject("Failed to load response: " + e);
            }
        });
    }
}

function saveSettingsClient(id) {
    return Promise.all([saveSettingsServer(true), getTreeName()])
        .then(([, treeNameLocal]) => {
            return getSettings(ID_MAIN_SETTINGS).then((settings_json_string) => [settings_json_string,treeNameLocal]);
        })
        .then(([settings_json_string, treeNameLocal]) => {
            try {
                JSON.parse(settings_json_string);
            } catch (e) {
                return Promise.reject("Invalid JSON 2");
            }
            localStorage.setItem("GVE_Settings_" + treeNameLocal + "_" + id, settings_json_string);
            return Promise.resolve();
        });
}

function getIdLocal() {
    return getTreeName().then((treeName) => {
        let next_id;
        let settings_list = localStorage.getItem(SETTINGS_ID_LIST_NAME + "_" + treeName);
        if (settings_list) {
            settings_list = localStorage.getItem(SETTINGS_ID_LIST_NAME + "_" + treeName);
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
    getTreeName().then((treeName) => {
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

// From https://stackoverflow.com/questions/51805395/navigator-clipboard-is-undefined
function copyToClipboard(textToCopy) {
    // navigator clipboard api needs a secure context (https)
    if (navigator.clipboard && window.isSecureContext) {
        // navigator clipboard api method'
        return navigator.clipboard.writeText(textToCopy);
    } else {
        // text area method
        let textArea = document.createElement("textarea");
        textArea.value = textToCopy;
        // make the textarea out of viewport
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        textArea.style.top = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        return new Promise((res, rej) => {
            // here the magic happens
            document.execCommand('copy') ? res() : rej();
            textArea.remove();
        });
    }
}

function toggleHighlightCheckbox(e) {
    let xref = e.target.getAttribute('data-xref');
    if (e.target.checked) {
        removeFromXrefList(xref, 'no_highlight_xref_list');
    } else {
        addToXrefList(xref, 'no_highlight_xref_list');
    }
    handleFormChange();
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


function toggleHighlightStartPersons(enable, adminPage) {
    if (enable && !adminPage) {
        let list = document.getElementById('highlight_list');
        let xrefList = document.getElementById('xref_list');
        let xrefExcludeArray = document.getElementById('no_highlight_xref_list').value.split(',');
        list.innerHTML = '';
        let xrefs = xrefList.value.split(',');
        for (let i=0; i<xrefs.length; i++) {
            if (xrefs[i].trim() !== "") {
                const xrefItem = document.createElement('div');
                const checkboxEl = document.createElement('input');
                checkboxEl.setAttribute('id', 'highlight_check' + i);
                checkboxEl.setAttribute('class', 'highlight_check');
                checkboxEl.setAttribute('type', 'checkbox');
                checkboxEl.setAttribute('data-xref', xrefs[i]);
                if (!xrefExcludeArray.includes(xrefs[i])) {
                    checkboxEl.checked = true;
                }
                checkboxEl.addEventListener("click", toggleHighlightCheckbox);
                xrefItem.appendChild(checkboxEl);
                const indiItem = document.getElementById('indi_list')
                    .querySelector('.indi_list_item[data-xref="' + xrefs[i] + '"]');
                let indiName = "";
                if (indiItem != null) {
                    indiName = indiItem.getElementsByClassName("NAME")[0].innerText;
                }
                const labelEl = document.createElement('label');
                labelEl.setAttribute('class', 'highlight_check_label');
                labelEl.setAttribute('for', 'highlight_check' + i);
                labelEl.innerHTML = indiName + " (" + xrefs[i] + ")";
                xrefItem.appendChild(labelEl);
                list.appendChild(xrefItem);
            }
        }
    }
    Form.showHide(document.getElementById('startcol_option'),enable);
}

function setSvgImageClipPath(element, clipPath) {
    // Circle photo
    const imageElements = element.getElementsByTagName("image");
    for (let i = 0; i < imageElements.length; i++) {
        imageElements[i].setAttribute("clip-path", clipPath);
        imageElements[i].removeAttribute("width");
    }
}

// Tidies SVG before embedding in page
function cleanSVG(element) {
    const SHAPE_OVAL = '10';
    const SHAPE_CIRCLE = '20';
    const SHAPE_SQUARE = '30';
    const SHAPE_ROUNDED_RECT = '40';
    const SHAPE_ROUNDED_SQUARE = '50';
    switch(document.getElementById('photo_shape')?.value) {
        case SHAPE_OVAL:
            setSvgImageClipPath(element, "inset(0% round 50%)");
            break;
        case SHAPE_CIRCLE:
            setSvgImageClipPath(element, "circle(50%)");
            break;
        case SHAPE_SQUARE:
            setSvgImageClipPath(element, "inset(5%)");
            break;
        case SHAPE_ROUNDED_RECT:
            setSvgImageClipPath(element, "inset(0% round 25%)");
            break;
        case SHAPE_ROUNDED_SQUARE:
            setSvgImageClipPath(element, "inset(0% round 25%)");
            break;
    }

    // remove title tags, so we don't get weird data on hover,
    // instead this defaults to the XREF of the record
    const a = element.getElementsByTagName("a");
    for (let i = 0; i < a.length; i++) {
        a[i].removeAttribute("xlink:title");
    }
    //half of bug fix for photos not showing in browser - we change & to %26 in functions_dot.php
    element.innerHTML = element.innerHTML.replaceAll("%26", "&amp;");
    // Don't show anything when hovering on blank space
    element.innerHTML = element.innerHTML.replaceAll("<title>WT_Graph</title>", "");
    // Set SVG viewBox to height/width so image is not cut off
    element.setAttribute("viewBox", "0 0 " + element.getAttribute("width").replace("pt", "") + " " + element.getAttribute("height").replace("pt", ""));
}

function diagramSearchBoxChange(e) {
    let xref = document.getElementById('diagram_search_box').value.trim();
    // Skip the first trigger, only fire for the follow-up trigger when the XREF is set
    if (xref !== ""){
        if (!scrollToRecord(xref)) {
            UI.showToast(TRANSLATE['Individual not found']);
        }
        clearIndiSelect('diagram_search_box');
        Form.showHideSearchBox(e, false);
    }
}

function createXrefListFromSvg() {
    xrefList = [];
    const rendering = document.getElementById('rendering');
    const svg = rendering.getElementsByTagName('svg')[0].cloneNode(true);
    let titles = svg.getElementsByTagName('title');
    for (let i=0; i<titles.length; i++) {
        let xrefs = titles[i].innerHTML.split("_");
        for (let j = 0; j < xrefs.length; j++) {
            // Ignore the arrows that go between records
            if (!xrefs[j].includes("&gt;")) {
                xrefList.push(xrefs[j]);
            }
        }
    }
}

// In a tomselect, the option chosen goes into a box that is initially blank. For the search box,
// this blank space is never used (as the selected option is not filled to the box). This function
// removes this to give a cleaner search box.
function tidyTomSelect() {
    let searchContainer = document.getElementById('diagram_search_box_container');
    let control = document.getElementById('diagram_search_box-ts-control');

    if (control !== null) {
        control.remove();
    }
    let tomWrappers = searchContainer.getElementsByClassName('ts-wrapper');
    if (tomWrappers.length > 0) {
        Array.from(tomWrappers).forEach((wrapper) => {
            wrapper.className = "";
        })
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