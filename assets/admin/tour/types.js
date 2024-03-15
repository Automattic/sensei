/**
 * A single tour step.
 *
 * @typedef {Object} TourStep
 *
 * @property {string}      slug                           - Identifier slug of the tour step.
 * @property {Object}      meta                           - Metadata about the tour step.
 * @property {string}      meta.heading                   - The title of the step.
 * @property {Object}      meta.descriptions              - Descriptions for different platforms.
 * @property {string}      meta.descriptions.desktop      - Desktop description.
 * @property {string|null} meta.descriptions.mobile       - Mobile description.
 * @property {Object}      meta.referenceElements         - Reference elements for different platforms.
 * @property {string}      meta.referenceElements.desktop - Reference element for desktop.
 * @property {Object}      options                        - Additional options for the tour step.
 * @property {Object}      options.classNames             - Class names for different platforms.
 * @property {string}      options.classNames.desktop     - Class name for desktop.
 * @property {string}      options.classNames.mobile      - Class name for mobile.
 * @property {Function}    action                         - Action to be performed when the step is shown.
 */

/**
 * @type {TourStep}
 */
let TourStep;

export { TourStep };
