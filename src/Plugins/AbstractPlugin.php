<?php

/**
 * TechDivision\Import\Plugins\AbstractPlugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * PHP version 5
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import
 * @link      http://www.techdivision.com
 */

namespace TechDivision\Import\Plugins;

use TechDivision\Import\Utils\LoggerKeys;
use TechDivision\Import\ApplicationInterface;
use TechDivision\Import\Configuration\PluginConfigurationInterface;

/**
 * Abstract plugin implementation.
 *
 * @author    Tim Wagner <t.wagner@techdivision.com>
 * @copyright 2016 TechDivision GmbH <info@techdivision.com>
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * @link      https://github.com/techdivision/import
 * @link      http://www.techdivision.com
 */
abstract class AbstractPlugin implements PluginInterface
{

    /**
     * The appliation instance.
     *
     * @var \TechDivision\Import\ApplicationInterface
     */
    protected $application;

    /**
     * The plugin configuration instance.
     *
     * @var \TechDivision\Import\Configuration\PluginConfigurationInterface
     */
    protected $pluginConfiguration;

    /**
     * Initializes the plugin with the application instance.
     *
     * @param \TechDivision\Import\ApplicationInterface                       $application         The application instance
     * @param \TechDivision\Import\Configuration\PluginConfigurationInterface $pluginConfiguration The plugin configuration instance
     */
    public function __construct(
        ApplicationInterface $application,
        PluginConfigurationInterface $pluginConfiguration
    ) {
        $this->application = $application;
        $this->pluginConfiguration = $pluginConfiguration;
    }

    /**
     * Return's the application instance.
     *
     * @return \TechDivision\Import\ApplicationInterface The application instance
     */
    protected function getApplication()
    {
        return $this->application;
    }

    /**
     * Return's the plugin configuration instance.
     *
     * @return \TechDivision\Import\Configuration\PluginConfigurationInterface The plugin configuration instance
     */
    protected function getPluginConfiguration()
    {
        return $this->pluginConfiguration;
    }

    /**
     * Return's the RegistryProcessor instance to handle the running threads.
     *
     * @return \TechDivision\Import\Services\RegistryProcessor The registry processor instance
     */
    protected function getRegistryProcessor()
    {
        return $this->getApplication()->getRegistryProcessor();
    }

    /**
     * Return's the import processor instance.
     *
     * @return \TechDivision\Import\Services\ImportProcessorInterface The import processor instance
     */
    protected function getImportProcessor()
    {
        return $this->getApplication()->getImportProcessor();
    }

    /**
     * Return's the unique serial for this import process.
     *
     * @return string The unique serial
     */
    protected function getSerial()
    {
        return $this->getApplication()->getSerial();
    }

    /**
     * Return's the logger with the passed name, by default the system logger.
     *
     * @param string $name The name of the requested system logger
     *
     * @return \Psr\Log\LoggerInterface The logger instance
     * @throws \Exception Is thrown, if the requested logger is NOT available
     */
    protected function getSystemLogger($name = LoggerKeys::SYSTEM)
    {
        return $this->getApplication()->getSystemLogger($name);
    }

    /**
     * Query whether or not the system logger with the passed name is available.
     *
     * @param string $name The name of the requested system logger
     *
     * @return boolean TRUE if the logger with the passed name exists, else FALSE
     */
    protected function hasSystemLogger($name = LoggerKeys::SYSTEM)
    {
        return $this->getApplication()->hasSystemLogger($name);
    }

    /**
     * Return's the array with the system logger instances.
     *
     * @return array The logger instance
     */
    protected function getSystemLoggers()
    {
        return $this->getApplication()->getSystemLoggers();
    }

    /**
     * Persist the UUID of the actual import process to the PID file.
     *
     * @return void
     * @throws \Exception Is thrown, if the PID can not be added
     */
    protected function lock()
    {
        $this->getApplication()->lock();
    }

    /**
     * Remove's the UUID of the actual import process from the PID file.
     *
     * @return void
     * @throws \Exception Is thrown, if the PID can not be removed
     */
    protected function unlock()
    {
        $this->getApplication()->unlock();
    }

    /**
     * Remove's the passed line from the file with the passed name.
     *
     * @param string $line     The line to be removed
     * @param string $filename The name of the file the line has to be removed
     *
     * @return void
     * @throws \Exception Is thrown, if the file doesn't exists, the line is not found or can not be removed
     */
    protected function removeLineFromFile($line, $filename)
    {
        $this->getApplication()->removeLineFromFile($line, $filename);
    }

    /**
     * Return's the system configuration.
     *
     * @return \TechDivision\Import\ConfigurationInterface The system configuration
     */
    protected function getConfiguration()
    {
        return $this->getApplication()->getConfiguration();
    }

    /**
     * Return's the PID filename to use.
     *
     * @return string The PID filename
     */
    protected function getPidFilename()
    {
        return $this->getConfiguration()->getPidFilename();
    }

    /**
     * Return's the source directory that has to be watched for new files.
     *
     * @return string The source directory
     */
    protected function getSourceDir()
    {
        return $this->getConfiguration()->getSourceDir();
    }

    /**
     * Removes the passed directory recursively.
     *
     * @param string $src Name of the directory to remove
     *
     * @return void
     * @throws \Exception Is thrown, if the directory can not be removed
     */
    protected function removeDir($src)
    {

        // open the directory
        $dir = opendir($src);

        // remove files/folders recursively
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $src . '/' . $file;
                if (is_dir($full)) {
                    $this->removeDir($full);
                } else {
                    if (!unlink($full)) {
                        throw new \Exception(sprintf('Can\'t remove file %s', $full));
                    }
                }
            }
        }

        // close handle and remove directory itself
        closedir($dir);
        if (!rmdir($src)) {
            throw new \Exception(sprintf('Can\'t remove directory %s', $src));
        }
    }

    /**
     * Return's the configured swift mailer instance.
     *
     * @return \Swift_Mailer|null The mailer instance
     */
    protected function getSwiftMailer()
    {

        // the swift mailer configuration
        if ($swiftMailerConfiguration = $this->getPluginConfiguration()->getSwiftMailer()) {
            // load the factory that creates the swift mailer instance
            $factory = $swiftMailerConfiguration->getFactory();
            // create the swift mailer instance
            return $factory::factory($swiftMailerConfiguration);
        }
    }
}