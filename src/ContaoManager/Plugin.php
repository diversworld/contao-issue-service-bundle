<?php
declare(strict_types=1);
namespace Vendor\ContaoIssueServiceBundle\ContaoManager;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Vendor\ContaoIssueServiceBundle\ContaoIssueServiceBundle;
final class Plugin implements BundlePluginInterface, RoutingPluginInterface {
 public function getBundles(ParserInterface $parser): array { return [BundleConfig::create(ContaoIssueServiceBundle::class)]; }
 public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): string { return '@ContaoIssueServiceBundle/config/routes.yaml'; }
}
