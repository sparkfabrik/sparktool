<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\Finder\Finder as Finder;
use Symfony\Component\Yaml\Parser;
use Robo\Task\Development\SemVer;

/**
 * This is project's console commands configuration for the Drocker project.
 */
class Robofile extends \Robo\Tasks
{

    private function buildGetReleaseVersion() {
      $semver_file = '.semver';
      $semver = new SemVer($semver_file);
      return (string) $semver;
    }

    /**
     * Build phar and increment version.
     */
    public function release() {
      $this->yell("Releasing Spark...");
      // Try to detect git status.
      $status = $this->taskExec('git status')
                      ->arg('-s')
                      ->printed(false)
                      ->run()
                      ->getMessage();
      if (!empty($status)) {
        throw new Exception('Seems that you have some file not yet commited, check your git status.');
      }
      //$this->buildSemver();
      $this->buildGithub();
      $this->buildPhar();
      $this->buildPublish();
    }

    public function buildGithub() {
      $version = (string) new Semver('.semver');
      $this->taskGitHubRelease($version)
                 ->uri('sparkfabrik/sparktool')
                 ->askDescription()
                 ->run();
    }

   /**
    * Publish the package on the release branch.
    */
    public function buildPublish() {
      $version = $this->buildGetReleaseVersion();
      rename('spark.phar', 'spark-release.phar');
      $this->taskGitStack()->checkout('release')->run();
      rename('spark-release.phar', 'spark.phar');
      $release_commit_message = 'Phar: spark ' . $version . ' released';
      $this->taskGitStack()
          ->stopOnFail()
          ->add('spark.phar')
          ->commit($release_commit_message)
          ->push('origin', 'release')
          ->checkout('develop')
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
            ->in(__DIR__);
      foreach ($files as $file) {
        $packer->addFile($file->getRelativePathname(), $file->getRealPath());
      }
      $packer->addFile('.banner.txt', '.banner.txt');

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

      // Commit release version.
      $version = $this->buildGetReleaseVersion();
      $release_commit_message = 'Release: spark ' . $version . ' released';
      $this->taskGitStack()
           ->stopOnFail()
           ->add('.semver')
           ->commit($release_commit_message)
           ->push('origin', 'develop')
           ->run();
    }
}
