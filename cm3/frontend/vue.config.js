module.exports = {
  productionSourceMap: false,
  publicPath: '',
  devServer: {
    proxy: {
      '^/concrescent/': {
        target: 'https://tsaukpaetra.com',
        changeOrigin: true, // so CORS doesn't bite us.
      }
    }
  },

  chainWebpack: config => {
    config
      .plugin('html')
      .tap(args => {
        args[0].template = './src/index.html';
        args[0].title = 'ConCrescent';
        args[0].favicon = './customization/favicon.ico';
        return args
      });
    config
      .plugin('define')
      .tap(args => {
        return args
      });

  }
}
