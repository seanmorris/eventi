exports.files = {
  javascripts: {joinTo: 'app.js'},
  stylesheets: {joinTo: 'app.css'}
};

exports.hooks  = {};

exports.plugins = {
  babel: {presets: ['es2015']},
  raw: {
	pattern: /\.(html)$/,
	wrapper: content => `module.exports = ${JSON.stringify(content)}`
  }
};

exports.watcher = {
	awaitWriteFinish: true,
	usePolling: true
};
