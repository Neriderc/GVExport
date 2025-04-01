export class SettingsGroup {
    constructor(id, modifierId, value, customCondition) {
        this.id = id;
        this.linkModifierElement(modifierId, value, customCondition);
        // Run to make sure we start with the right state since saved settings can change the state
        this.runEventListener(modifierId, value, customCondition);
    }

    linkModifierElement(modifierId, value, customCondition) {
        let element = document.querySelector('#' + modifierId);
        element.addEventListener('change', () => {this.runEventListener(modifierId, value, customCondition)});
    }

    runEventListener(modifierId, value, customCondition) {
        if (typeof customCondition === 'function') {
            if (!customCondition()) return;
        }
        Form.showHideMatchDropdown(modifierId, this.id, value)
    }
}