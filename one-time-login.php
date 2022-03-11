<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Page\Page;


/**
 * Class OneTimeLoginPlugin
 * @package Grav\Plugin
 */
class OneTimeLoginPlugin extends Plugin
{
    /**
     * Custom route for the OTL page.
     */
    protected $route;
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 1],
            'onTwigTemplatePaths'  => ['onTwigTemplatePaths', 1],
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        $this->route = $this->config->get('plugins.one-time-login.otl_route');
        if($this->isAdmin()){
            $this->notify_otl_route();
        }

        $path = $this->grav['uri']->path();

        // Don't proceed if we aren't at the OTL page.
        if ($path !== $this->route) {
            return;
        }

        $this->enable([
           'onPagesInitialized' => ['addOtlPage', 0],
        ]);
        $this->authenticateOtl();
    }

    /**
     * Add twig paths to plugin templates.
     */
    public function onTwigTemplatePaths()
    {
        $twig = $this->grav['twig'];
        $twig->twig_paths[] = __DIR__ . '/templates';
    }

    /**
     * Add OTL page.
     */
    public function addOtlPage()
    {
        $route = $this->config->get('plugins.one-time-login.otl_route');

        $uri = $this->grav['uri'];
        $pages = $this->grav['pages'];
        $page = $pages->dispatch($route);

        if (!$page) {
            // Only add OTL page if it hasn't already been defined.
            $page = new Page;
            $page->init(new \SplFileInfo(__DIR__ . "/pages/otl.md"));
            $page->slug(basename($route));

            $pages->addPage($page, $route);
        }
    }

    /**
     * Authenticate user via uri and params.
     */
    private function authenticateOtl()
    {
        $username = $this->grav['uri']->param('user');
        $otl_nonce = $this->grav['uri']->param('otl_nonce');
        $this->redirect = "/";

        // Load user object.
        $user = !empty($username) ? $this->grav['accounts']->load($username) : null;

        if (empty($user) || !$user->one_time_login) {
            $this->grav['log']->info("Invalid OTL URL.");
            $this->grav['messages']->add('Invalid OTL URL.', 'error');
        } else {
            if (($user->one_time_login['nonce'] == $otl_nonce) && (time() < $user->one_time_login['nonce_expire'])) {
                // Remove OTL user entries.
                unset($user->one_time_login);
                $user->save();

                $user->authenticated = true;
                $user->authorized = $user->authorize('admin.login');

                // Authenticate user to website.
                $this->grav['session']->user = $user;

                unset($this->grav['user']);
                $this->grav['user'] = $user;

                $this->grav['session']->user = $user;
                $user->authenticated = true;
                $user->authorized = $user->authorize('admin.login') ?? false;


                $this->redirect = "/admin/accounts/users/" . $username;
                $this->grav['messages']->add('You have signed in with your one-time-login.  Please change your password.', 'notice');
                $this->grav['messages']->add('Please change your password below.', 'info');
            } else {
                $this->grav['messages']->add('Invalid OTL URL.', 'error');
                $this->grav['log']->info("Invalid OTL URL.");
            }
        }
        // Redirect.
        $this->grav->redirect($this->redirect);
    }

    /**
     * Fixes custom OTL routes.
     *
     * @return void
     */
    private function notify_otl_route():void {
        $route = explode("/", $this->route);
        if ($route[1] != 'admin') {
            $this->grav['messages']->add('<a href="/admin/plugins/one-time-login">Invalid OTL configuration for "One-Time-Login Route" (must start with "/admin").  Click here to fix</a>', 'error');
        }
    }
}
