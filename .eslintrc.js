module.exports = {
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
    "extends": "eslint:recommended",
    "rules": {
        "indent": [
            "error",
            "tab"
        ],
        "linebreak-style": [
            "error",
            "unix"
        ],
        "quotes": [
            "error",
            "single"
        ],
        "semi": [
            "error",
            "always"
        ]
    }
};