const ERROR_CHAR = "E:";
const appendPidTo = function (sourceId, targetId) {
    const ids = [];
    document.getElementById(targetId).value.split(",").forEach(function (id) {
        id = id.trim();
        if (id !== "") {
            ids.push(id);
        }
    });
    const newId = document.getElementById(sourceId).value.trim();
    if (ids.indexOf(newId) === -1) {
        ids.push(newId);
    }
    document.getElementById(targetId).value = ids.join(", ");
};


function hideSidebar(e) {
    document.querySelector(".sidebar").hidden = true;
    document.querySelector(".sidebar__toggler").hidden = false;
    e.preventDefault();
}

function showSidebar(e) {
    document.querySelector(".sidebar__toggler").hidden = true;
    document.querySelector(".sidebar").hidden = false;
    e.preventDefault();
}

document.querySelector(".hide-form").addEventListener("click", hideSidebar);


document.querySelector(".sidebar__toggler a").addEventListener("click", showSidebar);

document.addEventListener("keydown", function(e) {
    if (e.key === "Esc" || e.key === "Escape") {
        document.querySelector(".sidebar").hidden ? showSidebar(e) : hideSidebar(e);
    }
});

// Enable or disable the option to add photos.
// This is used when selecting diagram type, as only
// some types support photos.
function togglePhotos(enable) {
    document.getElementById("vars[with_photos]").disabled = !enable;
}

// Add or remove the % sign from the text input
function togglePercent(element, add) {
    // Clicked out of input field, add % sign
    let startval;
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
        startval = element.value;
        element.value = element.value.replace("%", "");
        if (startval !== element.value) {
            element.select();
        }
    }
}

// Update provided element with provided value when element blank
function defaultValueWhenBlank(element, value) {
    if (element.value === "") {
        element.value = value;
    }
}

function checkIndiBlank() {
    let el = document.getElementsByClassName("item");
    return el.length === 0;
}

// This function ensures that if certain options are checked in regard to which relations to include,
// then other required options are selected. e.g. if "Anyone" is selected, all other options must
// all be selected
function updateRelationOption(field) {
    // If user clicked "All relatives"
    if (field === "indicous") {
        // If function triggered by checking "All relatives" field, ensure "Siblings" is checked
        if (document.getElementById("vars[indicous]").checked) {
            document.getElementById("vars[indisibl]").checked = true;
        }
        // If "All relatives" unchecked, uncheck "Anyone"
        if (!document.getElementById("vars[indicous]").checked) {
            document.getElementById("vars[indiany]").checked = false;
        }
    }
    // If user clicked "Siblings"
    if (field === "indisibl") {
        // If function triggered by unchecking "Siblings" field, ensure "All relatives" is unchecked
        if (!document.getElementById("vars[indisibl]").checked) {
            document.getElementById("vars[indicous]").checked = false;
        }
        // If "Siblings" unchecked, uncheck "Anyone"
        if (!document.getElementById("vars[indisibl]").checked) {
            document.getElementById("vars[indiany]").checked = false;
        }
    }
    // If user clicked "Spouses"
    if (field === "indispou") {
        // If function triggered by checking "All relatives" field, ensure "Siblings" is checked
        if (!document.getElementById("vars[indisibl]").checked) {
            document.getElementById("vars[indicous]").checked = false;
        }
        // If "Spouses" unchecked, uncheck "Anyone"
        if (!document.getElementById("vars[indispou]").checked) {
            document.getElementById("vars[indiany]").checked = false;
        }
    }
    // If function triggered by checking "All relatives" field, ensure everything else is checked
    if (field === "indiany") {
        if (document.getElementById("vars[indiany]").checked) {
            document.getElementById("vars[indicous]").checked = true;
            document.getElementById("vars[indisibl]").checked = true;
            document.getElementById("vars[indispou]").checked = true;

        }
    }

}




// Gets position of element relative to another
// From https://stackoverflow.com/questions/1769584/get-position-of-element-by-javascript
function getPos(el, rel)
{
    let x = 0, y = 0;

    do {
        x += el.offsetLeft;
        y += el.offsetTop;
        el = el.offsetParent;
    }
    while (el !== rel)
    return {x:x, y:y};
}


// Toggle items based on if the items in the cart should be used or not
// enable - if set to true, use cart. Update form to disable options. Set to false to reverse.
function toggleCart(enable) {
    const el = document.getElementsByClassName("cart_toggle");
    for (let i = 0; i < el.length; i++) {
        el.item(i).disabled = enable;
    }
    showHideClass("cart_toggle_hide", !enable);
    showHideClass("cart_toggle_show", enable);
}

// This function is used in toggleCart to show or hide all elements with a certain class,
// by adding or removing "display: none"
// css_class - the class to search for
// show - true to show the elements and false to hide them
function showHideClass(css_class, show) {
    let el = document.getElementsByClassName(css_class);
    for (let i = 0; i < el.length; i++) {
        showHide(el.item(i), show)
    }
}

// Show or hide an element on the page
// element - the element to affect
// show - whether to show (true) or hide (false) the element
function showHide(element, show) {
    if (show) {
        element.style.removeProperty("display");
    } else {
        element.style.display = "none";
    }
}

