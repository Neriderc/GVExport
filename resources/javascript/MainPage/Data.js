/**
 * Data object to hold data fetching and validation functionality not related to the form
 *
 * @type {{}}
 */
const Data = {
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
                Data.replaceImageURLs(svg, type, img);
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
            Data.replaceImageURLs(svgData, "svg", null);
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
            Data.replaceImageURLs(xml, type, img);
            // Once image loaded, draw to canvas then download it
            img.onload = function () {
                canvas.setAttribute('width', img.width.toString());
                canvas.setAttribute('height', img.height.toString());
                // draw the image onto the canvas
                let context = canvas.getContext('2d');
                context.drawImage(img, 0, 0, img.width, img.height);
                // Download it
                const dataURL = canvas.toDataURL('image/' + type);
                if (dataURL.length < 10) {
                    UI.showToast(ERROR_CHAR + TRANSLATE['Your browser does not support exporting images this large. Please reduce number of records, reduce DPI setting, or use SVG option.']);
                } else if (type === "pdf") {
                    Data.download.createPdfFromImage(dataURL, img.width, img.height);
                } else {
                    Data.download.downloadLink(dataURL, download_file_name + "." + type);
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
            // If running test suite, don't actually trigger download of data
            // We have generated it so know it works
            if (!window.Cypress) {
                doc.save(download_file_name + ".pdf");
            }
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
            // If running test suite, don't actually trigger download of data
            // We have generated it so know it works
            if (!window.Cypress) {
                downloadLinkElement.click();
            }
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
            isUserLoggedIn().then((loggedIn) => {
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
            getTreeName().then((treeName) => {
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
            return isUserLoggedIn().then((loggedIn) => {
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
        },

        /**
         * Triggered when user clicks save settings button in advanced section
         * @param userPrompted whether the user has been asked to overwrite settings
         * @returns {boolean}
         */
        saveSettingsAdvanced(userPrompted = false) {
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
            return getTreeName().then(async (treeName) => {
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
    }
}