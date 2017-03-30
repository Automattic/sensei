jQuery(document).ready(function() {
    var $ = jQuery.noConflict();
    var React = window.React;
    var ReactDOM = window.ReactDOM;

    var RequestResult = React.createClass({
        render: function () {
            return React.createElement('div', null,
                React.createElement('h3', null, 'result'),
                React.createElement('pre', null, JSON.stringify(this.props.result, null, 4)));
        }
    });

    var InputField = React.createClass({
        onUpdate: function (event) {
            event.preventDefault();
            if (!this.props.onUpdate) {
                return;
            }
            var val = event.target.value.trim();
            this.props.onUpdate(this.props.name, val);
        },
        render: function () {
            return React.createElement('div', {className: ''},
                React.createElement('label', {'for': this.props.name}, this.props.name.split('_').join(' ')),
                React.createElement('br', null),
                React.createElement('input', {type: this.props.type, name: this.props.name, value: this.props.value, onChange: this.onUpdate}));
        }
    });

    var RequestForm = React.createClass({
        render: function () {
            return React.createElement('form', {
                    onSubmit: this.props.onSubmit
                },
                React.createElement('h1', {
                    className: 'wp-heading-inline'
                }, 'Sensei REST API Test Harness'),
                React.createElement(InputField, {
                    type: 'text',
                    name: 'url',
                    value: this.props.request.url,
                    onUpdate: this.props.onUpdate
                }),
                React.createElement(InputField, {
                    type: 'text',
                    name: 'method',
                    value: this.props.request.method,
                    onUpdate: this.props.onUpdate
                }),
                React.createElement(InputField, {
                    type: 'text',
                    name: 'data',
                    value: this.props.request.data,
                    onUpdate: this.props.onUpdate
                }),
                React.createElement('button', {
                    className: 'button button-primary button-large'
                }, 'Perform')
            );
        }
    });

    var requestDispatcher = function (state, action) {
        if (typeof state === 'undefined') {
            state = {
                request: {
                    method: 'GET',
                    url: 'sensei/v1/courses',
                    data: '{}'
                },
                result: {}
            }
        }
        switch (action.type) {
            case ACTIONS.UPDATE_REQUEST_FIELD:
                var clonedRequest = $.extend({}, state.request);
                clonedRequest[action.name] = action.value;
                return {
                    request: clonedRequest,
                    result: state.result
                };
            case ACTIONS.UPDATE_RESULT:
                return {
                    request: state.request,
                    result: action.result
                };
            default:
                return state;
        }
    };
    var ACTIONS = {
        UPDATE_REQUEST_FIELD: 'UPDATE_REQUEST_FIELD',
        UPDATE_RESULT: 'UPDATE_RESULT'
    };

    // in terms of react this is our master compoment that should handle all state?
    var TestHarness = React.createClass({

        dispatch: function (action) {
            this.setState(function (prevState) {
                return requestDispatcher(prevState, action);
            });
        },
        getInitialState: function() {
            return requestDispatcher(undefined, {});
        },
        render: function() {
            var wrapper = React.createElement('div', {className: 'wrapper'},
                React.createElement(RequestForm, {
                    request: this.state.request,
                    onSubmit: this.handleSubmit,
                    onUpdate: this.handleFormUpdate
                }),
                React.createElement(RequestResult, {
                    result: this.state.result
                })
            );
            return wrapper;
        },
        handleFormUpdate: function(inputName, value) {
            this.dispatch({
                type: ACTIONS.UPDATE_REQUEST_FIELD,
                name: inputName,
                value: value
            })
        },
        handleSubmit: function (event) {
            var self = this;
            event.preventDefault();
            $.ajax( {
                url: wpApiSettings.root + self.state.request.url,
                method: self.state.request.method,
                dataType: 'json',
                beforeSend: function ( xhr ) {
                    xhr.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
                },
                data: (self.state.request.data) ? JSON.parse(self.state.request.data) : {},
            }).done( function ( response ) {
                self.dispatch({
                    type: ACTIONS.UPDATE_RESULT,
                    result: response
                });
            }).fail(function (err) {
                self.dispatch({
                    type: ACTIONS.UPDATE_RESULT,
                    result: err.responseJSON
                });
            }).always(function (whatever) {
                console.log( whatever );
            });
        }
    });

    ReactDOM.render(
        React.createElement(TestHarness, {}, null),
        document.getElementById('testharness-app')
    );


});
