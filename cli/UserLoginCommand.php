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
     * Greets a person with or without yelling
     */
    protected function configure()
    {
        $this
            ->setName("user-login")
            ->setAliases(['uli'])
            ->setDescription("Greets a person.")
            ->addOption(
                'user',
                'u',
                InputOption::VALUE_REQUIRED,
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
        //$param_sep = $this->grav['config']->get('system.param_sep', ':');
        $param_sep = ":";
        // Collects the arguments and options as defined
        $this->options = [
            'user'        => $this->input->getOption('user'),
        ];
        
        $this->validateOptions();
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
        $expire = time() + 604800; // next week
        $nonce = Utils::getNonce('admin-form', false);
       
        
        // Load user object.
        $user = !empty($username) ? User::load($username) : null;
        // Set reset token/ expiration.
        $user->reset = $token . '::' . $expire;
        // Save user object with reset values.
        $user->save();
        
        
        $url = 'http://default/admin/reset/task' . $param_sep . 'reset/user' . $param_sep . $username . '/token' . $param_sep . $token . '/admin-nonce' . $param_sep . $nonce;
        $this->output->writeln('This OTL URL will expire in two weeks:');
        $this->output->writeln($url);
    }
    
    /**
     * Performs validation on each CLI command option.
     */
    protected function validateOptions()
    {
        foreach (array_filter($this->options) as $type => $value) {
            $this->validate($type, $value);
        }
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
