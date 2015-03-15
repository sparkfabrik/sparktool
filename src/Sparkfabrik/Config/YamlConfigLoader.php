<?php

namespace Sparkfabrik\Tools\Spark\Config;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Yaml;

class YamlConfigLoader extends FileLoader
{
  public function load($resource, $type = null)
  {
    $config = Yaml::parse($resource);
    return $config;
  }

  public function supports($resource, $type = null)
  {
    return is_string($resource) && 'yml' === pathinfo(
      $resource,
      PATHINFO_EXTENSION
    );
  }
}
