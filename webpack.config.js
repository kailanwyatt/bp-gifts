const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

module.exports = (env, argv) => {
  const isProduction = argv.mode === 'production';
  
  return {
    entry: {
      'bp-gifts': './assets/src/js/main.js',
      'bp-gifts-styles': './assets/src/css/main.css',
      'bp-gifts-admin': './assets/src/css/admin.css',
      'bp-gifts-admin-mycred': './assets/src/css/admin-mycred.css'
    },
    
    output: {
      path: path.resolve(__dirname, 'assets/dist'),
      filename: isProduction ? '[name].min.js' : '[name].js',
      clean: true
    },
    
    module: {
      rules: [
        {
          test: /\.js$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: ['@babel/preset-env']
            }
          }
        },
        {
          test: /\.css$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader'
          ]
        },
        {
          test: /\.scss$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            'sass-loader'
          ]
        }
      ]
    },
    
    plugins: [
      new MiniCssExtractPlugin({
        filename: isProduction ? '[name].min.css' : '[name].css'
      })
    ],
    
    optimization: {
      minimize: isProduction,
      minimizer: [
        new TerserPlugin({
          terserOptions: {
            format: {
              comments: false,
            },
          },
          extractComments: false,
        }),
        new CssMinimizerPlugin()
      ]
    },
    
    externals: {
      jquery: 'jQuery'
    },
    
    devtool: isProduction ? false : 'source-map',
    
    stats: {
      colors: true,
      modules: false,
      children: false,
      chunks: false,
      chunkModules: false
    }
  };
};