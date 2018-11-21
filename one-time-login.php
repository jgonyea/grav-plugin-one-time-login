<?php
namespace Grav\Plugin;

use Grav\Common\Plugin;
use Grav\Common\Utils;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use Grav\Common\Page\Pages;
use Grav\Common\User\User;
use RocketTheme\Toolbox\Event\Event;
use Grav\Plugin\AdminPlugin;
use Grav\Plugin\Admin\AdminController;
use RocketTheme\Toolbox\Session\Session;

/**
 * Class OneTimeLoginPlugin
 * @package Grav\Plugin
 */
class OneTimeLoginPlugin extends Plugin
{
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
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }
        $uri = $this->grav['uri'];
        
        $path = $uri->path();
        $this->route = $this->config->get('plugins.one-time-login.otl_route');

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
        $user = !empty($username) ? User::load($username) : null;
        if (empty($user) || !$user->otl_nonce) {
            $this->grav['log']->info("Invalid OTL URL.");
            $this->grav['messages']->add('Invalid OTL URL.', 'error');
        }
        if ($user) {
            $otl_nonce_expire = $user->otl_nonce_expire;

            if (($user->otl_nonce == $otl_nonce) && (time() < $otl_nonce_expire)) {
                // Remove OTL user entries.
                unset($user->otl_nonce);
                unset($user->otl_nonce_expire);
                unset($user->otl_admin_logon);
                $user->save();
                
                $user->authenticated = true;
                $user->authorized = $user->authorize('admin.login');
                        
                // Authenticate user to website.
                $this->grav['session']->user = $user;
                unset($this->grav['user']);
                $this->grav['user'] = $user;
                $this->redirect = "/";
            } else {
                $this->grav['messages']->add('Invalid OTL URL.', 'error');
                $this->grav['log']->info("Invalid OTL URL.");
            }
        }
        // Redirect.
        $this->grav->redirect($this->redirect);
    }
}
