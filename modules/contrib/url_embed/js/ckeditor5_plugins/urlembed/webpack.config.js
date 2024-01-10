const path = require('path');
const fs = require('fs');
const webpack = require('webpack');
const { styles, builds } = require('@ckeditor/ckeditor5-dev-utils');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = {
  // https://webpack.js.org/configuration/entry-context/
  entry: './src/index.js',
  // https://webpack.js.org/configuration/output/
  output: {
    path: path.resolve(__dirname, './build'),
    filename: 'urlembed.js',
    library: ['CKEditor5', 'urlembed'],
    libraryTarget: 'umd',
    libraryExport: 'default',
  },
  mode: 'production',
  optimization: {
    minimize: true,
    minimizer: [
      new TerserPlugin({
        terserOptions: {
          format: {
            comments: false,
          },
        },
        test: /\.js(\?.*)?$/i,
        extractComments: false,
      }),
    ],
    moduleIds: 'named',
  },
  plugins: [
    new webpack.DllReferencePlugin({
      manifest: require('ckeditor5/build/ckeditor5-dll.manifest.json'), // eslint-disable-line global-require, import/no-unresolved
      scope: 'ckeditor5/src',
      name: 'CKEditor5.dll',
    }),
  ],
  module: {
    rules: [
      {
        test: /[^/\\]+\.svg$/,
        use: ["raw-loader"]
      },
      {
        test: /theme[/\\].+\.css$/,
        use: [
          {
            loader: "style-loader",
            options: {
              injectType: "singletonStyleTag",
              attributes: {
                "data-cke": true
              }
            }
          }
        ]
      }],
  },
};
