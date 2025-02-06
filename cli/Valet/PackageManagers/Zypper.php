<?php

namespace Valet\PackageManagers;

use DomainException;
use Valet\CommandLine;
use Valet\Contracts\PackageManager;

class Zypper implements PackageManager
{
    public $cli;

    const SUPPORTED_PHP_VERSIONS = [
        'php8',
    ];


    /**
     * Create a new Yum instance.
     *
     * @param CommandLine $cli
     * @return void
     */
    public function __construct(CommandLine $cli)
    {
        $this->cli = $cli;
    }

    /**
     * Determine if the given package is installed.
     *
     * @param string $package
     * @return bool
     */
    public function installed($package)
    {
        $query = "zypper search --installed-only {$package} | grep -E '^i[+s]? |^v[+s]?' | awk '{print $3}'";

        $packages = explode(PHP_EOL, trim($this->cli->run($query)));

        return in_array($package, $packages);
    }

    /**
     * Ensure that the given package is installed.
     *
     * @param string $package
     * @return void
     */
    public function ensureInstalled($package)
    {
        if (!$this->installed($package)) {
            $this->installOrFail($package);
        }
    }

    /**
     * Install the given package and throw an exception on failure.
     *
     * @param string $package
     * @return void
     */
    public function installOrFail($package)
    {
        output('<info>[' . $package . '] is not installed, installing it now via Zypper...</info> 🍻');

        $this->cli->run(trim('sudo zypper install -y ' . $package), function ($exitCode, $errorOutput) use ($package) {
            output($errorOutput);

            throw new DomainException('Zypper was unable to install [' . $package . '].');
        });
    }

    /**
     * Configure package manager on valet install.
     *
     * @return void
     */
    public function setup()
    {
        // Nothing to do
    }

    /**
     * Restart dnsmasq in Fedora.
     */
    public function nmRestart($sm)
    {
        $sm->restart('NetworkManager');
    }

    /**
     * Determine if package manager is available on the system.
     *
     * @return bool
     */
    public function isAvailable()
    {
        try {
            $output = $this->cli->run('which zypper', function ($exitCode, $output) {
                throw new DomainException('Zypper not available');
            });

            return !empty(trim($output));
        } catch (DomainException $e) {
            return false;
        }
    }

    public function supportedPhpVersions()
    {
        return collect(static::SUPPORTED_PHP_VERSIONS);
    }
}
