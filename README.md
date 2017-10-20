# One Time Login Plugin

The **One Time Login** Plugin is for [Grav CMS](http://github.com/getgrav/grav). It generates a one-time login URL to automatically authenticate as an existing user.

## Installation

Installing the One Time Login plugin can be done in one of two ways. The GPM (Grav Package Manager) installation method enables you to quickly and easily install the plugin with a simple terminal command, while the manual method enables you to do so via a zip file.

### GPM Installation (Preferred)

The simplest way to install this plugin is via the [Grav Package Manager (GPM)](http://learn.getgrav.org/advanced/grav-gpm) through your system's terminal (also called the command line).  From the root of your Grav install type:

    bin/gpm install one-time-login

This will install the One Time Login plugin into your `/user/plugins` directory within Grav. Its files can be found under `/your/site/grav/user/plugins/one-time-login`.

### Manual Installation

To install this plugin, just download the zip version of this repository and unzip it under `/your/site/grav/user/plugins`. Then, rename the folder to `one-time-login`. You can find these files on [GitHub](https://github.com/jgonyea/grav-plugin-one-time-login) or via [GetGrav.org](http://getgrav.org/downloads/plugins#extras).

You should now have all the plugin files under

    /your/site/grav/user/plugins/one-time-login
	
> NOTE: This plugin is a modular component for Grav which requires the following:
* [Admin](https://github.com/getgrav/grav-plugin-admin)
* [Grav](http://github.com/getgrav/grav)
* [Error](https://github.com/getgrav/grav-plugin-error)
* [Problems](https://github.com/getgrav/grav-plugin-problems)

## Configuration

Before configuring this plugin, you should copy the `user/plugins/one-time-login/one-time-login.yaml` to `user/config/plugins/one-time-login.yaml` and only edit that copy.

Here is the default configuration and an explanation of available options:

```yaml
enabled: true
```
_Defaults plugin to **enabled** after installation_


## Usage

From the commandline, you can generate one-time login URL's.

`$ bin/plugin one-time-login user-login [username]`

or

`$ bin/plugin one-time-login uli [username]`


Paste the URL into a browser to authenticate as that user.


## Credits



## To Do

- [ ] Have the URL fully authenticate to both site and admin logins, not just the site.
- [ ] Better theme for otl.html.twig.
