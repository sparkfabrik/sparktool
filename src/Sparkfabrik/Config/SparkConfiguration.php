<?php

/*
 * This file is part of the Spark tool package.
 *
 * (c) Paolo Mainardi <paolo.mainardi@sparkfabrik.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


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
                                ->scalarNode('redmine_url')
                                ->cannotBeEmpty()
                                ->info('Please fill in the value with a valid redmine url')
                                ->end()
                                ->scalarNode('redmine_api_key')
                                ->cannotBeEmpty()
                                ->info('Please fill in the value with a valid redmine api key that can be found at [redmine_url]/my/account')
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('gitlab_credentials')
                          ->children()
                            ->scalarNode('gitlab_url')->end()
                            ->scalarNode('gitlab_token')->end()
                          ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('projects')
                    ->children()
                        ->scalarNode('redmine_project_id')->end()
                    ->end()
                    ->children()
                      ->scalarNode('gitlab_project_id')->end()
                    ->end()
                ->end()
                ->arrayNode('git')
                    ->children()
                        ->scalarNode('branch_pattern')
                        ->cannotBeEmpty()
                        ->info('Please fill in the value with a valid branch pattern')
                        ->defaultValue('%(story)_%(issue_id)_%(story_name)')
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $treeBuilder;
    }
}
