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
        $grav = Grav::instance();
        $config = $grav['config'];
        $param_sep = $config['system']['param_sep'];

        // Collects the arguments and options as defined
        $this->options = [
            'user' => $this->input->getArgument('user'),
        ];

        $helper = $this->getHelper('question');
        $this->output->writeln('<green>Generating OTL URL</green>');
        $this->output->writeln('');

        if (!$this->options['user']) {
            // Get username and validate.
            $question = new Question('Enter a <yellow>username</yellow>: ');
            $question->setValidator(function ($value) {
                return $this->validateUser('user', $value);
            });
            $username = $helper->ask($this->input, $this->output, $question);
        } else {
            $username = $this->options['user'];
            $valid_user = $this->validateUser('user', $username);
        }

        $token  = md5(uniqid(mt_rand(), true));
        $token_expire = time() + 900; // 15 minutes
        $nonce = Utils::getNonce('admin-form', false);

        // Load user object.
        $user = !empty($username) ? $grav['accounts']->load($username) : null;

        // Set OTL nonce/ expiration.
        $otl['nonce'] = $nonce;
        $otl['nonce_expire'] = $token_expire;
        $user->one_time_login = $otl;

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
    protected function validateUser($type, $value, $extra = '')
    {
        $config = Grav::instance()['config'];
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
