<?php
namespace Grav\Plugin\Console;

use Grav\Common\Config\Config;
use Grav\Console\ConsoleCommand;
use Grav\Common\File\CompiledYamlFile;
use Grav\Common\User\User;
use Grav\Common\Utils;
use Grav\Common\Grav;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * Class HelloCommand
 *
 * @package Grav\Plugin\Console
 */
class UserLoginCommand extends ConsoleCommand
{
   
    /**
     * @var array
     */
    protected $options = [];
    
    
    /**
     * Generates a OTL for a specified existing user.
     */
    protected function configure()
    {
        $this
            ->setName("user-login")
            ->setAliases(['uli'])
            ->setDescription("Generates a OTL for a specified existing user.")
            ->addArgument(
                'user',
                InputArgument::OPTIONAL,
                'The username'
            )
            ->addOption(
                'admin',
                'a',
                InputOption::VALUE_NONE,
                'Will also start an admin session with this user.  This will not grant additional rights beyond what the user already has.',
                null
            )
            ->setHelp('The <info>user-login</info> generates a one-time login URL for an existing user.')
        ;
    }

    /**
     * @return int|null|void
     */
    protected function serve()
    {
        // Start session to generate a new token value each time.
        session_start();
        
        $config = Grav::instance()['config'];
        $param_sep = $config['system']['param_sep'];
        
        // Collects the arguments and options as defined
        $this->options = [
            'user' => $this->input->getArgument('user'),
            'admin' => $this->input->getOption('admin'),
        ];

        $helper = $this->getHelper('question');
        $this->output->writeln('<green>Generating OTL URL</green>');
        $this->output->writeln('');

        if (!$this->options['user']) {
            // Get username and validate.
            $question = new Question('Enter a <yellow>username</yellow>: ');
            $question->setValidator(function ($value) {
                return $this->validate('user', $value);
            });
            $username = $helper->ask($this->input, $this->output, $question);
        } else {
            $username = $this->options['user'];
        }
        
        $token  = md5(uniqid(mt_rand(), true));
        $token_expire = time() + 900; // 15 minutes
        $nonce = Utils::getNonce('admin-form', false);
        
        // Load user object.
        $user = !empty($username) ? User::load($username) : null;
        
        // Set OTL nonce/ expiration.
        $user->otl_nonce = $nonce;
        $user->otl_nonce_expire = $token_expire;
        $user->otl_admin_login = $this->options['admin'];
        
        // Save user object with otl values.
        $user->save();
        
        // Display URL to CLI.
        $base_uri = $config->get('plugins.one-time-login.base_otl_utl') . $config->get('plugins.one-time-login.otl_route') . '/';
        $url = $base_uri . 'user' . $param_sep . $username . '/otl_nonce' . $param_sep . $nonce;
        $this->output->writeln('This OTL URL will expire in fifteen (15) minutes.');
        $this->output->writeln($url);
    }

    
    /**
     * @param        $type
     * @param        $value
     * @param string $extra
     *
     * @return mixed
     */
    protected function validate($type, $value, $extra = '')
    {
        /** @var Config $config */
        $config = Grav::instance()['config'];

        /** @var UniformResourceLocator $locator */
        $locator = Grav::instance()['locator'];
        switch ($type) {
            case 'user':
                if (!file_exists($locator->findResource('account://' . $value . YAML_EXT))) {
                    throw new \RuntimeException('Username "' . $value . '" does not exist. Please pick an existing username');
                }

                break;
        }
        return $value;
    }
}
