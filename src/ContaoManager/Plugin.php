<?php

declare(strict_types=1);

namespace Diversworld\ContaoIssueServiceBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;
use Diversworld\ContaoIssueServiceBundle\ContaoIssueServiceBundle;

final class Plugin implements BundlePluginInterface, RoutingPluginInterface {

    public function getBundles(ParserInterface $parser): array 
    { 
        return [
            BundleConfig::create(ContaoIssueServiceBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ]; 
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        $file = __DIR__.'/../../config/routes.yaml';
        $loader = $resolver->resolve($file);

        return false === $loader ? null : $loader->load($file);
    }
}

if (!class_exists('Vendor\\ContaoIssueServiceBundle\\ContaoManager\\Plugin', false)) {
    class_alias(Plugin::class, 'Vendor\\ContaoIssueServiceBundle\\ContaoManager\\Plugin');
}
