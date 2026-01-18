/**
 * Data object to hold diagram related functions
 *
 * @type {{}}
 */
const Diagram = {
    element: document.getElementById('rendering'),
    state: {
        border: 'default',

    },

    svg: {
        /**
         * Clip path for different photo shapes
         * 
         * @param {*} element 
         * @param {*} clipPath 
         */
        setSvgImageClipPath(element, clipPath) {
            const imageElements = element.getElementsByTagName("image");
            for (let i = 0; i < imageElements.length; i++) {
                imageElements[i].setAttribute("clip-path", clipPath);
                imageElements[i].removeAttribute("width");
            }
        },

        /**
         * Tidies SVG before embedding in page
         */
        clean(element) {
            const SHAPE_OVAL = '10';
            const SHAPE_CIRCLE = '20';
            const SHAPE_SQUARE = '30';
            const SHAPE_ROUNDED_RECT = '40';
            const SHAPE_ROUNDED_SQUARE = '50';
            switch(document.getElementById('photo_shape')?.value) {
                case SHAPE_OVAL:
                    this.setSvgImageClipPath(element, "inset(0% round 50%)");
                    break;
                case SHAPE_CIRCLE:
                    this.setSvgImageClipPath(element, "circle(50%)");
                    break;
                case SHAPE_SQUARE:
                    this.setSvgImageClipPath(element, "inset(5%)");
                    break;
                case SHAPE_ROUNDED_RECT:
                    this.setSvgImageClipPath(element, "inset(0% round 25%)");
                    break;
                case SHAPE_ROUNDED_SQUARE:
                    this.setSvgImageClipPath(element, "inset(0% round 25%)");
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
    },

    search: {

        /**
         * Some individuals may appear in the diagram multiple times (especially in combined mode)
         * When this happens, the diagram search function needs a way to present multiple copies
         * of the same individual so the user can select which one to scroll to
         * 
         * @returns 
         */
        setup() {
            const dropdown = document.getElementById('diagram_search_box-ts-dropdown');
            if (!dropdown) return;
            
            const observer = new MutationObserver((mutationsList) => {
                const xrefCounts = Data.countItems(Data.diagram.getXrefs());

                for (const mutation of mutationsList) {
                    if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(node => {
                            if (node.nodeType === Node.ELEMENT_NODE && node.dataset?.value) {
                                const xref = node.dataset.value;
                                const count = xrefCounts.get(xref) || 0;
                                if (count > 1 && !node.id.includes('-dup')) {
                                    for (let i = 0; i < count - 1; i++) {
                                        const clone = node.cloneNode(true);
                                        clone.id += '-dup'+i;
                                        node.parentNode.insertBefore(clone, node.nextSibling);
                                        clone.dataset.value = 'skip_value'; // value is not important as long as not a valid XREF, so other scroll action doesn't kick in
                                        clone.addEventListener('click', (e) => {
                                            UI.scrollToRecord(xref, 'indi', undefined, undefined, undefined, i+1);
                                            Form.showHideSearchBox(e, false);
                                        });
                                    }
                                }
                            }
                        });
                    }
                }
            });

            observer.observe(dropdown, { childList: true, subtree: true });
        },

        change(e) {
            let xref = document.getElementById('diagram_search_box').value.trim();
            // Skip the first trigger, only fire for the follow-up trigger when the XREF is set
            if (xref !== ""){
                if (!UI.scrollToRecord(xref, 'indi', 0)) {
                    UI.showToast(TRANSLATE['Individual not found']);
                }
                Form.clearSelect('diagram_search_box');
                Form.showHideSearchBox(e, false);
            }
        },

        createXrefListFromSvg() {
            xrefCount = Data.countItems(Data.diagram.getXrefs());
        },

        /**
         * In a tomselect, the option chosen goes into a box that is initially blank. For the search box,
         * this blank space is never used (as the selected option is not filled to the box). This function
         * removes this to give a cleaner search box.
         */
        tidyTomSelect() {
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
    },
}