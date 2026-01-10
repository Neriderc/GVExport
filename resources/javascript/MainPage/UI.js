/**
 * UI object to hold UI functionality not related to the form
 *
 * @type {{}}
 */
const UI = {

    /**
     * Hides the settings panel
     */
    hideSidebar: function() {
        document.querySelector(".sidebar").hidden = true;
        document.querySelector(".sidebar_toggle").hidden = false;
    },

    /**
     * Displays the settings panel
     */
    showSidebar: function() {
        document.querySelector(".sidebar_toggle").hidden = true;
        document.querySelector(".sidebar").hidden = false;
    },


    /**
     * Shows a pop-up message
     *
     * @param message
     */
    showToast: function(message) {
        const toastParent = document.getElementById("toast-container");
        if (toastParent !== null) {

            const existing = [...toastParent.querySelectorAll(".toast-message")].some(t => t.innerText === message);
            if (existing) return;

            const toast = document.createElement("div");
            toast.setAttribute("class", "toast-message pointer");
            if (message.substring(0, ERROR_CHAR.length) === ERROR_CHAR) {
                toast.className += " error";
                message = message.substring(ERROR_CHAR.length);
            }
            toast.innerText = message;
            let msg = [];
            msg[0] = new Date();
            msg[1] = message;
            messageHistory.push(msg);
            setTimeout(function () {
                toast.remove();
            }, 5500);
            toastParent.appendChild(toast);
            toast.setAttribute("style", " margin-left: -"+toast.clientWidth/2 + "px; width:" + toast.clientWidth + "px");
            toast.setAttribute("onclick", "return showHelp('message_history');");
            toast.className += " show";
        }
    },

    /**
     * Scroll the diagram to put the provided XREF at the given position on the screen
     * Placed in middle of screen if no position specified
     *
     * @param xref xref of individual whose tile we are positioning
     * @param scrollX Position middle of tile this far from left edge of view
     * @param scrollY Position middle of tile this far from top of view
     * @param zoom Set zoom level to this number
     * @returns {boolean}
     */
    scrollToRecord(xref, type = 'indi', scrollX = null, scrollY = null, zoom = null, nth = 0) {
        // Why do we multiply the scale by 1 and 1/3?
        let zoomBase = (zoom ? zoom : panzoomInst.getTransform().scale) * (1 + 1 / 3);
        let zoom_value = zoomBase * parseFloat(document.getElementById("dpi").value) / 72;
        let [found, x, y] = UI.tile.getElementPositionFromXref(xref, type, nth);
        if (!found) {
            // The xref isn't in the diagram
            return false;
        } else {
            const rendering = document.getElementById('rendering');
            const svg = rendering.getElementsByTagName('svg')[0];
            if (zoom) {
                panzoomInst.zoomTo(0, 0, zoom);
            }
            if (scrollX && scrollY) {
                // Jump xref tile to position. Note this does not scroll
                panzoomInst.moveTo(scrollX - x * zoom_value, scrollY - parseFloat(svg.getAttribute('height')) * zoomBase - y * zoom_value);
            } else {
                // Put in middle of screen if no position specified
                panzoomInst.smoothMoveTo((rendering.offsetWidth / 2) - x * zoom_value, (rendering.offsetHeight / 2) - parseFloat(svg.getAttribute('height')) * zoomBase - y * zoom_value);
            }
            return true;
        }
    },



    calculateDiagramSize: {
        getRenderWidth() {
            let svgEl = document.getElementById("rendering").querySelector("svg");
            return svgEl.getAttribute("width").split('pt')[0];
        },

        getRenderHeight() {
            let svgEl = document.getElementById("rendering").querySelector("svg");
            return svgEl.getAttribute("height").split('pt')[0];
        },

        getDpi() {
            let dpiEl = form.querySelector("#dpi");
            return dpiEl.value;
        },

        getDiagramDimensionsString() {
            const width = this.getRenderWidth();
            const height = this.getRenderHeight();
            const BASE_DPI = 72;
            const dpi = this.getDpi();
            const INCH_TO_MM = 25.4;
            const MARGIN_WIDTH = 20 * dpi/BASE_DPI;
            const MARGIN_HEIGHT = 26 * dpi/BASE_DPI;

            const inchesWidth = width/BASE_DPI;
            const inchesHeight = height/BASE_DPI;
            const mmWidth = (inchesWidth * INCH_TO_MM) + MARGIN_WIDTH;
            const mmHeight = (inchesHeight * INCH_TO_MM) +  MARGIN_HEIGHT;
            return Math.round(mmWidth) + "mm x " + Math.round(mmHeight) + "mm";
        },

        updateDiagramSize() {
            let diagramSizeEl = form.querySelector("#diagram_size");
            diagramSizeEl.innerHTML = this.getDiagramDimensionsString();
        }
    },

    tile: {
        /**
         * Check if the SVG node has <A> tags with a URL with '/individual/' in it.
         * @param node
         * @returns {boolean}
         */
        isNodeAnIndividual(node) {
            if (Data.url.cleanUrl(node.getAttribute('xlink:href')).indexOf('/individual/') !== -1) {
                return true;
            }
            // Also check children
            for (let i = 0; i < node.childNodes.length; i++) {
                const child = node.childNodes[i];
                if (child.tagName && child.tagName.toLowerCase() === 'a') {
                    if (Data.url.cleanUrl(node.getAttribute('xlink:href')).indexOf('/individual/') !== -1) {
                        return true;
                    }
                }
                // Recursively check child nodes
                if (child.childNodes.length > 0) {
                    if (this.isNodeAnIndividual(child)) {
                        return true;
                    }
                }
            }

            return false;
        },

        /**
         * Add event listeners to handle clicks on the individuals and family nodes in the diagram
         */
        handleTileClick() {
            const MIN_DRAG = 100;
            const DEFAULT_ACTION = '0';
            let startx;
            let starty;

            let linkElements = document.querySelectorAll("svg a");
            linkElements = Array.from(linkElements).filter(function (aTag) {
                return aTag.hasAttribute('xlink:href') && aTag.hasAttribute('xmlns:xlink');
            });

            for (let i = 0; i < linkElements.length; i++) {
                linkElements[i].addEventListener("mousedown", function (e) {
                    startx = e.clientX;
                    starty = e.clientY;
                });
                // Only trigger links if not dragging
                linkElements[i].addEventListener('click', function (e) {
                    e.preventDefault();
                    let isIndividual = UI.tile.isNodeAnIndividual(linkElements[i]);
                    let clickActionEl = isIndividual ? document.getElementById('click_action_indi') : document.getElementById('click_action_fam');
                    let clickAction = clickActionEl ? clickActionEl.value : DEFAULT_ACTION;
                    let url = linkElements[i].getAttribute('xlink:href');

                    // Do nothing if user is dragging
                    if (Data.getDistance(startx, starty, e.clientX, e.clientY) >= MIN_DRAG) {
                        return;
                    }

                    if (isIndividual) {
                        let xref = Data.url.getXrefFromUrl(url);
                        switch (clickAction) {
                            case '0':
                                window.open(url,'_blank');
                                break;
                            case '10': // Add to list of starting individuals
                                UI.tile.addIndividualToStartingIndividualsList(xref);
                                break;
                            case '20': // Remove list of starting individuals and have just this person
                                if (xref) {
                                    Form.indiList.clearIndiList(false);
                                    Form.indiList.addIndiToList(xref);
                                    Data.url.changeURLXref(xref);
                                    Form.handleFormChange();
                                }
                                break;
                            case '30':// Add to list of stopping individuals
                                if (xref) {
                                    Form.stoppingIndiList.addIndiToStopList(xref);
                                    Form.handleFormChange();
                                }
                                break;
                            case '40':// Remove list of stopping individuals and have just this person
                                if (xref) {
                                    Form.stoppingIndiList.clearStopIndiList(false);
                                    Form.stoppingIndiList.addIndiToStopList(xref);
                                    Data.url.changeURLXref(xref);
                                    Form.handleFormChange();
                                }
                                break;
                            case '50': // Show a menu for user to choose
                                UI.tile.showIndiContextMenu(e, url, xref);
                                break;
                            case '70': // Add XREF to list of custom highlighted individuals
                                UI.tile.highlightIndividual(xref);
                                break;
                            case '80': // Add a partner
                                UI.tile.goToAddPartner(url, xref);
                                break;
                            case '90': // Add a parent
                                UI.tile.goToAddParent(url, xref);
                                break;
                            case '100': // Add to clippings cart
                                UI.tile.addXrefsToClippingsCart([xref]);
                                break;
                            case '60': // Do nothing option
                            default: // Unknown, so do nothing
                                break;
                        }
                    } else {
                        let parent = e.currentTarget.closest('g.node');
                        let titleEl = parent.querySelector('title');
                        let xref = titleEl.textContent;
                        // Is a family tile
                        switch (clickAction) {
                            case '0': // Open family page
                                window.open(url,'_blank');
                                break;
                            case '10': // Add a child
                                UI.tile.goToAddChild(url, xref);
                                break;
                            case '20': // Add family to list of custom highlighted families
                                UI.tile.highlightFamily(xref);
                                break;
                            case '50': // Change family members
                                UI.tile.changeFamilyMembers(url, xref);
                                break;
                            case '60': // Add to clippings cart
                                UI.tile.addXrefsToClippingsCart([xref]);
                                break;
                            case '30': // Show menu
                                UI.tile.showFamilyContextMenu(e, url, xref);
                                break;
                            case '40': // Do nothing option
                            default: // Unknown, so do nothing
                                break;
                        }

                    }

                });
            }
        },

        /**
         * Shows a context menu on an individual in the diagram, e.g. show menu when individual clicked if this option enabled
         *
         * @param e The click event
         * @param url The URL of the individual's webtrees page
         * @param xref The xref of the individual
         */
        showIndiContextMenu(e, url, xref) {
            UI.contextMenu.clearContextMenu();
            const div = document.getElementById('context_list');
            div.setAttribute("data-xref",  xref);
            div.setAttribute("data-url",  url);
            UI.contextMenu.addContextMenuOption('👤', 'Open individual\'s page', UI.tile.openContextMenuUrl);
            UI.contextMenu.addContextMenuOption('➕', 'Add individual to list of starting individuals', UI.tile.addIndividualToStartingIndividualsContextMenu);
            UI.contextMenu.addContextMenuOption('🔄', 'Replace starting individuals with this individual', UI.tile.replaceStartingIndividualsContextMenu);
            UI.contextMenu.addContextMenuOption('🛑', 'Add this individual to the list of stopping individuals', UI.tile.addIndividualToStoppingIndividualsContextMenu);
            UI.contextMenu.addContextMenuOption('🚫', 'Replace stopping individuals with this individual', UI.tile.replaceStoppingIndividualsContextMenu);
            UI.contextMenu.addContextMenuOption('🖍️', 'Add to list of individuals to highlight', UI.tile.highlightIndividualContextMenu);
            UI.contextMenu.addContextMenuOption('❤️', 'Add a partner', UI.tile.addPartnerContextMenu);
            UI.contextMenu.addContextMenuOption('🧑‍🧒', 'Add a parent', UI.tile.addParentContextMenu);
            UI.contextMenu.addContextMenuOption('🛒', 'Add to clippings cart', UI.tile.addIndividualToCartContextMenu);
            UI.contextMenu.enableContextMenu(window.innerWidth - e.clientX, e.clientY);
        },

        /**
         * Shows a context menu on a family in the diagram, e.g. show menu when family clicked if this option enabled
         *
         * @param e The click event
         * @param url The URL of the individual or family webtrees page
         * @param xref The xref of the family
         */
        showFamilyContextMenu(e, url, xref) {
            UI.contextMenu.clearContextMenu();
            const div = document.getElementById('context_list');
            div.setAttribute("data-xref",  xref);
            div.setAttribute("data-url",  url);
            UI.contextMenu.addContextMenuOption('👥', 'Open family page', UI.tile.openContextMenuUrl);
            UI.contextMenu.addContextMenuOption('👶', 'Add a child', UI.tile.addChildContextMenu);
            UI.contextMenu.addContextMenuOption('🖍️', 'Add to list of families to highlight', UI.tile.highlightFamilyContextMenu);
            UI.contextMenu.addContextMenuOption('🧑‍🧑‍🧒‍🧒', 'Change family members', UI.tile.changeFamilyMembersContextMenu);
            UI.contextMenu.addContextMenuOption('🛒', 'Add to clippings cart', UI.tile.addFamilyToCartContextMenu);
            UI.contextMenu.enableContextMenu(window.innerWidth - e.clientX, e.clientY);
        },

        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        openContextMenuUrl(e) {
            UI.tile.openUrlFromContextMenu(e.currentTarget.parentElement.getAttribute('data-url'));
        },

        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        addIndividualToStartingIndividualsContextMenu(e) {
            UI.tile.addIndividualToStartingIndividualsList(e.currentTarget.parentElement.getAttribute('data-xref'));
        },

        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        replaceStartingIndividualsContextMenu(e) {
            UI.tile.replaceStartingIndividuals(e.currentTarget.parentElement.getAttribute('data-xref'));
        },

        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        addIndividualToStoppingIndividualsContextMenu(e) {
            UI.tile.addIndividualToStoppingIndividualsList(e.currentTarget.parentElement.getAttribute('data-xref'));
        },

        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        replaceStoppingIndividualsContextMenu(e) {
            UI.tile.replaceStoppingIndividuals(e.currentTarget.parentElement.getAttribute('data-xref'));
        },

        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        highlightIndividualContextMenu(e) {
            UI.tile.highlightIndividual(e.currentTarget.parentElement.getAttribute('data-xref'));
        },

        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        addPartnerContextMenu(e) {
            let xref = e.currentTarget.parentElement.getAttribute('data-xref');
            let url = e.currentTarget.parentElement.getAttribute('data-url');
            UI.tile.goToAddPartner(url, xref);
        },

        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        addParentContextMenu(e) {
            let xref = e.currentTarget.parentElement.getAttribute('data-xref');
            let url = e.currentTarget.parentElement.getAttribute('data-url');
            UI.tile.goToAddParent(url, xref);
        },


        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        addIndividualToCartContextMenu(e) {
            let xref = e.currentTarget.parentElement.getAttribute('data-xref');
            UI.tile.addXrefsToClippingsCart([xref]);
        },

        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        highlightFamilyContextMenu(e) {
            UI.tile.highlightFamily(e.currentTarget.parentElement.getAttribute('data-xref'));
        },

        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        changeFamilyMembersContextMenu(e) {
            let xref = e.currentTarget.parentElement.getAttribute('data-xref');
            let url = e.currentTarget.parentElement.getAttribute('data-url');
            UI.tile.changeFamilyMembers(url, xref);
        },

        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        addFamilyToCartContextMenu(e) {
            let xref = e.currentTarget.parentElement.getAttribute('data-xref');
            UI.tile.addXrefsToClippingsCart([xref]);
        },

        /**
         * Function for context menu item
         *
         * @param e Click event
         */
        addChildContextMenu(e) {
            let xref = e.currentTarget.parentElement.getAttribute('data-xref');
            let url = e.currentTarget.parentElement.getAttribute('data-url');
            UI.tile.goToAddChild(url, xref);
        },

        /**
         * Go to the add a child webtrees page
         */
        goToAddChild(url, xref) {
            let urlDecoded = url.replaceAll('%2F', '/'); // Handle non-pretty URLs
            let pos = urlDecoded.lastIndexOf('/family/');
            let addUrl = urlDecoded.substring(0,pos) + "/add-child-to-family/" + xref + "/U";
            window.open(addUrl,'_blank');
        },

        /**
         * Go to the add a spouse webtrees page
         */
        goToAddPartner(url, xref) {
            let urlDecoded = url.replaceAll('%2F', '/'); // Handle non-pretty URLs
            let pos = urlDecoded.lastIndexOf('/individual/');
            let addUrl = urlDecoded.substring(0,pos) + "/add-spouse-to-individual/" + xref;
            window.open(addUrl,'_blank');
        },

        /**
         * Go to the add a parent webtrees page
         */
        goToAddParent(url, xref) {
            let urlDecoded = url.replaceAll('%2F', '/'); // Handle non-pretty URLs
            let pos = urlDecoded.lastIndexOf('/individual/');
            let addUrl = urlDecoded.substring(0,pos) + "/add-parent-to-individual/" + xref + "/U";
            window.open(addUrl,'_blank');
        },

        /**
         * Go to the change family members webtrees page
         */
        changeFamilyMembers(url, xref) {
            // We need to handle pretty and non-pretty URLs differently
            let urlDecoded = url.replaceAll('%2F', '/');
            let prettyUrls = urlDecoded.indexOf('route=') === -1;
            let pos = urlDecoded.lastIndexOf('/family/');
            let addUrl = urlDecoded.substring(0,pos) + '/change-family-members' + (prettyUrls ? '?' : '&') + 'xref=' + xref;
            window.open(addUrl,'_blank');
        },

        /**
         * Add the XREF records to the clippings cart
         */
        addXrefsToClippingsCartSavedSetting(xrefs, userPrompted = false) {
            if (userPrompted) {
                this.addXrefsToClippingsCart(xrefs);
            } else {
                let message = TRANSLATE["This saved setting contains clippings cart items, add them to the clippings cart?"];
                let buttons = '<div class="modal-button-container"><button id="modal-cancel" class="btn btn-secondary modal-button" >' + TRANSLATE['Cancel'] + '</button><button id="modal-yes" class="btn btn-primary modal-button" >' + TRANSLATE['Yes'] + '</button></div>';
                showModal('<div class="modal-container">' + message + '<br>' + buttons + '</div>');
                
                document.getElementById('modal-cancel').onclick = () => {
                    document.getElementById('modal').remove();
                    Form.showHide(document.getElementById('cart-section'), !cartempty);
                    Form.toggleCart(!cartempty);
                };

                document.getElementById('modal-yes').onclick = () => {
                    UI.tile.addXrefsToClippingsCartSavedSetting(xrefs, true);
                    document.getElementById('modal').remove();
                };

                return false;
            }
        },

        /**
         * Add the XREF records to the clippings cart
         */
        addXrefsToClippingsCart(xrefs) {
            this.addXrefsToClippingsCartRequest(xrefs).then((response) => {
                if (response) {
                    Form.updateClippingsCartCount();
                    UI.showToast(TRANSLATE[response]);
                    UI.contextMenu.clearContextMenu();
                } else {
                    UI.showToast(ERROR_CHAR + TRANSLATE['Unknown error']);
                }
            });
        },

        /**
         * Send the server request to add the array of xrefs to the clippings cart
         */
        addXrefsToClippingsCartRequest(xrefs) {
                let request = {
                "type": REQUEST_TYPE_ADD_CLIPPINGS_CART,
                "xrefs": xrefs,
            };
            return Data.callAPI(request);
        },

        /**
         * Opens the given URL from a context menu
         *
         * @param xref
         */
        openUrlFromContextMenu(url) {
            if (url) {
                window.open(url,'_blank');
                UI.contextMenu.clearContextMenu();
            }
        },

        /**
         * Adds the individual to the starting individual list
         *
         * @param xref
         */
        addIndividualToStartingIndividualsList(xref) {
            if (xref) {
                Form.indiList.addIndiToList(xref);
                Form.handleFormChange(xref);
                UI.contextMenu.clearContextMenu();
            }
        },

        /**
         * Replaces starting individual list with this individual
         *
         * @param xref
         */
        replaceStartingIndividuals(xref) {
            if (xref) {
                Form.indiList.clearIndiList(false);
                Form.indiList.addIndiToList(xref);
                Data.url.changeURLXref(xref);
                Form.handleFormChange(xref);
                UI.contextMenu.clearContextMenu();
            }
        },

        /**
         * Adds the individual to the stopping individual list
         *
         * @param xref
         */
        addIndividualToStoppingIndividualsList(xref) {
            if (xref) {
                Form.stoppingIndiList.addIndiToStopList(xref);
                Form.handleFormChange(xref);
                UI.contextMenu.clearContextMenu();
            }
        },

        /**
         * Replaces stopping individual list with this individual
         *
         * @param xref
         */
        replaceStoppingIndividuals(xref) {
            if (xref) {
                Form.stoppingIndiList.clearStopIndiList(false);
                Form.stoppingIndiList.addIndiToStopList(xref);
                Form.handleFormChange(xref);
                UI.contextMenu.clearContextMenu();
            }
        },

        /**
         * Adds individual's XREF to custom highlight list
         *
         * @param xref
         */
        highlightIndividual(xref) {
            if (xref) {
                this.addIndiToCustomHighlightList(xref);
                Form.handleFormChange(xref);
                UI.contextMenu.clearContextMenu();
            }
        },


        /**
         * Adds family XREF to custom highlight list
         *
         * @param xref
         */
        highlightFamily(xref) {
            if (xref) {
                this.addFamToCustomHighlightList(xref);
                Form.handleFormChange(xref, 'fam');
                UI.contextMenu.clearContextMenu();
            }
        },

        /**
         *  Adds the XREF to the list of individuals to highlight
         *
         * @param xref xref of individual to add
         * @param colour colour to use for this individual (if not default)
         */
        addIndiToCustomHighlightList(xref, colour = null) {
            let listEl = document.getElementById('highlight_custom_json');
            let list = listEl.value.trim();
            if (list.trim() === '') list = '{}';
            let data = JSON.parse(list);
            if (xref !== '' && !data[xref]) {
                if (colour) {
                    data[xref] = colour;
                } else {
                    let el = document.getElementById('highlight_custom_col');
                    if (el) {
                        data[xref] = el.value;
                    }
                }
            }
            listEl.value = JSON.stringify(data);
            Form.indiList.refreshIndisFromJson('highlight_custom_json', 'highlight_list');
        },

        /**
         *  Adds the XREF to the list of families to highlight
         *
         * @param xref xref of family to add
         * @param colour colour to use for this family (if not default)
         */
        addFamToCustomHighlightList(xref, colour = null) {
            let listEl = document.getElementById('highlight_custom_fams_json');
            let list = listEl.value.trim();
            if (list.trim() === '') list = '{}';
            let data = JSON.parse(list);
            if (xref !== '' && !data[xref]) {
                if (colour) {
                    data[xref] = colour;
                } else {
                    let el = document.getElementById('highlight_custom_fams_col');
                    if (el) {
                        data[xref] = el.value;
                    }
                }
            }
            listEl.value = JSON.stringify(data);
            Form.famList.refreshFamsFromJson('highlight_custom_fams_json', 'highlight_fams_list');
        },

        /**
         * Save settings without refreshing diagram
         */
        clickOptionChanged() {
            Data.api.isUserLoggedIn().then((loggedIn) => {
                if (loggedIn) {
                    saveSettingsServer().then();
                } else {
                    Data.storeSettings.saveSettingsClient(ID_MAIN_SETTINGS).then();
                }
            });
        },

        /**
         * Calls the right function to get the position of the relevant element
         * 
         * @param {*} xref xref of entity (individual or family)
         * @param {*} type the type of the entity (individual or family)
         */

        getElementPositionFromXref(xref, type, nth = 0) {
            if (type === 'indi') {
                return this.getPolygonPositionFromXref(xref, nth);
            } else if (type === 'fam') {
                return this.getEllipsePositionFromXref(xref);
            }
            return false;
        },

        /**
         * Finds the individual's tile from the provided XREF, and returns position information
         *
         * @param xref The XREF of the individual we are looking for
         * @returns {boolean[]|(boolean|string|number)[]} An array [true if found, x position, y position]
         */
        getPolygonPositionFromXref(xref, nth = 0) {
            const rendering = document.getElementById('rendering');
            const svg = rendering.getElementsByTagName('svg')[0].cloneNode(true);
            let titles = svg.getElementsByTagName('title');

            let count = 0;
            for (let i=0; i<titles.length; i++) {
                let xrefs = titles[i].innerHTML.split("_");
                for (let j=0; j<xrefs.length; j++) {
                    if (xrefs[j] === xref) {
                        if (count !== nth) {
                            count ++;
                            continue;
                        }
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
                            const textList = group.getElementsByTagName('text');
                            if (textList.length !== 0) {
                                x = textList[0].getAttribute('x');
                                y = textList[0].getAttribute('y');
                            } else {
                                console.warn("No polygon or text found for XREF:", xref, group);
                                return [false, null, null];
                            }
                        }
                        return [true, x, y];
                    }
                }
            }
            return [false, null, null];
        },


        /**
         * Finds the family's tile from the provided XREF, and returns position information
         *
         * @param xref The XREF of the family we are looking for
         * @returns {boolean[]|(boolean|string|number)[]} An array [true if found, x position, y position]
         */
        getEllipsePositionFromXref(xref) {
            const rendering = document.getElementById('rendering');
            const svg = rendering.getElementsByTagName('svg')[0].cloneNode(true);
            let titles = svg.getElementsByTagName('title');
            for (let i=0; i<titles.length; i++) {
                let xrefs = titles[i].innerHTML.split("_");
                for (let j=0; j<xrefs.length; j++) {
                    if (xrefs[j] === xref) {
                        let x = null;
                        let y = null;
                        const group = titles[i].parentElement;
                        // We need to locate the element within the SVG. We use "ellipse" here because it is the
                        // only element that will always exist and that also has position information
                        // (other elements like text, image, etc. can be disabled by the user)
                        const list = group.getElementsByTagName('ellipse');
                        if (list.length !== 0) {
                            x = list[0].getAttribute('cx');
                            y = list[0].getAttribute('cy');
                        } else {
                            x = group.getElementsByTagName('text')[0].getAttribute('x');
                            y = group.getElementsByTagName('text')[0].getAttribute('y')
                        }
                        return [true, x, y];
                    }
                }
            }
            return [false, null, null];
        },

    },

    /**
     * Additional side panel that shows help information
     */
    helpPanel: {

        /**
         * Run startup code when help panel created
         */
        init() {
            document.querySelector('.hide-help').addEventListener('click', UI.helpPanel.hideHelpSidebar);
            document.querySelector('.help-toggle a').addEventListener('click', UI.helpPanel.clickHelpSidebarButton);
            document.querySelector('.btn-help-home').addEventListener('click', UI.helpPanel.loadHelpHome);
            document.querySelector('#help-about').addEventListener('click', UI.helpPanel.loadHelpAbout);
            let helpContentElement = document.querySelector('#help-content');
            helpContentElement.addEventListener('click', UI.helpPanel.handleHelpContentClick);
            UI.helpPanel.loadHelp('Home').catch(function (error){
                UI.showToast(ERROR_CHAR + error);
            });
        },

        /**
         * Handle event when button to show sidebar is clicked
         */
        clickHelpSidebarButton() {
            UI.helpPanel.showHelpSidebar();
        },

        /**
         * Displays the help side panel
         *
         * @param help
         */
        showHelpSidebar(help = '') {
            if (document.getElementById('help-content').innerText === '') {
                this.loadHelpHome();
            }

            UI.helpPanel.loadHelp(help).then(() => {
                document.querySelector(".help-toggle").hidden = true;
                document.querySelector(".help-sidebar").hidden = false;
            })
        },

        /**
         * Hides the help sidebar
         */
        hideHelpSidebar() {
            document.querySelector(".help-sidebar").hidden = true;
            document.querySelector(".help-toggle").hidden = false;
        },

        /**
         * 
         */
        showHelpFullscreen() {
            let menu = document.getElementsByClassName('help-sidebar').item(0);
            if (menu.hidden) {
                UI.helpPanel.showHelpSidebar();
            } else {
                UI.helpPanel.hideHelpSidebar();
            }
        },

        /**
         * Handle click on help content form
         *
         * @param event
         */
        handleHelpContentClick(event) {
            if (event.target.tagName === 'A') {
                UI.helpPanel.showHelpSidebar(event.target.getAttribute('data-name'));
            }
        },

        /**
         * Reverts the help panel back to the home page
         */
        loadHelpHome() {
            UI.helpPanel.loadHelp('Home');
        },

        /**
         * Shows the About GVExport page when Help button clicked
         */
        loadHelpAbout(event) {
            event.preventDefault();
            UI.helpPanel.showHelpSidebar('About GVExport');
        },

        /**
         * Send request to server to retrieve help information then
         * adds response into page
         *
         * @param help The name of the help we want to load
         */
        loadHelp(help) {
            if (help !== '') {
                return Data.getHelp(help).then(function (response) {
                    if (response) {
                        let contentEl = document.getElementById('help-content');
                        contentEl.innerHTML = Data.decodeHTML(response);
                        contentEl.scrollTop = 0;
                    } else {
                        setTimeout(function(){location.reload();}, 3000);
                        UI.showToast(ERROR_CHAR + TRANSLATE['Login expired. Reloading page...']);
                    }
                });
            } else {
                return Promise.resolve();
            }
        },

        /**
         * Triggered when help icon is clicked
         *
         * @param event
         */
        clickInfoIcon(event) {
            event.stopPropagation();
            event.preventDefault();
            UI.helpPanel.showHelpSidebar(event.target.getAttribute('data-help'));
            UI.contextMenu.clearContextMenu();
        }
    },

    /**
     * When page loaded, make changes if theme chosen doesn't work nicely
     *
     */
    fixTheme() {
        try {
            // first, what theme?
            let theme = '';
            let dropdownMenu = document.querySelector('.menu-theme');
            if (dropdownMenu) {
                let activeItem = dropdownMenu.querySelector('.dropdown-item.active');
                if (activeItem) {
                    theme = activeItem.textContent.trim();
                }
            }

            if (theme === 'webtrees') {
                // Set the webtrees blue colours that are for some reason not stored in a css variable
                // We do this here rather than hard coding the colour we want so we don't break other themes.
                let elements = document.querySelectorAll('.sidebar-labels');
                for (let i = 0; i < elements.length; i++) {
                    elements[i].classList.add('theme-webtrees-labels');
                }

                elements = document.querySelectorAll('.options-panel-background');
                for (let i = 0; i < elements.length; i++) {
                    elements[i].classList.add('theme-webtrees-options');
                }

                // Make colour behind headings white instead of theme colour leaking through
                let form = document.getElementById('gvexport');
                form.style.background = '#ffffff';
            }
        } catch (e) {
            // ignore if theme doesn't like us messing with it
        }
    },

    /**
     * Represents the context menu that is shown when an individual is selected and the option to show a menu is enabled
     */
    contextMenu: {
        /**
         * Adds the context menu element - must only be run once (say, on page load)
         */
        init() {
            let div = document.createElement('div');
            div.setAttribute('id', 'context_list');
            div.style.display = 'block';
            div.style.paddingRight = '20px';
            document.getElementById('context_menu').appendChild(div);
        },

        /**
         * Enables the context menu at the provided page location
         *
         * @param x
         * @param y
         */
        enableContextMenu(x, y) {
            const contextMenu = document.getElementById('context_menu');
            // Un-hide first, so our width calculations work
            contextMenu.style.display = '';

            // Adjustment so pointy bit of menu is on mouse click position
            x -= 8;
            y += 5;

            // Make sure it's not off the page
            let width = contextMenu.offsetWidth;
            let height = contextMenu.offsetHeight;

            if (x + width >= window.innerWidth) {
                let diff = x + width - window.innerWidth;
                contextMenu.style.setProperty('--gve-context-right', diff + 'px');
                x = window.innerWidth - width;
            } else {
                contextMenu.style.setProperty('--gve-context-right', '1px');
            }
            if (y + height >= window.innerHeight) {
                let diff = y + height - window.innerHeight;
                contextMenu.style.setProperty('--gve-context-top', (height-7) + 'px');
                contextMenu.style.setProperty('--gve-context-up', 'transparent');
                contextMenu.style.setProperty('--gve-context-down', 'var(--gve-context-border)');
                y -= height+10;
            } else {
                contextMenu.style.setProperty('--gve-context-top', '-5px');
                contextMenu.style.setProperty('--gve-context-up', 'var(--gve-context-border)');
                contextMenu.style.setProperty('--gve-context-down', 'transparent');
            }

            // Set position
            contextMenu.style.position = 'fixed';
            contextMenu.style.right = x + 'px';
            contextMenu.style.top = y + 'px';
        },

        /**
         * Removes items from context menu and hides it
         */
        clearContextMenu() {
            const list = document.getElementById('context_list');
            const menu = document.getElementById('context_menu');
            if (menu.style.display !== 'none') {
                list.innerHTML = '';
                menu.style.display = 'none';
            }
        },

        /**
         * Adds an option to the context menu list
         *
         * @param emoji Emoji to show at the start of line before text
         * @param text The text of the option to show
         * @param callback The function to call when option is selected
         */
        addContextMenuOption(emoji, text, callback) {
            const div = document.getElementById('context_list');
            let el = document.createElement('a');
            el.setAttribute('class', 'settings_ellipsis_menu_item');
            el.innerHTML = '<span class="settings_ellipsis_menu_icon">' + emoji + '</span><span>' + TRANSLATE[text] + '</span>';
            div.appendChild(el);
            el.addEventListener("click", (e) => {
                callback(e);
            });
        }
    },

    /**
     * UI functionality for Saved Settings options
     */
    savedSettings: {

        /**
         * Display context menu for item in saved settings list
         *
         * @param event
         */
        showSavedSettingsItemMenu(event) {
            event.stopImmediatePropagation();
            let id = event.target.parentElement.parentElement.getAttribute('data-id');
            let token = event.target.parentElement.parentElement.getAttribute('data-token');
            removeSettingsEllipsisMenu(event.target);
            Data.api.isUserLoggedIn().then((loggedIn) => {
                if (id != null) {
                    id = id.trim();
                    let div = document.createElement('div');
                    div.setAttribute('class', 'settings_ellipsis_menu');
                    UI.savedSettings.addSettingsMenuOption(id, div, '❌', 'Delete', UI.savedSettings.deleteSettingsMenuAction);
                    UI.savedSettings.addSettingsMenuOption(id, div, '💻', 'Download', UI.savedSettings.downloadSettingsFileMenuAction);
                    UI.savedSettings.addSettingsMenuOption(id, div, '🏷️', 'Rename', UI.savedSettings.renameSettingsMenuAction);
                    if (loggedIn) {
                        UI.savedSettings.addSettingsMenuOption(id, div, '🔗', 'Copy link', UI.savedSettings.copySavedSettingsLinkMenuAction);
                        if (token !== '') {
                            UI.savedSettings.addSettingsMenuOption(id, div, '🚫', 'Revoke link', UI.savedSettings.revokeSavedSettingsLinkMenuAction, token);
                        }
                        if (MY_FAVORITES_MODULE_ACTIVE) {
                            UI.savedSettings.addSettingsMenuOption(id, div, '🌟', 'Add to My favourites', UI.savedSettings.addUrlToMyFavouritesMenuAction);
                        }
                        if (TREE_FAVORITES_MODULE_ACTIVE) {
                            UI.savedSettings.addSettingsMenuOption(id, div, '🌲', 'Add to Tree favourites', UI.savedSettings.addUrlToTreeFavourites);
                        }
                    }
                    event.target.appendChild(div);
                }
            });
        },

        /**
         * Add an item to the saved settings item context menu
         *
         * @param id
         * @param div
         * @param emoji
         * @param text
         * @param callback
         * @param token
         */
        addSettingsMenuOption(id, div, emoji, text, callback, token = '') {
            let el = document.createElement('a');
            el.setAttribute('class', 'settings_ellipsis_menu_item');
            el.innerHTML = '<span class="settings_ellipsis_menu_icon">' + emoji + '</span><span>' + TRANSLATE[text] + '</span>';
            el.id = id;
            el.token = token;
            el.addEventListener("click", (e) => {
                callback(e);
            });
            div.appendChild(el);
        },

        /**
         * Trigger rename option from saved setting context menu
         *
         * @param e
         */
        renameSettingsMenuAction(e) {
            e.stopPropagation();
            let id = e.currentTarget.id;
            Data.savedSettings.renameSetting(id);
        },

        /**
         * Downloads settings as JSON file
         */
        downloadSettingsFileMenuAction(event) {
            let parent = event.target.parentElement;
            while (!parent.dataset.settings) {
                parent = parent.parentElement;
            }
            let settings_json_string = parent.dataset.settings;
            let settings;
            try {
                settings = JSON.parse(settings_json_string);
            } catch (e) {
                UI.showToast("Failed to load settings: " + e);
                return false;
            }
            let file = new Blob([settings_json_string], {type: "text/plain"});
            let url = URL.createObjectURL(file);
            Data.download.downloadLink(url, TREE_NAME + " - " + settings['save_settings_name'] + ".json")
        },

        /**
         * Trigger delete option from saved setting context menu
         *
         * @param e
         */
        async deleteSettingsMenuAction(e) {
            e.stopPropagation();
            try {
                const id = e.currentTarget.id;
                const loggedIn = await Data.api.isUserLoggedIn();

                if (loggedIn) {
                    await Data.savedSettings.deleteSettingsServer(id);
                    loadSettingsDetails();
                } else {
                    Data.savedSettings.deleteSettingsClient(id);
                    loadSettingsDetails();
                }
            } catch (error) {
                UI.showToast("Failed to delete settings: " + error);
            }
        },

        /**
         * Trigger "copy link" option from saved setting context menu
         *
         * @param e
         */
        async copySavedSettingsLinkMenuAction(e) {
            e.stopPropagation();
            const id = e.currentTarget.id;
            try {
                const url = await Data.savedSettings.getSavedSettingsLink(id);
                await copyToClipboard(url);
                UI.showToast(TRANSLATE['Copied link to clipboard']);
            } catch (error) {
                console.error('Error copying saved settings link:', error);
                UI.showToast(TRANSLATE['Failed to copy link to clipboard']);
                showModal(`<p>${TRANSLATE['Failed to copy link to clipboard']}. ${TRANSLATE['Copy manually below']}:</p><textarea style="width: 100%">${url}</textarea>`);
            }
        },

        /**
         * Trigger option to revoke a shared link from saved setting context menu (after sharing)
         *
         * @param e
         */
        revokeSavedSettingsLinkMenuAction(e) {
            e.stopPropagation();
            let token = e.currentTarget.token;
            Data.api.isUserLoggedIn().then((loggedIn) => {
                if (loggedIn) {
                    let request = {
                        "type": REQUEST_TYPE_REVOKE_SAVED_SETTINGS_LINK,
                        "token": token
                    };
                    let json = JSON.stringify(request);
                    sendRequest(json).then((response) => {
                        loadSettingsDetails();
                        try {
                            let json = JSON.parse(response);
                            if (json.success) {
                                UI.showToast(TRANSLATE['Revoked access to shared link']);
                            } else {
                                UI.showToast(ERROR_CHAR + json.errorMessage);
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
         * Trigger option to add link to My Favourites webtrees page, from saved setting context menu
         *
         * @param e
         */
        addUrlToMyFavouritesMenuAction(e) {
            e.stopPropagation();
            let id = e.currentTarget.id;
            Data.api.isUserLoggedIn().then((loggedIn) => {
                if (loggedIn) {
                    let request = {
                        "type": REQUEST_TYPE_ADD_MY_FAVORITE,
                        "settings_id": id
                    };
                    let json = JSON.stringify(request);
                    sendRequest(json).then((response) => {
                        try {
                            let json = JSON.parse(response);
                            if (json.success) {
                                UI.showToast(TRANSLATE['Added to My favourites']);
                            } else {
                                UI.showToast(ERROR_CHAR + json.errorMessage);
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
         * Trigger option to add link to webtrees Tree Favourites page, from saved setting context menu
         *
         * @param e
         */
        addUrlToTreeFavourites(e) {
            e.stopPropagation();
            let parent = event.target.parentElement;
            while (!parent.dataset.id) {
                parent = parent.parentElement;
            }
            let id = parent.getAttribute('data-id');
            Data.api.isUserLoggedIn().then((loggedIn) => {
                if (loggedIn) {
                    let request = {
                        "type": REQUEST_TYPE_ADD_TREE_FAVORITE,
                        "settings_id": id
                    };
                    let json = JSON.stringify(request);
                    sendRequest(json).then((response) => {
                        try {
                            let json = JSON.parse(response);
                            if (json.success) {
                                UI.showToast(TRANSLATE['Added to Tree favourites']);
                            } else {
                                UI.showToast(ERROR_CHAR + json.errorMessage);
                            }
                        } catch (e) {
                            UI.showToast("Failed to load response: " + e);
                            return false;
                        }
                    });
                }
            });
        }
    },

    /**
     * Make a list draggable - to use, run the addDragHandlers() function on each item in the list
     */
    draggableList: {
        dragEl: null,

        /**
         * Adds event listeners to list item element - run this on each <li> element in the list to initiate dragging functionality
         *
         * @param el
         */
        addDragHandlers(el) {
            el.addEventListener('dragstart', UI.draggableList.handleDragStart, false);
            el.addEventListener('dragover', UI.draggableList.handleDragOver, false);
            el.addEventListener('dragleave', UI.draggableList.handleDragLeave, false);
            el.addEventListener('drop', UI.draggableList.handleDrop, false);
            el.addEventListener('dragend', UI.draggableList.handleDragEnd, false);
        },

        /**
         * When you start dragging
         *
         * @param e
         */
        handleDragStart(e) {
            UI.draggableList.dragEl = this;
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/html', this.outerHTML);
        },

        /**
         * When you drag over a list item
         *
         * @param e
         * @returns {boolean}
         */
        handleDragOver(e) {
            if (e.preventDefault) {
                e.preventDefault();
            }
            this.classList.add('over');
            e.dataTransfer.dropEffect = 'move';
            return false;
        },

        /**
         * Remove highlighting when dragging goes away from this list item
         */
        handleDragLeave() {
            this.classList.remove('over');
        },

        /**
         * Remove highlighting when dragging stops
         */
        handleDragEnd(e) {
            this.classList.remove('over');
        },

        /**
         * When you drop item you've been dragging
         *
         * @param e
         * @returns {boolean}
         */
        handleDrop(e) {
            if (e.stopPropagation) {
                e.stopPropagation(); // May help stop browser redirect
            }

            if (UI.draggableList.dragEl !== this) { // If you didn't just drop back where it was
                this.parentNode.removeChild(UI.draggableList.dragEl);
                const dropHTML = e.dataTransfer.getData('text/html');
                this.insertAdjacentHTML('beforebegin',dropHTML);
                const dropElem = this.previousSibling;
                UI.draggableList.addDragHandlers(dropElem);

            }
            this.classList.remove('over');
            return false;
        }
    },

    mainOptions: {
        showOptionsFullscreen() {
            let menu = document.getElementsByClassName('sidebar').item(0);
            if (menu.hidden) {
                UI.showSidebar();
            } else {
                UI.hideSidebar();
            }
        }
    },

    // Toggle full screen for element
    // Modified from https://stackoverflow.com/questions/7130397/how-do-i-make-a-div-full-screen
    toggleFullscreen(elementId = '') {
        // If already fullscreen, exit fullscreen
        if (
            document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement
        ) {
            if (document.exitFullscreen) {
                document.exitFullscreen().then(r => {});
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            }
        } else { // Not full screen, so go fullscreen
            const element = document.getElementById(elementId);
            if (element.requestFullscreen) {
                element.requestFullscreen().then(r => {});
            } else if (element.mozRequestFullScreen) {
                element.mozRequestFullScreen();
            } else if (element.webkitRequestFullscreen) {
                element.webkitRequestFullscreen(Element.ALLOW_KEYBOARD_INPUT);
            } else if (element.msRequestFullscreen) {
                element.msRequestFullscreen();
            }
        }
    }
};
