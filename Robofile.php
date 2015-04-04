<?php

use Symfony\Component\Finder\Finder as Finder;
use Symfony\Component\Yaml\Parser as Parser;

/**
 * This is project's console commands configuration for the Drocker project.
 */
class Robofile extends \Robo\Tasks
{

    /**
     * Build phar and increment version.
     */
    public function build() {
      $this->yell("Releasing Spark...");
      $this->buildPhar();
      $this->buildSemver();
    }

    /**
     * Build the Drocker phar package
     */
    public function buildPhar()
    {
      $yaml = new Parser();
      $packer = $this->taskPackPhar('spark.phar');
      $this->taskComposerInstall()
            ->noDev()
            ->printed(false)
            ->run();

      // Add php sources.
      $files = Finder::create()->ignoreVCS(true)
            ->files()
            ->name('*.php')
            ->path('src')
            ->path('vendor')
            ->notPath('patched-libraries')
            ->notPath('vendor/codegyre')
            ->in(__DIR__);
      foreach ($files as $file) {
        $packer->addFile($file->getRelativePathname(), $file->getRealPath());
      }

      // Executable.
      $packer->addFile('.semver', '.semver');
      $packer->addFile('spark.php', 'spark.php')
             ->executable('spark.php')
             ->run();

      // Semantic version file.
      $this->taskSemVer('.semver')
           ->increment()
           ->run();
      $this->buildSemver();
    }

  /**
   * Increment semantic version file.
   */
    public function buildSemver($increment = 'patch') {
      // Semantic version file.
      $this->taskSemVer('.semver')
           ->increment($increment)
           ->run();
    }
}
