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
                    ->isRequired()
                    ->children()
                        ->arrayNode('redmine_credentials')
                            ->isRequired()
                            ->children()
                                ->scalarNode('redmine_url')
                                    ->info('Please fill in the value with a valid redmine url')
                                    ->end()
                                ->scalarNode('redmine_api_key')
                                    ->info('Please fill in the value with a valid redmine api key that can be found at [redmine_url]/my/account')
                                    ->end()
                            ->end()
                        ->end()
                        ->arrayNode('gitlab_credentials')
                            ->isRequired()
                            ->children()
                                ->scalarNode('gitlab_url')
                                    ->end()
                                ->scalarNode('gitlab_token')
                                    ->end()
                            ->end()
                        ->end()
                        ->arrayNode('github_credentials')
                            ->isRequired()
                            ->children()
                                ->scalarNode('github_user')
                                    ->info('Please fill in the value with a valid github user')
                                    ->end()
                                ->scalarNode('github_repo')
                                    ->info('Please fill in the value with a valid github repository that can be found at [github_user]/[github_repo]')
                                    ->end()
                                ->scalarNode('github_token')
                                    ->info('Please fill in the value with a valid github token if you need to authenticate')
                                    ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('projects')
                    ->isRequired()
                    ->children()
                        ->scalarNode('redmine_project_id')
                        ->end()
                    ->end()
                    ->children()
                        ->scalarNode('gitlab_project_id')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('git')
                    ->isRequired()
                    ->children()
                        ->scalarNode('branch_pattern')
                        ->cannotBeEmpty()
                        ->info('Please fill in the value with a valid branch pattern')
                        ->defaultValue('%(story)_%(issue_id)_%(story_name)')
                        ->end()
                        ->scalarNode('commit_pattern')
                        ->cannotBeEmpty()
                        ->info('Please fill in the value with a valid pattern for the commit message')
                        ->defaultValue('refs #%(issue_id): %(story) - %(story_name):')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('configuration')
                    ->isRequired()
                    ->children()
                        ->scalarNode('redmine_output_fields')
                            ->isRequired()
                            ->defaultValue('id|ID,project|Project,created_on|Created,updated_on|Updated,tracker|Traker,fixed_version|Version,author|Author,assigned_to|Assigned,status|Status,category|Category,estimated_hours|Estimated,subject|Subject')
                    ->end()
                ->end();
        return $treeBuilder;
    }
}
