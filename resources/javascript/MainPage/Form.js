/**
 * Data object to hold form related functionality
 *
 * @type {{}}
 */
const Form = {
    
    /**
     * Form change event
     */

    handleFormChangeEvent(e) {
        Form.handleFormChange();
    },

    /**
     * Triggers on form change, to update diagram if autoUpdate enabled
     *
     * @param xref xref of tile we want to keep in the same position
     */
    handleFormChange(xref = null, xrefType = 'indi') {
    if (autoUpdate) {
            // If xref has been nominated, calculate the position on screen, so we can keep it in the same place
            if (xref) {
                let [found, x, y] = UI.tile.getElementPositionFromXref(xref, xrefType);
                if (found) {
                    let scale = panzoomInst.getTransform().scale;
                    // Why is this 1 1/3 number needed?
                    let zoomBase = scale * (1 + 1/3);
                    let zoom_value = zoomBase * parseFloat(document.getElementById("dpi").value) / 72;
                    const rendering = document.getElementById('rendering');
                    const svg = rendering.getElementsByTagName('svg')[0];
                    let transform = panzoomInst.getTransform();
                    updateRender(x*zoom_value + transform.x, parseFloat(svg.getAttribute('height'))*zoomBase + y*zoom_value + transform.y, transform.scale, xref, xrefType);
                } else {
                    updateRender();
                }
            // Nothing fany happening, just update
            } else {
                updateRender();
            }
        }
    },

    /**
     * Add or remove the % sign from the text input
     *
     * @param element
     * @param add
     */
    togglePercent(element, add) {
        // Clicked out of input field, add % sign
        let startValue;
        if (add) {
            // Keep just numbers
            let boxVal = element.value.replace(/\D/g, "");
            // If result is blank, set to default
            if (boxVal === "") {
                boxVal = "100";
            }
            element.value =  boxVal + "%";
        } else {
            // Clicked in input box, remove % and select text,
            // but only select text the first time, let user move cursor if they want
            startValue = element.value;
            element.value = element.value.replace("%", "");
            if (startValue !== element.value) {
                element.select();
            }
        }
    },

    /**
     * Update provided element with provided value when element blank
     *
     * @param element
     * @param value
     */
    setDefaultValueIfBlank(element, value) {
        if (element.value === "") {
            element.value = value;
        }
    },

    /**
     * Decide if we should use client side generation of diagram or server side, depending on various factors
     *
     * @returns {boolean}
     */
    mustUseClientGeneration() {
        let optionsUnsupported = (document.getElementById('photo_shape')?.value !== '0');
        return !graphvizAvailable || optionsUnsupported;
    },
    /**
     * Checks if a starting individual is selected or the list is blank
     *
     * @returns {boolean}
     */
    isIndiBlank() {
        let el = document.getElementsByClassName("item");
        let list = document.getElementById('xref_list');
        return el.length === 0 && list.value.toString().length === 0;
    },
    /**
     * This function ensures that if certain options are checked in regard to which relations to include,
     * then other required options are selected. e.g. if "Anyone" is selected, all other options are
     * set to selected.
     *
     * @param field
     */
    updateRelationOption(field) {
        // If user clicked "All relatives"
        if (field === "include_all_relatives") {
            // If function triggered by checking "All relatives" field, ensure "Siblings" is checked
            if (document.getElementById("include_all_relatives").checked) {
                document.getElementById("include_siblings").checked = true;
            }
            // If "All relatives" unchecked, uncheck "Anyone"
            if (!document.getElementById("include_all_relatives").checked) {
                document.getElementById("include_all").checked = false;
            }
        }
        // If user clicked "Siblings"
        if (field === "include_siblings") {
            // If function triggered by unchecking "Siblings" field, ensure "All relatives" is unchecked
            if (!document.getElementById("include_siblings").checked) {
                document.getElementById("include_all_relatives").checked = false;
            }
            // If "Siblings" unchecked, uncheck "Anyone"
            if (!document.getElementById("include_siblings").checked) {
                document.getElementById("include_all").checked = false;
            }
        }
        // If user clicked "Spouses"
        if (field === "include_spouses") {
            // If function triggered by checking "All relatives" field, ensure "Siblings" is checked
            if (!document.getElementById("include_siblings").checked) {
                document.getElementById("include_all_relatives").checked = false;
            }
            // If "Spouses" unchecked, uncheck "Anyone"
            if (!document.getElementById("include_spouses").checked) {
                document.getElementById("include_all").checked = false;
            }
        }
        // If function triggered by checking "All relatives" field, ensure everything else is checked
        if (field === "include_all") {
            if (document.getElementById("include_all").checked) {
                document.getElementById("include_all_relatives").checked = true;
                document.getElementById("include_siblings").checked = true;
                document.getElementById("include_spouses").checked = true;
            }
        }
    },

    /**
     * Gets position of element relative to another
     * From https://stackoverflow.com/questions/1769584/get-position-of-element-by-javascript
     *
     * @param el
     * @param rel
     * @returns {{x: number, y: number}}
     */
    getPos(el, rel)
    {
        let x = 0, y = 0;

        do {
            x += el.offsetLeft;
            y += el.offsetTop;
            el = el.offsetParent;
        }
        while (el !== rel)
        return {x:x, y:y};
    },

    clearSelect(selectId) {
        let dropdown = document.getElementById(selectId);
        if (typeof dropdown.tomselect !== 'undefined') {
            dropdown.tomselect.clear();
        } else {
            setTimeout(function () {
                Form.clearSelect(selectId);
            }, 100);
        }
    },

    /**
     * Toggle items based on if the items in the cart should be used or not
     * enable - if set to true, use cart. Update form to disable options. Set to "false" to reverse.
     *
     * @param enable
     */
    toggleCart(enable) {
            const el = document.getElementsByClassName("cart_toggle");
            for (let i = 0; i < el.length; i++) {
                el.item(i).disabled = enable;
            }
            Form.showHideClass("cart_toggle_hide", !enable);
            Form.showHideClass("cart_toggle_show", enable);
        },

    /**
     * This function is used in Form.toggleCart to show or hide all elements with a certain class,
     * by adding or removing "display: none"
     *
     * @param css_class the class to search for
     * @param show true to show the elements and false to hide them
     */
    showHideClass(css_class, show) {
        let el = document.getElementsByClassName(css_class);
        for (let i = 0; i < el.length; i++) {
            Form.showHide(el.item(i), show)
        }
    },
    /**
     * Show or hide an element on the page
     *
     * @param element
     * @param show whether to show (true) or hide (false) the element
     */
    showHide(element, show) {
        if (show) {
            element.style.removeProperty("display");
        } else {
            element.style.display = "none";
        }
    },

    /**
     * Show or hide an element based on whether a checkbox is checked
     *
     * @param checkboxId
     * @param elementId
     */
    showHideMatchCheckbox(checkboxId, elementId) {
        Form.showHide(document.getElementById(elementId), document.getElementById(checkboxId).checked);
    },

    /**
     * Show or hide an element based on whether a select field is a certain value
     *
     * @param dropdownId
     * @param elementId element to show/hide
     * @param value
     */
    showHideMatchDropdown(dropdownId, elementId, value) {
        let values = value.split("|");
        let show = false;
        let elValue = document.getElementById(dropdownId).value;
        values.forEach((value) => {
            if (value === elValue) {
                show = true;
            }
        });
        Form.showHide(document.getElementById(elementId),  show);
    },

    /**
     * Show or hide a settings group based on toggle arrow
     *
     * @param elementId
     * @param callingEl
     */
    showHideSubgroup(elementId, callingEl) {
        let callerText = callingEl.innerText;
        let visible = callerText.includes('↓');
        Form.showHide(document.getElementById(elementId), !visible);
        if (visible) {
            callingEl.innerText = callerText.replace('↓', '→');
        } else {
            callingEl.innerText = callerText.replace('→', '↓');
        }

    },

    /**
     * Shows or hides the diagram search box
     *
     * @param event
     * @param visible (optional) whether to show (true) or hide, leave blank to toggle
     */
    showHideSearchBox(event, visible = null) {
        const el = document.getElementById('diagram_search_box_container');
        // If toggling, set to the opposite of current state
        if (visible === null) {
            visible = el.style.display === "none";
        }
        Form.showHide(el, visible);
        if (visible) {
            // Remove blank section from search box
            tidyTomSelect();
            // Give search box focus
            let dropdown = document.getElementById('diagram_search_box');
            if (typeof dropdown.tomselect !== 'undefined') {
                dropdown.tomselect.focus();
            }
        }
    },

    /**
     * Toggle the showing of an advanced settings section
     *
     * @param button the button element calling the script
     * @param id the id of the element we are toggling
     * @param visible whether to make element visible or hidden. Null to toggle current state.
     */
    toggleAdvanced(button, id, visible = null) {
        const el = document.getElementById(id);
        // If toggling, set to the opposite of current state
        if (visible === null) {
            visible = el.style.display === "none";
        }
        Form.showHide(el, visible);
        if (visible) {
            button.innerHTML = button.innerHTML.replaceAll('↓','↑');
            const hidden = document.getElementById(id+"-hidden");
            hidden.value = "show";
        } else {
            button.innerHTML = button.innerHTML.replaceAll('↑','↓');
            // Update our hidden field for saving the state
            const hidden = document.getElementById(id+"-hidden");
            hidden.value = "";
        }
    },

    /**
     * Shared note panel - form displaying options that allow styling individuals based on their linked shared notes
     */
    sharedNotePanel: {

        /**
         * Run startup code
         */
        init() {
            document.getElementById('shared_note_button').addEventListener('click', Form.sharedNotePanel.clickSharedNoteButton);
            document.getElementById('sharednote_col_add').addEventListener('change', Form.sharedNotePanel.noteSelectChanged);
            document.getElementById('sharednote_col_default').addEventListener('change', Form.sharedNotePanel.defaultChanged);
        },

        /**
         * Handle event when button to show shared note panel is clicked
         */
        clickSharedNoteButton() {
            Form.sharedNotePanel.showNoteModal();
        },

        /**
         * Runs when user changes shared note selection box (i.e. chooses shared note to add)
         */
        noteSelectChanged() {
            let xref = document.getElementById('sharednote_col_add').value.trim();
            if (xref !== "") {
                Form.sharedNotePanel.addNoteToList(xref);
            }
        },

        /**
         * Adds the provided note XREF to the list of shared notes
         *
         * @param xref
         */
        addNoteToList(xref) {
            const obj = Form.sharedNotePanel.getNoteListJSON();
            const xrefTrim = xref.replaceAll('@','');
            if (!obj.find(item => item.xref === xrefTrim)) {
                const newNote = {
                    xref: xrefTrim,
                    bg_col: shared_note_default
                };
                obj.push(newNote);
            }
            document.getElementById('sharednote_col_data').value = JSON.stringify(obj);
            Form.clearSelect('sharednote_col_add');
            Form.sharedNotePanel.showNoteModal();
        },

        /**
         * Displays the modal for managing shared note settings
         *
         * @returns {Promise<*>}
         */
        showNoteModal() {
            return Data.getSharedNoteForm().then(function (response) {
                if (response) {
                    showModal(Data.decodeHTML(response));
                    const items = document.querySelectorAll('#shared_note_list .sharednote-list-item');
                    [].forEach.call(items, UI.draggableList.addDragHandlers);
                    // save to update count in case user backs out without saving as note is already added
                    Form.sharedNotePanel.saveSharedNotesData(false);
                } else {
                    setTimeout(function(){location.reload();}, 3000);
                    UI.showToast(ERROR_CHAR + TRANSLATE['Login expired. Reloading page...']);
                }
            });
        },

        /**
         * Returns the JSON object stored for the shared note list
         *
         * @returns {any}
         */
        getNoteListJSON() {
            let list = document.getElementById('sharednote_col_data');
            return JSON.parse(list.value || '[]');
        },

        /**
         * Triggered when user clicks on the save button of the shared notes modal
         */
        saveButtonClick() {
            Form.sharedNotePanel.saveSharedNotesData(true);
            document.getElementById('modal').remove();
        },

        /**
         * Saves the data from the shared notes modal into JSON and stored in hidden field
         *
         * @param update {boolean} Whether to update the diagram (only if auto-update enabled)
         */
        saveSharedNotesData(update) {
            const listItems = document.querySelectorAll('.sharednote-list-item');
            const outputJSON = [];
            listItems.forEach(item => {
                const picker = item.querySelector('.picker');
                // Only add items that have settings in them - not the blank end one
                if (picker !== null) {
                    const xref = item.getAttribute('data-xref');
                    const bgColour = item.querySelector('.picker').value;
                    const itemObject = {
                        "xref": xref,
                        "bg_col": bgColour
                    };
                    outputJSON.push(itemObject);
                }
            });
            document.getElementById('sharednote_col_data').value = JSON.stringify(outputJSON);

            // Update count on form
            const el = document.getElementById('sharednote-count');
            let itemCount = outputJSON.length;
            if (itemCount === 0) {
                el.innerHTML = TRANSLATE['No styles set based on shared notes.'];
            } else {
                el.innerHTML = TRANSLATE['%s shared note styles saved.'].replace('%s', itemCount);
            }
            // If auto-update of diagram is enabled, trigger it
            if (autoUpdate && update) updateRender();
        },
        defaultChanged() {
            shared_note_default = this.value;
        }
    },

    /**
     * List of individuals for building diagram
     */
    indiList: {
        /**
         * Clear the list of starting individuals
         *
         * @param update Whether to update diagram (only if auto-update enables)
         */
        clearIndiList(update = true) {
            document.getElementById('xref_list').value = "";
            document.getElementById('indi_list').innerHTML = "";
            updateClearAll();
            if (autoUpdate && update) updateRender();
        },

        /**
         * Add an individual to the list of starting individuals
         *
         * @param xref ID of the individual to add
         */
        addIndiToList(xref) {
            let list = document.getElementById('xref_list');
            const regex = new RegExp(`(?<=,|^)(${xref})(?=,|$)`);
            if (!regex.test(list.value.replaceAll(" ','"))) {
                appendXrefToList(xref, 'xref_list');
                Form.indiList.loadIndividualDetails(TOMSELECT_URL, xref, 'indi_list').then(() => {
                })
            }
            Form.clearSelect('pid');
        },

        /**
         * Triggered when the selection box for selecting a starting individual is changed
         */
        indiSelectChanged() {
            let xref = document.getElementById('pid').value.trim();
            if (xref !== "") {
                Form.indiList.addIndiToList(xref);
                mainPage.Url.changeURLXref(xref);
                if (autoUpdate) {
                    updateRender();
                }
            }
        },

        /**
         * Updates the list of starting individuals to add the details of the individual
         *
         * @param url The webtrees URL that runs the Tom-select search that we use to pull the details
         * @param xref The webtrees ID of the individual
         * @param list 'indi_list' if it's the starting individual list, otherwise it updates the stopping individual's list
         * @param colour For some, we want to have a colour select box included. This is the colour for the box.
         * @returns {Promise<void>}
         */
        loadIndividualDetails(url, xref, list, colour = '') {
            return fetch(url + xref.trim()).then(async (response) => {
                const data = await response.json();
                let contents;
                let otherXrefId;
                if (list === "indi_list") {
                    otherXrefId = "xref_list";
                } else if (list === "stop_indi_list") {
                    otherXrefId = "stop_xref_list";
                } else {
                    otherXrefId = "highlight_custom_json";
                }
                if (data["data"].length !== 0) {
                    for (let i = 0; i < data['data'].length; i++) {
                        if (xref.toUpperCase() === data['data'][i].value.toUpperCase()) {
                            contents = data["data"][i]["text"];
                            // Fix case if mismatched
                            if (xref !== data['data'][i].value) {
                                let listEl = document.getElementById(otherXrefId);
                                let indiList = listEl.value.split(',');
                                for (let j = indiList.length - 1; j >= 0; j--) {
                                    if (indiList[j].trim() === xref.trim()) {
                                        indiList[j] = data["data"][i].value;
                                        break;
                                    }
                                }
                                listEl.value = indiList.join(',');
                                setTimeout(() => {
                                    refreshIndisFromXREFS(false)
                                }, 100);
                                Form.handleFormChange();
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
                newListItem.setAttribute("onclick", "UI.scrollToRecord('" + xref + "', 'indi')");
                const listItemIndi = document.createElement("span");
                listItemIndi.innerHTML = contents + "<div class=\"saved-settings-ellipsis\" onclick=\"Form.indiList.removeItem(event, this.parentElement.parentElement" + ", '" + otherXrefId + "')\"><a class='pointer'>×</a></div>";
                newListItem.appendChild(listItemIndi);
                if (colour !== '') {
                    listItemIndi.setAttribute('class', 'list-item-highlight');
                    let picker = `<input type="color" class="highlight_picker" data-xref="${xref}" value="${colour}">`;
                    newListItem.innerHTML = newListItem.innerHTML + picker;
                    newListItem.querySelector('.highlight_picker')?.addEventListener('change', Form.indiList.updateHighlightColour);
                } else {
                    listItemIndi.setAttribute('class', 'list_item_content');
                }
                // Multiple promises can be for the same xref - don't add if a duplicate
                let item = listElement.querySelector(`[data-xref="${xref}"]`);
                if (item == null) {
                    listElement.appendChild(newListItem);
                } else {
                    newListItem.remove();
                }
                updateClearAll();
            })
        },

        /**
         * Removes an item from a list of individuals - triggered by clicking X
         *
         * @param {Event} e
         * @param {HTMLElement} element
         * @param {string} xrefListId
         */
        removeItem(e, element, xrefListId) {
            e.stopPropagation();
            let xref = element.getAttribute("data-xref").trim();
            let list = document.getElementById(xrefListId);
            removeFromXrefList(xref, xrefListId);
            element.remove();
            if (xrefListId === 'xref_list') {
                mainPage.Url.changeURLXref(list.value.split(',')[0].trim());
            }
            updateClearAll();
            if (xrefListId === 'highlight_custom_json' || xrefListId === 'highlight_custom_fams_json') {
                let data = JSON.parse(list.value);
                delete data[xref];
                list.value = JSON.stringify(data);
            }
            if (autoUpdate) {
                updateRender();
            }
        },

        /** Triggered by change of highlight colour picker, updates the JSON that stores the colour
         * 
         * @param {Event} e
         */
        updateHighlightColour(e) {
            let xref = e.target.getAttribute('data-xref');
            let newColour = e.target.value;
            let list = document.getElementById('highlight_custom_json');
            let data = JSON.parse(list.value);
            data[xref] = newColour;
            list.value = JSON.stringify(data);

            if (autoUpdate) {
                updateRender();
            }
        },

        /**
         * Load a list of indis into element indiListId using JSON data
         *
         * @param {string} jsonId
         * @param {string} indiListId
         */
        refreshIndisFromJson(jsonId, indiListId) {
            let jsonEl = document.getElementById(jsonId);
            let listEl = document.getElementById(indiListId);
            document.getElementById(indiListId).innerHTML = "";
            if (jsonEl.value === '' || jsonEl.value === '[]') jsonEl.value = '{}';
            try {
                let data = JSON.parse(jsonEl.value);
                for (let key in data) {
                    Form.indiList.loadIndividualDetails(TOMSELECT_URL, key, indiListId, data[key]);
                }
            } catch (error) {
                UI.showToast(ERROR_CHAR + error);
            }
        },

        /**
         * Triggered by person select being changed for highlight indis
         */
        highlightIndiSelectChanged() {
            let xref = document.getElementById('highlight_pid').value.trim();
            if (xref !== "") {
                let colour = document.getElementById('highlight_custom_col').value;
                UI.tile.addIndiToCustomHighlightList(xref, colour);
                Form.clearSelect('highlight_pid');
            }
            if (autoUpdate) {
                updateRender();
            }
        },
    },

    /**
     * List of stopping individuals for pruning diagram
     */
    stoppingIndiList: {

        /**
         * Adds the individual to the list of stopping individuals
         *
         * @param xref The xref of the individual
         */
        addIndiToStopList(xref) {
            let list = document.getElementById('stop_xref_list');
            const regex = new RegExp(`(?<=,|^)(${xref})(?=,|$)`);
            if (!regex.test(list.value.replaceAll(" ','"))) {
                appendXrefToList(xref, 'stop_xref_list');
                Form.indiList.loadIndividualDetails(TOMSELECT_URL, xref, 'stop_indi_list').then(r => {});
            }
            Form.clearSelect('stop_pid');
        },

        /**
         * Clears the list of stopping individuals.
         *
         * @param update Whether it's ok to update the diagram afterward. Even if true, the diagram will only be updated if autoUpdate is enabled.
         */
        clearStopIndiList(update = true) {
            document.getElementById('stop_xref_list').value = "";
            document.getElementById('stop_indi_list').innerHTML = "";
            updateClearAll();
            if (autoUpdate && update) updateRender();
        }
    },

    /**
     * Settings related to lists of families
     */

    famList: {

        /**
         * Triggered by person select being changed for highlighted families
         */
        highlightFamSelectChanged() {
            let xref = document.getElementById('highlight_fid').value.trim();
            if (xref !== "") {
                let colour = document.getElementById('highlight_custom_fams_col').value;
                UI.tile.addFamToCustomHighlightList(xref, colour);
                Form.clearSelect('highlight_fid');
            }
            if (autoUpdate) {
                updateRender();
            }
        },


        /**
         * Updates the list of families to add the details of the family
         *
         * @param url The webtrees URL that runs the Tom-select search that we use to pull the details
         * @param xref The webtrees ID of the family
         * @param list  the ID of the list element that we are updating
         * @param colour For some, we want to have a colour select box included. This is the colour for the box.
         * @returns {Promise<void>}
         */
        loadFamilyDetails(url, xref, list, colour = '') {
            return fetch(url + xref.trim()).then(async (response) => {
                const data = await response.json();

                let contents;
                let otherXrefId = 'highlight_custom_fams_json';

                if (data["data"].length !== 0) {
                    for (let i = 0; i < data['data'].length; i++) {
                        if (xref.toUpperCase() === data['data'][i].value.toUpperCase()) {
                            contents = data["data"][i]["text"];
                            // Fix case if mismatched
                            if (xref !== data['data'][i].value) {
                                let listEl = document.getElementById(otherXrefId);
                                let famList = listEl.value.split(',');
                                for (let j = famList.length - 1; j >= 0; j--) {
                                    if (famList[j].trim() === xref.trim()) {
                                        famList[j] = data["data"][i].value;
                                        break;
                                    }
                                }
                                listEl.value = famList.join(',');
                                setTimeout(() => {
                                    Form.indiList.refreshFamsFromJson('highlight_custom_json', 'highlight_list');
                                }, 100);
                                Form.handleFormChange();
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
                newListItem.setAttribute("onclick", "UI.scrollToRecord('" + xref + "', 'fam')");
                const listItem = document.createElement("span");
                listItem.innerHTML = contents + "<div class=\"saved-settings-ellipsis\" onclick=\"Form.indiList.removeItem(event, this.parentElement.parentElement" + ", '" + otherXrefId + "')\"><a class='pointer'>×</a></div>";
                newListItem.appendChild(listItem);
                if (colour !== '') {
                    listItem.setAttribute('class', 'list-item-highlight');
                    let picker = `<input type="color" class="highlight_fams_picker" data-xref="${xref}" value="${colour}">`;
                    newListItem.innerHTML = newListItem.innerHTML + picker;
                    newListItem.querySelector('.highlight_fams_picker')?.addEventListener('change', Form.famList.updateFamsHighlightColour);
                } else {
                    listItemIndi.setAttribute('class', 'list_item_content');
                }
                // Multiple promises can be for the same xref - don't add if a duplicate
                let item = listElement.querySelector(`[data-xref="${xref}"]`);
                if (item == null) {
                    listElement.appendChild(newListItem);
                } else {
                    newListItem.remove();
                }
                updateClearAll();
            })
        },


        /**
         * Load a list of families into element famListId using JSON data
         *
         * @param {string} jsonId
         * @param {string} famListId
         */
        refreshFamsFromJson(jsonId, famListId) {
            let jsonEl = document.getElementById(jsonId);
            let listEl = document.getElementById(famListId);
            document.getElementById(famListId).innerHTML = "";
            if (jsonEl.value === '' || jsonEl.value === '[]') jsonEl.value = '{}';
            try {
                let data = JSON.parse(jsonEl.value);
                for (let key in data) {
                    Form.famList.loadFamilyDetails(TOMSELECT_FAM_URL, key, famListId, data[key]);
                }
            } catch (error) {
                UI.showToast(ERROR_CHAR + error);
            }
        },


        /** Triggered by change of highlight colour picker, updates the JSON that stores the colour
         * 
         * @param {Event} e
         */
        updateFamsHighlightColour(e) {
            let xref = e.target.getAttribute('data-xref');
            let newColour = e.target.value;
            let list = document.getElementById('highlight_custom_fams_json');
            let data = JSON.parse(list.value);
            data[xref] = newColour;
            list.value = JSON.stringify(data);

            if (autoUpdate) {
                updateRender();
            }
        },


    },

    settings: {
        load(data, isNamedSetting = false) {
            let autoUpdatePrior = autoUpdate;
            autoUpdate = false;
            let settings;
            try {
                settings = JSON.parse(data);
            } catch (e) {
                UI.showToast("Failed to load settings: " + e);
                return false;
            }
            if (!settings.hasOwnProperty("sharednote_col_data")) {
                settings["sharednote_col_data"] = "[]";
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
                        case 'divorce_date_year_only':
                            setCheckStatus(document.getElementById('dvd_type_y'), toBool(settings[key]));
                            setCheckStatus(document.getElementById('dvd_type_gedcom'), !toBool(settings[key]));
                            break;
                        case 'burial_date_year_only':
                            setCheckStatus(document.getElementById('bud_type_y'), toBool(settings[key]));
                            setCheckStatus(document.getElementById('bud_type_gedcom'), !toBool(settings[key]));
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
                        // Handle transforming old highlight_custom xrefs into the new custom highlight system
                        case 'highlight_custom':
                            let xrefs = settings[key].split(',');
                            for (let xref of xrefs) {
                                if (xref !== '') {
                                    UI.tile.addIndiToCustomHighlightList(xref);
                                }
                            }
                            break;
                        // Handle transforming highlighted start indis into new custom highlight system
                        case 'highlight_start_indis':
                            Form.settings.migrateHighlightStartIndis(settings);
                            break;
                        // Ignore these as handled by highlight_start_indis
                        case 'highlight_col':
                        case 'no_highlight_xref_list':
                        // If option to use cart is not showing, don't load, but also don't show error
                        case 'use_cart':
                        // These options only exist if debug panel active - don't show error if not found
                        case 'enable_debug_mode':
                        case 'enable_graphviz':
                        // Token is not loaded as an option
                        case 'token':
                        // Date of settings is not a setting so don't load it
                        case 'updated_date':
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
            });
            // Make sure show/hide groups are set right now that we changed the settings
            Form.showHideMatchDropdown('output_type', 'server_pdf_subgroup', 'pdf|svg|jpg')
            Form.showHideMatchCheckbox('mark_not_related', 'mark_related_subgroup');
            Form.showHideMatchCheckbox('show_birthdate', 'birth_date_subgroup');
            Form.showHideMatchCheckbox('show_death_date', 'death_date_subgroup');
            Form.showHideMatchCheckbox('show_marriage_date', 'marriage_date_subgroup');
            Form.showHideMatchCheckbox('show_divorce_date', 'divorce_date_subgroup');
            Form.showHideMatchCheckbox('show_burial_date', 'burial_date_subgroup');
            Form.showHideMatchCheckbox('highlight_custom_indis', 'highlight_custom_option');
            Form.showHideMatchCheckbox('highlight_custom_fams', 'highlight_custom_fams_option');
            Form.showHideMatchCheckbox('show_marriage_type', 'marriage_type_subgroup');
            Form.showHideMatchCheckbox('show_divorces', 'divorces_subgroup');
            Form.showHideMatchCheckbox('show_marriages', 'marriages_subgroup');
            setSavedDiagramsPanel();
            Form.showHide(document.getElementById('arrow_group'),document.getElementById('colour_arrow_related').checked)
            toggleUpdateButton();
            if (autoUpdatePrior) {
                if (firstRender) {
                    firstRender = false;
                } else {
                    updateRender();
                }
                autoUpdate = true;
            }
            refreshIndisFromXREFS(false);
        },

        /**
         * Migrate old setting to highlight start individuals into the new custom highlight function
         * @param settings
         */
        migrateHighlightStartIndis(settings){
            if (settings['highlight_start_indis'] && settings['highlight_col']) {
                let xrefs = settings['xref_list'].split(',');
                let nohighlight = settings['no_highlight_xref_list'].split(',');
                for (let xref of xrefs) {
                    if (xref.trim() !== '' && !nohighlight.includes(xref)) {
                        UI.tile.addIndiToCustomHighlightList(xref, settings['highlight_col']);
                        settings['highlight_custom_indis'] = true;
                    }
                }
            }
        }
    },
}