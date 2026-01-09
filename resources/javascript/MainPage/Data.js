/**
 * Data object to hold data fetching and validation functionality not related to the form
 *
 * @type {{}}
 */
const Data = {

    api: {
        _treename: null,
        _loggedIn: null,

        getTreeName() {
            if (Data.api._treename != null)  {
                return Promise.resolve(Data.api._treename);
            } else {
                let request = {
                    "type": REQUEST_TYPE_GET_TREE_NAME
                };
                let json = JSON.stringify(request);
                return sendRequest(json).then((response) => {
                    try {
                        let json = JSON.parse(response);
                        if (json.success) {
                            Data.api._treename = json.treeName.replace(/[^a-zA-Z0-9_]/g, ""); // Only allow characters that play nice
                            return Data.api._treename;
                        } else {
                            return Promise.reject(ERROR_CHAR + json.errorMessage);
                        }
                    } catch (e) {
                        return Promise.reject("Failed to load response: " + e);
                    }
                });
            }
        },

        isUserLoggedIn() {
            if (Data.api._loggedIn != null)  {
                return Promise.resolve(Data.api._loggedIn);
            } else {
                let request = {
                    "type": REQUEST_TYPE_IS_LOGGED_IN
                };
                let json = JSON.stringify(request);
                return sendRequest(json).then((response) => {
                    try {
                        let json = JSON.parse(response);
                        if (json.success) {
                            Data.api._loggedIn = json.loggedIn;
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
    },
    /**
     * Convert image URL to base64 data - we use for embedding images in SVG
     * From https://stackoverflow.com/questions/22172604/convert-image-from-url-to-base64
     *
     * @param img
     * @returns {string}
     */
    getBase64Image: function(img) {
        const canvas = document.createElement("canvas");
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext("2d");
        ctx.drawImage(img, 0, 0);
        return canvas.toDataURL("image/png");
    },

    /**
     *
     * @param help
     * @returns {Promise<unknown>}
     */
    getHelp(help) {
        let request = {
            "type": REQUEST_TYPE_GET_HELP,
            "help_name": help
        };
        return Data.callAPI(request);
    },

    /**
     * Sends API request to retrieve record count
     *
     * @returns {Promise<unknown>}
     */
    getResourceCount(token) {
        let request = {
            "type": REQUEST_TYPE_GET_RECORD_COUNT,
            "token": token,
        };
        return Data.callAPI(request);
    },

    decodeHTML(html) {
        const textarea = document.createElement('textarea');
        textarea.innerHTML = html;
        return textarea.value;
    },

    /**
     * Retrieved the shared note view
     *
     * @returns {Promise<unknown>}
     */
    getSharedNoteForm() {
        let request = {
            "type": REQUEST_TYPE_GET_SHARED_NOTE_FORM,
        };
        return Data.callAPI(request);
    },

    callAPI(request) {
        let json = JSON.stringify(request);
        return sendRequest(json).then((response) => {
            let responseJson = Data.parseResponse(response);
            if (responseJson) {
                return responseJson['response'];
            } else {
                return false;
            }
        });
    },

    parseResponse(response) {
        try {
            let json = JSON.parse(response);
            if (json.success) {
                return json;
            } else {
                return ERROR_CHAR + json['errorMessage'];
            }
        } catch(e) {
            UI.showToast(ERROR_CHAR + e);
        }
        return false;
    },

    // Return distance between two points
        getDistance(x1, y1, x2, y2){
        let x = x2 - x1;
        let y = y2 - y1;
        return Math.sqrt(x * x + y * y);
    },

    /**
     * Responsible for generating downloads and related activities
     */
    download: {
        /**
         * Remove the <a> tags from the SVG string
         *
         * @param svgString
         * @returns {string}
         */
        removeHrefLinksFromSVG(svgString) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = svgString;
            const aTags = tempDiv.querySelectorAll('a');
            for (let i = 0; i < aTags.length; i++) {
                const parent = aTags[i].parentNode;
                while (aTags[i].firstChild) {
                    parent.insertBefore(aTags[i].firstChild, aTags[i]);
                }
                parent.removeChild(aTags[i]);
            }
            return tempDiv.innerHTML;
        },

        /**
         * Request download of SVG file
         */
        downloadSVGAsText() {
            const svg = document.getElementById('rendering').getElementsByTagName('svg')[0].cloneNode(true);
            svg.removeAttribute("style");
            let svgData = svg.outerHTML.replace(/&nbsp;/g, '');
            // Remove links if link option not enabled
            const el = document.getElementById('add_links');
            if (el && !el.checked) {
                svgData = Data.download.removeHrefLinksFromSVG(svgData);
            }
            // Replace image URLs with embedded data for SVG - also triggers download
            Data.url.replaceImageURLs(svgData, "svg", null);
        },

        /**
         * Request download of PDF file
         */
        downloadSVGAsPDF() {
            Data.download.downloadSVGAsImage("pdf");
        },

        /**
         * Request download of PNG file
         */
        downloadSVGAsPNG() {
            Data.download.downloadSVGAsImage("png");
        },

        /**
         * Request download of JPEG file
         */
        downloadSVGAsJPEG() {
            Data.download.downloadSVGAsImage("jpeg");
        },

        /**
         * Create and trigger download of diagram in the requested image type
         *
         * @param type one of the supported image types
         */
        downloadSVGAsImage(type) {
            let dataURL;
            
            const svg = document.getElementById('rendering').getElementsByTagName('svg')[0].cloneNode(true);
            // Style attribute used for the draggable browser view, remove this to reset to standard SVG
            svg.removeAttribute("style");

            const canvas = document.createElement("canvas");
            const img = document.createElement("img");
            // get svg data and remove line breaks
            let xml = new XMLSerializer().serializeToString(svg);
            // Fix the + symbol (any # breaks everything)
            xml = xml.replace(/&#45;/g, "+");
            // Replace # colours with rgb equivalent
            // From https://stackoverflow.com/questions/13875974/search-and-replace-hexadecimal-color-codes-with-rgb-values-in-a-string
            const rgbHex = /#([0-9A-F][0-9A-F])([0-9A-F][0-9A-F])([0-9A-F][0-9A-F])/gi;
            xml = xml.replace(rgbHex, function (m, r, g, b) {
                return 'rgb(' + parseInt(r, 16) + ','
                    + parseInt(g, 16) + ','
                    + parseInt(b, 16) + ')';
            });
            // Replace image URLs with embedded images
            Data.url.replaceImageURLs(xml, type, img);
            // Once image loaded, draw to canvas then download it
            img.onload = function () {
                canvas.setAttribute('width', img.width.toString());
                canvas.setAttribute('height', img.height.toString());

                try {
                // draw the image onto the canvas
                let context = canvas.getContext('2d');
                context.drawImage(img, 0, 0, img.width, img.height);
                
                dataURL = canvas.toDataURL('image/' + type);
                } catch (e) {
                    if (e instanceof DOMException) {
                        UI.showToast(ERROR_CHAR + TRANSLATE['Your browser does not support exporting images this large. Please reduce number of records, reduce DPI setting, or use SVG option.']);
                        return;
                    }
                    throw e;
                }

                // Download it
                if (type === "pdf") {
                    if (img.width.toString() > 14400 || img.height.toString() > 14400) {
                        UI.showToast(ERROR_CHAR + TRANSLATE['Diagram is larger than the PDF page size limit. Please reduce number of records, reduce DPI setting, or use another export option.']);
                    } else {
                        Data.download.createPdfFromImage(dataURL, img.width, img.height);
                    }
                } else {
                    let name = download_file_name + "." + (type === 'jpeg' ? 'jpg' : type);
                    Data.download.downloadLink(dataURL, name);
                }
            }
        },

        /**
         * Create and download a PDF version of the provided image data
         *
         * @param imgData
         * @param width
         * @param height
         */
        createPdfFromImage(imgData, width, height) {
            const orientation = width >= height ? 'landscape' : 'portrait';
            const dpi = document.getElementById('dpi').value;
            const widthInches = width / dpi;
            const heightInches = height / dpi;
            const doc = new window.jspdf.jsPDF({orientation: orientation, format: [widthInches, heightInches], unit: 'in', compress: true});
            doc.addImage(imgData, "PNG", 0, 0, widthInches, heightInches);
            doc.save(download_file_name + ".pdf");
        },

        /**
         * Trigger a download via javascript
         *
         * @param URL
         * @param filename
         */
        downloadLink(URL, filename) {
            const downloadLinkElement = document.createElement("a");
            downloadLinkElement.href = URL;
            downloadLinkElement.download = filename;
            document.body.appendChild(downloadLinkElement);
            downloadLinkElement.click();
            document.body.removeChild(downloadLinkElement);
        },
    },

    /**
     * Section of form that handles saving and loading settings from a list
     */
    savedSettings: {
        /**
         * Updates a setting in the saved settings list to have a new name
         *
         * @param id
         * @param userPrompted
         * @returns {boolean}
         */
        renameSetting(id, userPrompted = false) {
            let name = "";
            if (userPrompted) {
                name = document.getElementById('rename_text').value;
                document.getElementById('modal').remove();
                if (name === '') return false;
            } else {
                let originalName = document.querySelector('[data-id="' + id + '"]').getAttribute('data-name');
                let message = TRANSLATE["Enter new setting name"] + ': <input type="text" onfocus="this.selectionStart = this.selectionEnd = this.value.length;" id="rename_text" value="' + originalName + '" autofocus="autofocus">';
                let buttons = '<div class="modal-button-container"><button class="btn btn-secondary modal-button" onclick="document.getElementById(' + "'modal'" + ').remove()">' + TRANSLATE['Cancel'] + '</button><button class="btn btn-primary modal-button" onclick="Data.savedSettings.renameSetting(\'' + id + '\', true)">' + TRANSLATE['Rename'] + '</button></div>';
                showModal('<div class="modal-container">' + message + '<br>' + buttons + '</div>');
                return false;
            }
            Data.api.isUserLoggedIn().then((loggedIn) => {
                if (loggedIn) {
                    let request = {
                        "type": REQUEST_TYPE_RENAME_SETTINGS,
                        "settings_id": id,
                        "name": name
                    };
                    let json = JSON.stringify(request);
                    sendRequest(json).then((response) => {
                        try {
                            let json = JSON.parse(response);
                            if (json.success) {
                                loadSettingsDetails();
                                UI.showToast(TRANSLATE['Update successful']);
                            } else {
                                UI.showToast(ERROR_CHAR + json['errorMessage']);
                            }
                        } catch (e) {
                            UI.showToast("Failed to load response: " + e);
                            return false;
                        }
                    });
                } else {
                    // Logged out so save in browser
                    let settings_field = document.getElementById('save_settings_name');
                    let settings_text = settings_field.value;
                    settings_field.value = name;
                    Data.storeSettings.saveSettingsClient(id).then(() => {
                        settings_field.value = settings_text;
                        loadSettingsDetails();
                    }).catch(error => UI.showToast(error));
                }
            });
        },

        /**
         * Delete a saved settings item saved on the server
         *
         * @param id
         * @returns {Promise<void>}
         */
        async deleteSettingsServer(id) {
            const request = {
                "type": REQUEST_TYPE_DELETE_SETTINGS,
                "settings_id": id
            };
            const json = JSON.stringify(request);
            const response = await sendRequest(json);
            const parsedResponse = JSON.parse(response);

            if (!parsedResponse.success) {
                throw new Error(parsedResponse['errorMessage']);
            }
        },

        /**
         * Delete a saved settings item saved on the server
         *
         * @param id
         */
        deleteSettingsClient(id) {
            Data.api.getTreeName().then((treeName) => {
                try {
                    localStorage.removeItem("GVE_Settings_" + treeName + "_" + id);
                    deleteIdLocal(id);
                } catch (e) {
                    UI.showToast(e);
                }
            });
        },

        /**
         * Retrieve a link for sharing the settings saved in this settings record
         *
         * @param id
         * @returns {Promise<unknown>}
         */
        getSavedSettingsLink(id) {
            return Data.api.isUserLoggedIn().then((loggedIn) => {
                if (loggedIn) {
                    let request = {
                        "type": REQUEST_TYPE_GET_SAVED_SETTINGS_LINK,
                        "settings_id": id
                    };
                    let json = JSON.stringify(request);
                    return sendRequest(json).then((response) => {
                        loadSettingsDetails();
                        try {
                            let json = JSON.parse(response);
                            if (json.success) {
                                return json.url;
                            } else {
                                UI.showToast(ERROR_CHAR + json['errorMessage']);
                            }
                        } catch (e) {
                            UI.showToast("Failed to load response: " + e);
                            return false;
                        }
                    });
                }
            });
        },

        /**
         * Get the selected sort order from the UI
         *
         * @returns {*}
         */
        getSortOrder() {
            const selectElement = document.getElementById("settings_sort_order");
            return selectElement.value;
        },

        /**
         * Take settings object and return sorted array - based on sort order set in UI
         *
         * @param settings The settings object that holds all saved settings entries
         * @returns {{}|unknown[]}
         */
        sortSettings(settings) {
            switch (Data.savedSettings.getSortOrder()) {
                case '0':
                default:
                    return Data.savedSettings.sortSettingsByUpdatedDate(settings, false);
                case '10':
                    return Data.savedSettings.sortSettingsByUpdatedDate(settings, true);
                case '20':
                    return Data.savedSettings.sortSettingsByName(settings, false);
                case '30':
                    return Data.savedSettings.sortSettingsByName(settings, true);
            }
        },

        /**
         * Sorts saved settings list alphabetically
         *
         * @param settings Saved settings JSON object
         * @param reverse If true, settings will be sorted Z-A instead of A-Z
         * @returns {[]} Sorted saved settings *array* (as JSON objects don't guarantee order)
         */
        sortSettingsByName(settings, reverse = false) {
            return Object.values(settings).sort((a, b) => {
                if (reverse) {
                    return b.name.localeCompare(a.name);
                } else {
                    return a.name.localeCompare(b.name);
                }
            });
        },

        /**
         * Sorts saved settings list by date last updated
         *
         * @param settings Saved settings JSON object
         * @param reverse If true, settings will be newest to oldest instead of oldest to newest
         * @returns {[]} Sorted saved settings *array* (as JSON objects don't guarantee order)
         */
        sortSettingsByUpdatedDate(settings, reverse = false) {
            return Object.values(settings).sort((a, b) => {
                // Settings saved before this was added won't have a date, so give them a default value
                const dateA = a.updated_date || '';
                const dateB = b.updated_date || '';

                if (reverse) {
                    return dateB.localeCompare(dateA);
                } else {
                    return dateA.localeCompare(dateB);
                }
            });
        },
    },

    /**
     * Handles storing data to browser storage
     */
    storeSettings: {
        /**
         * Save settings for user
         *
         * @param id
         */
        saveSettings(id) {
            Data.api.isUserLoggedIn().then((loggedIn) => {
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
                            return Data.storeSettings.saveSettingsClient(newId);
                        });
                    } else {
                        return Data.storeSettings.saveSettingsClient(id);
                    }
                }
            }).then(() => {
                loadSettingsDetails();
                document.getElementById('save_settings_name').value = "";
            }).catch(
                error => UI.showToast(error)
            );
        },

        /**
         * Save settings to browser storage
         *
         * @param id
         * @returns {Promise<void>}
         */
        saveSettingsClient(id) {
            return Promise.all([saveSettingsServer(true), Data.api.getTreeName()])
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
        },

        /**
         * Triggered when user clicks save settings button in advanced section
         * @param userPrompted whether the user has been asked to overwrite settings
         * @returns {boolean}
         */
        saveSettingsAdvanced(userPrompted = false) {
            let saveButton = document.getElementById('save_settings_button');
            saveButton.disabled = true;
            setTimeout(() => {saveButton.disabled=false}, 500);

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
                    Data.storeSettings.saveSettings(id);
                } else {
                    let message = TRANSLATE["Overwrite settings '%s'?"].replace('%s', settingsName);
                    let buttons = '<div class="modal-button-container"><button class="btn btn-secondary modal-button" onclick="document.getElementById(' + "'modal'" + ').remove()">' + TRANSLATE['Cancel'] + '</button><button class="btn btn-primary modal-button" onclick="Data.storeSettings.saveSettingsAdvanced(true)">' + TRANSLATE['Overwrite'] + '</button></div>';
                    showModal('<div class="modal-container">' + message + '<br>' + buttons + '</div>');
                    return false;
                }
            } else {
                Data.storeSettings.saveSettings(id);
            }

        },

        /**
         * Retrieve settings from browser storage
         *
         * @param id
         * @returns {Promise<{} | {} | any | undefined | void>}
         */
        getSettingsClient(id = ID_ALL_SETTINGS) {
            return Data.api.getTreeName().then(async (treeName) => {
                try {
                    if (id === ID_ALL_SETTINGS) {
                        let settings_list = localStorage.getItem(SETTINGS_ID_LIST_NAME + "_" + treeName);
                        if (settings_list) {
                            let ids = settings_list.split(',');
                            let promises = ids.map(id_value => Data.storeSettings.getSettingsClient(id_value))
                            let results = await Promise.all(promises);
                            let settings = {};
                            for (let i = 0; i < ids.length; i++) {
                                let id_value = ids[i];
                                let userSettings = results[i];
                                if (userSettings !== null) {
                                    settings[id_value] = {};
                                    settings[id_value]['name'] = userSettings['save_settings_name'];
                                    settings[id_value]['updated_date'] = userSettings['updated_date'];
                                    settings[id_value]['id'] = id_value;
                                    settings[id_value]['settings'] = JSON.stringify(userSettings);
                                }
                            }
                            return settings;
                        } else {
                            return {};
                        }
                    } else {
                        try {
                            return JSON.parse(localStorage.getItem("GVE_Settings_" + treeName + "_" + id));
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
    },

    /**
     * Get current count of items in webtrees clippings cart
     */
    getClippingsCartCount() {
            let request = {
            "type": REQUEST_TYPE_COUNT_XREFS_CLIPPINGS_CART,
        };
        return Data.callAPI(request);
    },
    

    /**
     * Returns a map of counts of the items in the array
     * 
     * @param Array xrefs 
     * @returns 
     */
    countItems(array) {
        const counts = new Map();

        for (const item of array) {
            counts.set(item, (counts.get(item) || 0) + 1);
        }
        return counts;
    },

    diagram: {
        getXrefs() {
            const rendering = document.getElementById('rendering');
            const svg = rendering.getElementsByTagName('svg')[0].cloneNode(true);
            if (!svg) return [];
            const xrefs = [];
            svg.querySelectorAll('g.node').forEach(nodeEl => {
                // Only include the indi once per node, but can occur multiple times in different nodes
                let nodeXrefs = [];
                nodeEl.querySelectorAll('a').forEach(anchorEl => {
                    let url = anchorEl.getAttribute('xlink:href');
                    xref = Data.url.getXrefFromUrl(url);
                    if (!nodeXrefs.includes(xref)) {
                        xrefs.push(xref);
                        nodeXrefs.push(xref);
                    }
                    
                });
            });
            return xrefs;
        },
    },

    url:  Object.freeze({
        ACTION_GET: 0,
        ACTION_REMOVE: 10,
        ACTION_UPDATE: 20,


        /**
         * Fixes URL so regular expression doesn't get confused
         *
         * @param url
         * @returns {string}
         */
        cleanUrl(url){
            if (url) {
                return url.replaceAll('%2F', '/');
            } else {
                return '';
            }
        },


        /**
         * Takes a webtrees individual's URL as input, and returns their XREF
         *
         * @param url
         * @returns {*}
         */
        getXrefFromUrl(url) {
            url = this.cleanUrl(url);
            const regex = /\/tree\/[^/]+\/(individual|family)\/([^/]+)\/?/;
            if (!url.match(regex)) {
                return '';
            } else {
                return url.match(regex)[2];
            }
        },

        /**
         * Find image URLs and replace with embedded versions
         *
         * @param svg
         * @param type
         * @param img
         */
        replaceImageURLs: function(svg, type, img) {
            let startPos, len, url;
            let match = /<image.*xlink:href="http/.exec(svg);
            if (match != null) {
                startPos = match.index+match[0].length-4;
                len = svg.substring(startPos).indexOf("\"");
                url = svg.substring(startPos,startPos+len);
                const img2 = document.createElement("img");
                img2.onload = function() {
                    let base64 = Data.getBase64Image(img2);
                    svg = svg.replace(url,base64);
                    Data.url.replaceImageURLs(svg, type, img);
                    img2.remove();
                }
                img2.src = url.replace(/&amp;/g,"&");
            } else {
                if (type === "svg") {
                    const svgBlob = new Blob([svg], {type: "image/svg+xml;charset=utf-8"});
                    const svgUrl = URL.createObjectURL(svgBlob);
                    Data.download.downloadLink(svgUrl, download_file_name + "."+type);
                } else {
                    img.src = "data:image/svg+xml;utf8," + svg;
                }
            }
        },
        
        /**
         * Removes parameter from the URL
         *
         * @param {string} parameter
         */
        removeURLParameter(parameter) {
            this.updateURLParameter(parameter, "", this.ACTION_REMOVE);
        },



        /**
         * Updates the XREF in the URL
         *
         * @param {string} xref
         */
        changeURLXref(xref) {
            if (xref !== "") {
                this.updateURLParameter("xref", xref, this.ACTION_UPDATE);
            }
        },

        /**
         * Updates a specified parameter in the URL using the specified action
         *
         * @param {string} parameter
         * @param {string} value
         * @param {number} action
         * @returns {string}
         */
        updateURLParameter(parameter, value, action) {
            let href = document.location.href;
            if (href.indexOf('#') !== -1) {
                href = href.split('#')[0];
            }
            let split = href.split("?");
            let url = split[0];
            if (split.length > 1) {
                let args = split[1];
                let params = new URLSearchParams(args);
                if (params.toString().search(parameter) !== -1) {
                    if (action === this.ACTION_REMOVE) {
                        params.delete(parameter);
                    } else if (action === this.ACTION_UPDATE) {
                        params.set(parameter, value);
                    } else if (action === this.ACTION_GET) {
                        return params.get(parameter);
                    }
                }
                history.pushState(null, '', url + "?" + params.toString());
            } else if (action === this.ACTION_UPDATE) {
                history.pushState(null, '', url + "?" +  parameter + "=" + value);
            }
            return "";
        },

        /**
         * Returns the value of the URL parameter with the given name
         *
         * @param parameter
         * @returns {string}
         */
        getURLParameter(parameter) {
            let result = this.updateURLParameter(parameter, '', this.ACTION_GET);
            if (result !== null && result !== '') {
                return result.replace('#','');
            } else {
                return '';
            }
        },

        loadURLXref() {
            const xref = Data.url.getURLParameter("xref");
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
                        Form.indiList.addIndiToList(xref);
                        if (url_xref_treatment === 'default' && xrefs.length === 1 ) {
                            setTimeout(function () {UI.showToast(TRANSLATE['Source individual has replaced existing individual'])}, 100);
                        } else if (startValue !== el.value && (url_xref_treatment === 'default' || url_xref_treatment === 'add')) {
                            setTimeout(function () {UI.showToast(TRANSLATE['One new source individual added to %s existing individuals'].replace('%s', xrefs.length.toString()))}, 100);
                        }
                    }
                }
            }
        }
    })
}