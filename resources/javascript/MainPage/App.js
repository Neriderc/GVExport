/**
 * Foundational data object
 *
 * @type {{}}
 */
const App = {
    // This function is run when the page is loaded
    async pageLoaded() {
        if (firstRender) {
            const loggedIn = await Data.api.isUserLoggedIn();
            if (!loggedIn) {
                const settings = await Data.storeSettings.getSettingsClient(ID_MAIN_SETTINGS);
                if (settings !== null) {
                    Form.settings.load(JSON.stringify(settings));
                } else {
                    firstRender = false;
                }
            }
        }

        TOMSELECT_URL = document.getElementById('pid').getAttribute("data-wt-url") + "&query=";
        TOMSELECT_FAM_URL = document.getElementById('highlight_fid').getAttribute("data-wt-url") + "&query=";
        loadUrlToken();
        loadSettingsDetails();
        Data.url.loadURLXref();
        refreshIndisFromXREFS(false);
        // Remove reset parameter from URL when page loaded, to prevent
        // further resets when page reloaded
        Data.url.removeURLParameter("reset");
        // Remove options from selection list if already selected
        setInterval(function () {removeSearchOptions()}, 100);
        // Listen for fullscreen change
        UI.handleFullscreen();
        Diagram.search.setup();

        if (document.getElementById("diagtype_simple") != null) {
            handleSimpleDiagram();
            document.getElementById("diagtype_simple").remove();
        }
        // Keep a global copy of the clippings cart
        Data.api.getClippingsCartXrefs();
        // Load browser render when page has loaded
        if (autoUpdate) updateRender();
        // Handle sidebars
        document.querySelector(".hide-form").addEventListener("click", UI.hideSidebar);
        document.querySelector(".sidebar_toggle a").addEventListener("click", UI.showSidebar);
        UI.helpPanel.init();
        UI.contextMenu.init();
        UI.fixTheme();
        Form.sharedNotePanel.init();

        // Change events
        const form = document.getElementById('gvexport');
        let changeElems = form.querySelectorAll("input:not([type='file']):not(#save_settings_name):not(#stop_pid):not(#highlight_pid):not(#highlight_fid):not(#highlight_custom_json):not(#sharednote_col_add), select:not(#simple_settings_list):not(#pid):not(#stop_pid):not(#sharednote_col_add):not(#settings_sort_order):not(#click_action_indi):not(#click_action_fam)");
        for (let i = 0; i < changeElems.length; i++) {
            changeElems[i].addEventListener("change", Form.handleFormChangeEvent);
        }
        let indiSelectEl = form.querySelector("#pid");
        indiSelectEl.addEventListener('change', Form.indiList.indiSelectChanged);
        let clickActionSelectEl = form.querySelector("#click_action_indi");
        clickActionSelectEl.addEventListener('change', UI.tile.clickOptionChanged);
        clickActionSelectEl = form.querySelector("#click_action_fam");
        clickActionSelectEl.addEventListener('change', UI.tile.clickOptionChanged);
        let stopIndiSelectEl = form.querySelector("#stop_pid");
        stopIndiSelectEl.addEventListener('change', Form.stopIndiSelectChanged);
        let highlightIndiSelectEl = form.querySelector("#highlight_pid");
        highlightIndiSelectEl.addEventListener('change', Form.indiList.highlightIndiSelectChanged);
        let highlightFamSelectEl = form.querySelector("#highlight_fid");
        highlightFamSelectEl.addEventListener('change', Form.famList.highlightFamSelectChanged);
        let settingsSortOrder = form.querySelector("#settings_sort_order");
        settingsSortOrder.addEventListener('change', loadSettingsDetails);
        let simpleSettingsEl = form.querySelector("#simple_settings_list");
        simpleSettingsEl.addEventListener('change', function(e) {
            let element = document.querySelector('.settings_list_item[data-id="' + e.target.value + '"]');
            if (element !== null) {
                Form.settings.load(element.getAttribute('data-settings'), true);
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
        let marriagetypeEl = form.querySelector("#show_marriage_type");
        marriagetypeEl.addEventListener('change', function(e) {
            Form.showHideMatchCheckbox('show_marriage_type', 'marriage_type_subgroup');
        });
        let divorcesEl = form.querySelector("#show_divorces");
        divorcesEl.addEventListener('change', function(e) {
            Form.showHideMatchCheckbox('show_divorces', 'divorces_subgroup');
        });

        let marriagesEl = form.querySelector("#show_marriages");
        marriagesEl.addEventListener('change', function(e) {
            Form.showHideMatchCheckbox('show_marriages', 'marriages_subgroup');
        });

        document.addEventListener("mousedown", function(event) {
            // Hide diagram context menu if clicked off a tile
            if (event.target.closest('.settings_ellipsis_menu_item') == null && event.target.parentElement?.id !== 'menu-info') {
                UI.contextMenu.clearContextMenu();
            }
        });

        document.addEventListener("click", function(event) {
            removeSettingsEllipsisMenu(event.target);
            if (!document.getElementById('searchButton').contains(event.target) && !document.getElementById('diagram_search_box_container').contains(event.target)) {
                Form.showHideSearchBox(event, false);
            }
        });
        document.querySelector("#diagram_search_box_container").addEventListener('change', Diagram.search.change);
        document.querySelector('#searchButton').addEventListener('click', Form.showHideSearchBox);
        document.querySelector('#photo_shape')?.addEventListener('change', showGraphvizUnsupportedMessage);
        document.querySelector('#indi_tile_shape')?.addEventListener('change', Form.event.changeIndiTileShape);

        document.addEventListener('change', (e) => {
            if (e.target.matches('input[name="vars[diagram_type]"]')) {
                Form.event.changeDiagramType(e);
            }
        });
    }
}