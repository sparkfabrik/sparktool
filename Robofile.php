<?php

use Symfony\Component\Finder\Finder as Finder;
use Symfony\Component\Yaml\Parser as Parser;
use Robo\Task\Development\SemVer;

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
      $this->buildPublish();
      $this->buildSemver();
    }

   /**
    * Publish the package on the release branch.
    */
    public function buildPublish() {
      $semver_file = '.semver';
      $semver = new SemVer($semver_file);
      rename('spark.phar', 'spark-release.phar');
      $this->taskGitStack()->checkout('release')->run();
      rename('spark-release.phar', 'spark.phar');
      $release_commit_message = 'spark ' . (string) $semver . ' released';
      $this->taskGitStack()
          ->add('spark.phar')
          ->commit($release_commit_message)
          ->push('origin', 'release')
          ->checkout('develop')
          ->run();
       $this->taskGitStack()
          ->add('.semver')
          ->commit($release_commit_message)
          ->push('origin', 'develop')
          ->run();
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
