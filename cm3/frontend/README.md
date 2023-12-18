# frontend

## Project setup
```
yarn install
```

### Compiles and hot-reloads for development
```
yarn run serve
```

### Compiles and minifies for production
```
yarn run build
```

### Run your tests
```
yarn run test
```

### Lints and fixes files
```
yarn run lint
```

### Customize configuration
See [Configuration Reference](https://cli.vuejs.org/config/).


Getting Yarn:
Get NodeJS 16
`corepack enable`
Yarn should now be available. Update it to stable
`yarn set version stable`

Getting Vue UI (to help manage project)
yarn global add @vue/cli
yarn global add @vue/ui

Add global yarn bin to your path if not already:
Linux: `export PATH="$(yarn global bin):$PATH"`
Windows: `for /f "delims=" %i in ('yarn global bin') do set PATH=%PATH%;%i`
then:
vue ui
