<?php

namespace SpaceSpell\ElasticApmBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @link http://symfony.com/doc/current/cookbook/bundles/extension.html
 */
class ElasticApmExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter("elastic_apm.enabled", $config['enabled']);

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');

        $agentConfig = $config['agent'];
        $agentConfig['enabled'] = $config['enabled'];
        $elasticApmAgentDefinition = $container->getDefinition('elastic_apm.agent');
        $elasticApmAgentDefinition->replaceArgument(0, $agentConfig);

        $requestListenerDefinition = $container->getDefinition('elastic_apm.listener.request');
        $exceptionListenerDefinition = $container->getDefinition('elastic_apm.listener.exception');

        if ($transactionConfig = $config['transactions']) {
            if ($transactionConfig['exclude']) {
                $requestListenerDefinition->addMethodCall('setExclude', [$transactionConfig['exclude']]);
            }

            if ($transactionConfig['include']) {
                $requestListenerDefinition->addMethodCall('setInclude', [$transactionConfig['include']]);

                if ($transactionConfig['exclude']) {
                    @trigger_error('The "transactions.exclude" option is ignored in when "transactions.include" was set.', E_USER_NOTICE);
                }
            }
        }

        if ($exceptionConfig = $config['exceptions']) {
            if ($exceptionConfig['exclude']) {
                $exceptionListenerDefinition->addMethodCall('setExclude', [$exceptionConfig['exclude']]);
            }

            if ($exceptionConfig['include']) {
                $exceptionListenerDefinition->addMethodCall('setInclude', [$exceptionConfig['include']]);

                if ($exceptionConfig['exclude']) {
                    @trigger_error('The "exceptions.exclude" option is ignored in when "exceptions.include" was set.', E_USER_NOTICE);
                }
            }
        }
    }
}
