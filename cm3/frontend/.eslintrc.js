module.exports = {
  env: {
    browser: true,
    es2021: true,
  },
  extends: [
    'plugin:vue/essential',
    //'airbnb-base',
  ],
  parserOptions: {
    ecmaVersion: 'latest',
    sourceType: 'module',
  },
  plugins: [
    'vue',
  ],
  rules: {
  },
    overrides: [
        {
            files: ['src/routes/**/*.vue', 'src/views/**/*.vue'],
            rules: {
                'vue/multi-word-component-names': 'off',
            },
        },
    ],
};
