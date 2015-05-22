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
            ->scalarNode('redmine_url')->end()
            ->scalarNode('redmine_api_key')->end()
            ->end()
            ->end()
            ->arrayNode('github')
            ->children()
            ->scalarNode('github_user')->end()
            ->scalarNode('github_repo')->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->arrayNode('projects')
            ->children()
            ->scalarNode('redmine_project_id')->end()
            ->end()
            ->end()
            ->arrayNode('git')
            ->children()
            ->scalarNode('branch_pattern')
            ->defaultValue('%(story_prefix)-%(story_code)_%(issue_id)_%(story_name)')
            ->end()
            ->end()
            ->end()
            ->end();
        return $treeBuilder;
    }
}
