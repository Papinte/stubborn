const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')
    .addEntry('app', './assets/app.js')
    .addEntry('bootstrap', './assets/bootstrap.js')
    .addStyleEntry('home', './assets/styles/home/home.css')
    .addStyleEntry('login', './assets/styles/login/login.css')
    .addStyleEntry('registration', './assets/styles/registration/registration.css')
    .addStyleEntry('products', './assets/styles/products/products.css')
    .addStyleEntry('product', './assets/styles/product/product.css')
    .addStyleEntry('cart', './assets/styles/cart/cart.css')
    .addStyleEntry('admin', './assets/styles/admin/admin.css')
    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.38';
    });

module.exports = Encore.getWebpackConfig();