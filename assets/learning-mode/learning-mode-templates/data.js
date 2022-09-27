const data = window.sensei?.learningModeTemplateSetting || {};

export const activeTemplateName = data.value;

export const inputName = data.name;

export const templates = data.options;

export const customizeUrl = data.customizeUrl;

export const formId = data.formId;

export const tabId = data.section;
