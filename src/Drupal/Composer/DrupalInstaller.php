<?php

namespace Drupal\Composer;

use Composer\Package\PackageInterface;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Composer;

class DrupalInstaller extends LibraryInstaller
{
    public function __construct(IOInterface $io, Composer $composer)
    {
        parent::__construct($io, $composer);

        $extra = $this->composer->getPackage()->getExtra();
        $extra += array(
            'drupal-libraries' => array(),
            'drupal-modules' => array(),
            'drupal-themes' => array(),
            'drupal-root' => 'core',
        );

        $this->drupalLibraries = $extra['drupal-libraries'] + array(
            'ckeditor/ckeditor' => "",
        );

        $this->drupalModules = $extra['drupal-modules'] + array(
            'drupal/*' => 'contrib',
        );

        $this->drupalThemes = $extra['drupal-themes'] + array(
          'drupal/*' => 'contrib',
        );

        $this->drupalRoot = $extra['drupal-root'];

        $this->cached = array();
    }

    /**
     * {@inheritDoc}
     */
    public function getPackageBasePath(PackageInterface $package)
    {
        $packageName = strtolower($package->getName());

        if (isset($this->cached[$packageName])) {
            return $this->cached[$packageName];
        }

        if ($packageName === 'drupal/drupal') {
            $path = $this->drupalRoot;
        }
        else {
            list($vendor, $name) = explode('/', $packageName);

            $path = '';
            foreach (array('module' => 'drupalModules', 'theme' => 'drupalThemes') as $type => $drupalType) {
                if ($package->getType() === "drupal-$type") {
                    $subdir = "project";
                    foreach (array($packageName, "$vendor/*") as $key) {
                        if (isset($this->{$drupalType}[$key])) {
                            $subdir = $this->{$drupalType}[$key];
                        }
                    }
                    $path = "$this->drupalRoot/sites/all/{$type}s/$subdir/$name";
                }
            }
            if (!$path) {
                foreach (array($packageName, "$vendor/*") as $key) {
                    if (isset($this->drupalLibraries[$key])) {
                        $path = $this->drupalRoot . '/sites/all/libraries/';
                        $path .= empty($this->drupalLibraries[$key]) ? $name : $this->drupalLibraries[$key];
                    }
                }
            }
        }
        if ($path) {
            $this->io->write("<info>Installing $packageName in $path.</info>");
        }
        else {
            $path = parent::getPackageBasePath($package);
        }

        $this->cached[$packageName] = $path;

        return $path;
    }

    /**
     * {@inheritDoc}
     */
    public function supports($packageType)
    {
        return TRUE;
    }
}
