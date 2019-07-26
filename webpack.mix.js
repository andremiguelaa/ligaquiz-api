const mix = require('laravel-mix');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
const CopyWebpackPlugin = require('copy-webpack-plugin');
const ImageminPlugin = require('imagemin-webpack-plugin').default;
const imageminMozjpeg = require('imagemin-mozjpeg');

mix.webpackConfig({
  plugins: [
    new CopyWebpackPlugin([
      {
        from: 'resources/img',
        to: 'img'
      }
    ]),
    new ImageminPlugin({
      test: /\.(jpe?g|png|gif|svg)$/i,
      disable: process.env.NODE_ENV !== 'production',
      optipng: {
        optimizationLevel: 3
      },
      gifsicle: {
        optimizationLevel: 3
      },
      jpegtran: null,
      svgo: {
        removeUnknownsAndDefaults: false,
        cleanupIDs: false
      },
      pngquant: {
        quality: '65-90',
        speed: 4
      },
      plugins: [
        imageminMozjpeg({
          quality: 85,
          progressive: true
        })
      ]
    }),
    new BundleAnalyzerPlugin({
      analyzerMode:
        process.env.npm_lifecycle_event == 'watch' || process.env.npm_lifecycle_event == 'hot'
          ? 'server'
          : 'disabled'
    })
  ]
});

mix.react('resources/js/app.js', 'public/js');
mix.sass('resources/sass/app.scss', 'public/css');

if (mix.inProduction) {
  mix.version();
}

mix.browserSync({
  proxy: 'ligaquiz-v4.test'
});
