const webpack = require('webpack');
const path = require('path');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const FileManagerPlugin = require('filemanager-webpack-plugin');

const config = {
	entry: {
		'bulkpriceupdater.export': './src/index_export.js',
		'bulkpriceupdater.import': './src/index_import.js'
	},
	output: {
		path: path.resolve(__dirname, 'dist'),
		filename: '[name].min.js',
        chunkFilename: '[name].[hash].js',
        clean: true,
	},
	module: {
		rules: [
			{
				test: /\.js$/,
				use: 'babel-loader',
				exclude: /node_modules/
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
		new BundleAnalyzerPlugin({
			analyzerMode: 'static',
			openAnalyzer: false,
		}),
		new MiniCssExtractPlugin({
            filename: '[name].min.css'
        }),
		new FileManagerPlugin({
            events: {
                onStart: {
                    delete: [
                        { source: '../views/js/*.*', options: { force: true } },
                        { source: '../views/css/*.*', options: { force: true } }
                    ]
                },
                onEnd: {
                    copy: [
                        { source: './dist/*.js', destination: '../views/js' },
                        { source: './dist/*.css', destination: '../views/css' },
                    ]
                }
            }
        })
	]
};

module.exports = (env, argv) => {
    if (argv.mode === 'development') {
        config.devtool = 'eval-source-map';
    }

    if (argv.mode === 'production') {
        config.optimization = {
            minimize: true,
            splitChunks: {
                cacheGroups: {
                    vendor: {
                        test: /[\\/]node_modules[\\/]/,
                        name: 'vendors',
                        filename: "vendor.bundle.js",
                        chunks: 'all'
                    }
                }
            }
        }
    }

    return config;
}