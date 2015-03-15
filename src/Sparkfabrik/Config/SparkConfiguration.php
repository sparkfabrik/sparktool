<?php

namespace Sparkfabrik\Tools\Spark\Config;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

class SparkConfiguration implements ConfigurationInterface
{
  public function getConfigTreeBuilder()
  {
    $treeBuilder = new TreeBuilder();
    $rootNode = $treeBuilder->root('spark');
    $rootNode
      ->children()
        ->arrayNode('services')
          ->children()
            ->arrayNode('redmine_credentials')
              ->children()
                ->scalarNode('redmine_url')->end()
                ->scalarNode('redmine_api_key')->end()
              ->end()
            ->end()
          ->end()
        ->end()
        ->arrayNode('projects')
          ->children()
            ->scalarNode('redmine_project_id')->end()
          ->end()
        ->end()
      ->end()
    ;
    return $treeBuilder;
  }
}
