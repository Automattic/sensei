module.exports = {
    "parser": "babel-eslint",
    "env": {
        "browser": true,
        "es6": true
    },
    "globals": {
        "jQuery": true,
        "ajaxurl": true,
        "woo_localized_data": true,
        "wp": true
    },
    "extends": [
        "eslint:recommended",
        "plugin:react/recommended"
    ],
    "parserOptions": {
        "ecmaFeatures": {
            "jsx": true
        }
    },
    "plugins": [
        "react"
    ],
    "settings": {
        "react": {
            "pragma": "wp"
        }
    },
    "rules": {
        "indent": [
            "error",
            "tab"
        ],
        "jsx-quotes": "error",
        "linebreak-style": [
            "error",
            "unix"
        ],
        "quotes": [
            "error",
            "single"
        ],
        "react/display-name": "off",
        "react/jsx-curly-spacing": [ "error", "always" ],
        "react/jsx-equals-spacing": "error",
        "react/jsx-indent": [ "error", "tab" ],
        "react/jsx-indent-props": [ "error", "tab" ],
        "react/jsx-key": "error",
        "react/jsx-tag-spacing": "error",
        "react/no-children-prop": "off",
        "react/no-find-dom-node": "warn",
        "react/prop-types": "off",
        "semi": [
            "error",
            "always"
        ]
    }
};