// Show a toast message
// message - the message to show
function showToast(message) {
    const toastParent = document.getElementById("toast-container");
    if (toastParent !== null) {
        const toast = document.createElement("div");
        toast.setAttribute("id", "toast");
        if (message.substring(0, ERROR_CHAR.length) === ERROR_CHAR) {
            toast.className += "error";
            message = message.substring(ERROR_CHAR.length);
        }
        toast.innerText = message;
        setTimeout(function () {
            toast.remove();
        }, 5500);
        toastParent.appendChild(toast);
        toast.setAttribute("style", " margin-left: -"+toast.clientWidth/2 + "px; width:" + toast.clientWidth + "px");
        toast.className += " show";
    }
}

// Download SVG file
function downloadSVGAsText() {
    const svg = document.getElementById('rendering').getElementsByTagName('svg')[0].cloneNode(true);
    svg.removeAttribute("style");
    let svgData = svg.outerHTML.replace(/&nbsp;/g, '');
    // Replace image URLs with embedded data  for SVG also triggers download
    replaceImageURLs(svgData, "svg", null);
}

function downloadSVGAsPNG() {
    downloadSVGAsImage("png");
}

function downloadSVGAsJPEG() {
    downloadSVGAsImage("jpeg");
}

// Download PNG from SVG file
function downloadSVGAsImage(type) {
    const svg = document.getElementById('rendering').getElementsByTagName('svg')[0].cloneNode(true);
    // Style attribute used for the draggable browser view, remove this to reset to standard SVG
    svg.removeAttribute("style");

    const canvas = document.createElement("canvas");
    const img = document.createElement("img");
    // get svg data and remove line breaks
    let xml = new XMLSerializer().serializeToString(svg);
    // Fix the + symbol (any # breaks everything)
    xml = xml.replace(/&#45;/g,"+");
    // Replace # colours with rgb equivalent
    // From https://stackoverflow.com/questions/13875974/search-and-replace-hexadecimal-color-codes-with-rgb-values-in-a-string
    const rgbHex = /#([0-9A-F][0-9A-F])([0-9A-F][0-9A-F])([0-9A-F][0-9A-F])/gi;
    xml = xml.replace(rgbHex, function (m, r, g, b) {
        return 'rgb(' + parseInt(r,16) + ','
            + parseInt(g,16) + ','
            + parseInt(b,16) + ')';
    });
    // Replace image URLs with embedded images
    replaceImageURLs(xml, type, img);
    // Once image loaded, draw to canvas then download it
    img.onload = function() {
        canvas.setAttribute('width', img.width.toString());
        canvas.setAttribute('height', img.height.toString());
        // draw the image onto the canvas
        let context = canvas.getContext('2d');
        context.drawImage(img, 0, 0, img.width, img.height);
        // Download it
        const dataURL = canvas.toDataURL('image/'+type);
        if (dataURL.length < 10) {
            showToast("E:"+CLIENT_ERRORS[0]); // Canvas too big
        } else {
            downloadLink(dataURL, "gvexport." + type);
        }
    }

}

// Convert image URL to base64 data - we use for embedding images in SVG
// From https://stackoverflow.com/questions/22172604/convert-image-from-url-to-base64
function getBase64Image(img) {
    const canvas = document.createElement("canvas");
    canvas.width = img.width;
    canvas.height = img.height;
    const ctx = canvas.getContext("2d");
    ctx.drawImage(img, 0, 0);
    return canvas.toDataURL("image/png");
}

// Find image URLs and replace with embedded versions
function replaceImageURLs(svg, type, img) {
    let startPos, len, url;
    let match = /<image.*xlink:href="http/.exec(svg);
    if (match != null) {
        startPos = match.index+match[0].length-4;
        len = svg.substring(startPos).indexOf("\"");
        url = svg.substring(startPos,startPos+len);
        const img2 = document.createElement("img");
        img2.onload = function() {
            let base64 = getBase64Image(img2);
            svg = svg.replace(url,base64);
            replaceImageURLs(svg, type, img);
            img2.remove();
        }
        img2.src = url.replace(/&amp;/g,"&");
    } else {
        if (type === "svg") {
            const svgBlob = new Blob([svg], {type: "image/svg+xml;charset=utf-8"});
            const svgUrl = URL.createObjectURL(svgBlob);
            downloadLink(svgUrl, "gvexport."+type);
        } else {
            img.src = "data:image/svg+xml;utf8," + svg;
        }
    }
}

// Trigger a download via javascript
function downloadLink(URL, filename) {
    const downloadLink = document.createElement("a");
    downloadLink.href = URL;
    downloadLink.download = filename;
    document.body.appendChild(downloadLink);
    downloadLink.click();
    document.body.removeChild(downloadLink);
}

// Toggle the showing of an advanced settings section
// id - the id of the element we are toggling
function toggleAdvanced(caller, id) {
    const el = document.getElementById(id);
    const visible = el.style.display !== "none";
    showHide(el, !visible);
    if (visible) {
        caller.innerHTML = caller.innerHTML.replaceAll("↑","↓");
        // Update our hidden field for saving the state
        const hidden = document.getElementById(id+"-hidden");
        hidden.value = "";
    } else {
        caller.innerHTML = caller.innerHTML.replaceAll("↓","↑");
        const hidden = document.getElementById(id+"-hidden");
        hidden.value = "show";
    }
}
