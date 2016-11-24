<?php

/*
 * This file is part of Bootstrap CMS.
 *
 * (c) Graham Campbell <graham@alt-three.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GrahamCampbell\BootstrapCMS\Providers;

use GrahamCampbell\BootstrapCMS\Http\Controllers\CommentController;
use GrahamCampbell\BootstrapCMS\Navigation\Factory;
use GrahamCampbell\BootstrapCMS\Observers\PageObserver;
use GrahamCampbell\BootstrapCMS\Repositories\CommentRepository;
use GrahamCampbell\BootstrapCMS\Repositories\EventRepository;
use GrahamCampbell\BootstrapCMS\Repositories\PageRepository;
use GrahamCampbell\BootstrapCMS\Repositories\PostRepository;
use GrahamCampbell\BootstrapCMS\Services\MediaService;
use GrahamCampbell\BootstrapCMS\Services\PagesService;
use GrahamCampbell\BootstrapCMS\Services\SocialAccountService;
use GrahamCampbell\BootstrapCMS\Subscribers\CommandSubscriber;
use GrahamCampbell\BootstrapCMS\Subscribers\NavigationSubscriber;
use Illuminate\Support\ServiceProvider;
use GrahamCampbell\BootstrapCMS\Services\CategoriesService;
use GrahamCampbell\BootstrapCMS\Services\ConfigurationsService;
use Illuminate\Support\Facades\Validator;

/**
 * This is the app service provider class.
 *
 * @author Graham Campbell <graham@alt-three.com>
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupBlade();

        $this->setupListeners();

        Validator::extend('name_unique', function ($attribute, $value, $parameters) {
            $configService = new ConfigurationsService();
            $environments = $configService->getEnvironmentsList();

            return !in_array($parameters[0], $environments);
        });

        Validator::extend('ids_array', function ($attribute, $value, $parameters) {
            $requestArray = unserialize($parameters[0]);
            $model = unserialize($parameters[1]);

            $modelData = $model::all();
            $modelData = $modelData->pluck('id')->toArray();

            if (!is_array($requestArray)) {
                return false;
            }

            return count($requestArray) === count(array_intersect($requestArray, $modelData));
        });

        Validator::replacer('name_unique', function ($message) {
            return str_replace($message, 'Config with such name already exists!', $message);
        });

        Validator::replacer('ids_array', function ($message) {
            return str_replace($message, 'Some or all pages you entered don\'t exists!', $message);
        });
    }

    /**
     * Setup the blade compiler class.
     *
     * @return void
     */
    protected function setupBlade()
    {
        $blade = $this->app['view']->getEngineResolver()->resolve('blade')->getCompiler();

        $this->app['view']->share('__navtype', 'default');

        $blade->directive('navtype', function ($expression) {
            return "<?php \$__navtype = {$expression}; ?>";
        });

        $blade->directive('navigation', function () {
            return '<?php echo \GrahamCampbell\BootstrapCMS\Facades\NavigationFactory::make($__navtype); ?>';
        });
    }

    /**
     * Setup the event listeners.
     *
     * @return void
     */
    protected function setupListeners()
    {
        $this->app['events']->subscribe($this->app->make(CommandSubscriber::class));

        $this->app['events']->subscribe($this->app->make(NavigationSubscriber::class));

        $this->app['pagerepository']->observe($this->app->make(PageObserver::class));
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerNavigationFactory();

        $this->registerCommentRepository();
        $this->registerEventRepository();
        $this->registerPageRepository();
        $this->registerPostRepository();

        $this->registerCommandSubscriber();
        $this->registerNavigationSubscriber();

        $this->registerCommentController();
        $this->registerCategoriesService();
        $this->registerPagesService();
        $this->registerConfigurationsService();
        $this->registerMediaService();

        if ($this->app->environment() !== 'production') {
            $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
        }
    }

    /**
     * Register the social account service class.
     *
     * @return void
     */
    protected function registerSocialAccountService()
    {
        $this->app->bind(SocialAccountService::class, function () {
            return new SocialAccountService();
        });

        $this->app->alias('socialuser', 'GrahamCampbell\BootstrapCMS\Services\SocialAccountService');
    }

    /**
     * Register the navigation factory class.
     *
     * @return void
     */
    protected function registerNavigationFactory()
    {
        $this->app->singleton('navfactory', function ($app) {
            $credentials = $app['credentials'];
            $navigation = $app['navigation'];
            $name = $app['config']['app.name'];
            $property = $app['config']['cms.nav'];
            $inverse = $app['config']['theme.inverse'];

            return new Factory($credentials, $navigation, $name, $property, $inverse);
        });

        $this->app->alias('navfactory', 'GrahamCampbell\BootstrapCMS\Navigation\Factory');
    }

    /**
     * Register the comment repository class.
     *
     * @return void
     */
    protected function registerCommentRepository()
    {
        $this->app->singleton('commentrepository', function ($app) {
            $model = $app['config']['cms.comment'];
            $comment = new $model();

            $validator = $app['validator'];

            return new CommentRepository($comment, $validator);
        });

        $this->app->alias('commentrepository', 'GrahamCampbell\BootstrapCMS\Repositories\CommentRepository');
    }

    /**
     * Register category service.
     *
     * @return void
     */
    protected function registerCategoriesService()
    {
        $this->app->bind('GrahamCampbell\BootstrapCMS\Services\CategoriesService', function () {
            return new CategoriesService();
        });
    }

    /**
     * Register pages service.
     *
     * @return void
     */
    protected function registerPagesService()
    {
        $this->app->bind('GrahamCampbell\BootstrapCMS\Services\PagesService', function () {
            return new PagesService();
        });
    }

    /**
     * Register configurations service.
     *
     * @return void
     */
    protected function registerConfigurationsService()
    {
        $this->app->bind('GrahamCampbell\BootstrapCMS\Services\ConfigurationsService', function () {
            return new ConfigurationsService();
        });
    }

    /**
     * Register media service.
     *
     * @return void
     */
    protected function registerMediaService()
    {
        $this->app->bind('GrahamCampbell\BootstrapCMS\Services\MediaService', function () {
            return new MediaService();
        });
    }

    /**
     * Register the event repository class.
     *
     * @return void
     */
    protected function registerEventRepository()
    {
        $this->app->singleton('eventrepository', function ($app) {
            $model = $app['config']['cms.event'];
            $event = new $model();

            $validator = $app['validator'];

            return new EventRepository($event, $validator);
        });

        $this->app->alias('eventrepository', 'GrahamCampbell\BootstrapCMS\Repositories\EventRepository');
    }

    /**
     * Register the page repository class.
     *
     * @return void
     */
    protected function registerPageRepository()
    {
        $this->app->singleton('pagerepository', function ($app) {
            $model = $app['config']['cms.page'];
            $page = new $model();

            $validator = $app['validator'];

            return new PageRepository($page, $validator);
        });

        $this->app->alias('pagerepository', 'GrahamCampbell\BootstrapCMS\Repositories\PageRepository');
    }

    /**
     * Register the post repository class.
     *
     * @return void
     */
    protected function registerPostRepository()
    {
        $this->app->singleton('postrepository', function ($app) {
            $model = $app['config']['cms.post'];
            $post = new $model();

            $validator = $app['validator'];

            return new PostRepository($post, $validator);
        });

        $this->app->alias('postrepository', 'GrahamCampbell\BootstrapCMS\Repositories\PostRepository');
    }

    /**
     * Register the command subscriber class.
     *
     * @return void
     */
    protected function registerCommandSubscriber()
    {
        $this->app->singleton('GrahamCampbell\BootstrapCMS\Subscribers\CommandSubscriber', function ($app) {
            $pagerepository = $app['pagerepository'];

            return new CommandSubscriber($pagerepository);
        });
    }

    /**
     * Register the navigation subscriber class.
     *
     * @return void
     */
    protected function registerNavigationSubscriber()
    {
        $this->app->singleton('GrahamCampbell\BootstrapCMS\Subscribers\NavigationSubscriber', function ($app) {
            $navigation = $app['navigation'];
            $credentials = $app['credentials'];
            $pagerepository = $app['pagerepository'];
            $blogging = $app['config']['cms.blogging'];
            $events = $app['config']['cms.events'];
            $cloudflare = class_exists('GrahamCampbell\CloudFlare\CloudFlareServiceProvider');

            return new NavigationSubscriber(
                $navigation,
                $credentials,
                $pagerepository,
                $blogging,
                $events,
                $cloudflare
            );
        });
    }

    /**
     * Register the comment controller class.
     *
     * @return void
     */
    protected function registerCommentController()
    {
        $this->app->bind('GrahamCampbell\BootstrapCMS\Http\Controllers\CommentController', function ($app) {
            $throttler = $app['throttle']->get($app['request'], 1, 10);

            return new CommentController($throttler);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            'navfactory',
            'commentrepository',
            'eventrepository',
            'fileprovider',
            'folderprovider',
            'pagerepository',
            'postrepository',
            'socialuser'
        ];
    }
}
