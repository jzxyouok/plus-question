<?php

namespace SlimKit\PlusQuestion\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Boorstrap the service provider.
     *
     * @return void
     * @author Seven Du <shiweidu@outlook.com>
     */
    public function boot()
    {
        // Register a database migration path.
        $this->loadMigrationsFrom($this->app->make('path.question.migration'));

        // Register translations.
        $this->loadTranslationsFrom($this->app->make('path.question.lang'), 'plus-question');

        // Register handler singleton.
        $this->registerHandlerSingletions();

        // Publish config file.
        $this->publishes([
            $this->app->make('path.question').'/config/question' => $this->app->configPath('question.php'),
        ], 'config');

        // Publish public resource.
        $this->publishes([
            $this->app->make('path.question.asstes') => $this->app->PublicPath().'/question',
        ], 'public');
    }

    /**
     * Register the service provider.
     *
     * @return void
     * @author Seven Du <shiweidu@outlook.com>
     */
    public function register()
    {
        // Bind all of the package paths in the container.
        $this->app->instance('path.question', $path = dirname(dirname(__DIR__)));
        $this->app->instance('path.question.migration', $path.'/database/migrations');
        $this->app->instance('path.question.asstes', $path.'/asstes');
        $this->app->instance('path.question.lang', $path.'/resource/lang');

        // register config files.
        $this->registerConfigFiles();

        // register cntainer aliases
        $this->registerContainerAliases();

        // Register Plus package handlers.
        $this->registerPackageHandlers();
    }

    /**
     * Register Plus package handlers.
     *
     * @return void
     * @author Seven Du <shiweidu@outlook.com>
     */
    protected function registerHandlerSingletions()
    {
        // Owner handler.
        $this->app->singleton('plus-question:handler', function () {
            return new \SlimKit\PlusQuestion\Handlers\PackageHandler();
        });

        // Develop handler.
        $this->app->singleton('plus-question:dev-handler', function ($app) {
            return new \SlimKit\PlusQuestion\Handlers\DevPackageHandler($app);
        });
    }

    /**
     * Register container aliases.
     *
     * @return void
     * @author Seven Du <shiweidu@outlook.com>
     */
    protected function registerContainerAliases()
    {
        $aliases = [
            'plus-question:handler' => [
                \SlimKit\PlusQuestion\Handlers\PackageHandler::class,
            ],
            'plus-question:dev-handler' => [
                \SlimKit\PlusQuestion\Handlers\DevPackageHandler::class,
            ],
        ];

        foreach ($aliases as $key => $aliases) {
            foreach ($aliases as $key => $alias) {
                $this->app->alias($key, $alias);
            }
        }
    }

    /**
     * Register Plus package handlers.
     *
     * @return void
     * @author Seven Du <shiweidu@outlook.com>
     */
    protected function registerPackageHandlers()
    {
        $this->loadHandleFrom('question', 'plus-question:handler');
        $this->loadHandleFrom('question-dev', 'plus-question:dev-handler');
    }

    /**
     * Register handler.
     *
     * @param string $name
     * @param \Zhiyi\Plus\Support\PackageHandler|string $handler
     * @return void
     * @author Seven Du <shiweidu@outlook.com>
     */
    private function loadHandleFrom(string $name, $handler)
    {
        \Zhiyi\Plus\Support\PackageHandler::loadHandleFrom($name, $handler);
    }

    /**
     * Register config files.
     *
     * @author bs<414606094@qq.com>
     * @return void
     */
    protected function registerConfigFiles()
    {
        $this->mergeConfigFrom(
            $this->app->make('path.question').'/config/question.php', 'question'
        );
    }
}
