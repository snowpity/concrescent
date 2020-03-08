module.exports = {
  productionSourceMap: false,
  publicPath: ''
}  chainWebpack: config => {
    config
      .plugin('html')
      .tap(args => {
        args[0].template = './src/index.html';
        args[0].title = 'ConCrescent';
        args[0].favicon = './customization/favicon.ico';
        return args
      });

  }
}
