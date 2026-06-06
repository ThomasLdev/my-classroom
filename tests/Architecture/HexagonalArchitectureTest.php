<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

/**
 * Enforces the Ports & Adapters dependency direction across every bounded
 * context (Teaching, Scheduling, Shared, ...). Rules use regex namespace
 * selectors so a new context inherits the constraints automatically.
 */
final class HexagonalArchitectureTest
{
    private const string DOMAIN = '#^App\\\\[A-Za-z]+\\\\Domain(\\\\|$)#';

    private const string APPLICATION = '#^App\\\\[A-Za-z]+\\\\Application(\\\\|$)#';

    private const string INFRASTRUCTURE = '#^App\\\\[A-Za-z]+\\\\Infrastructure(\\\\|$)#';

    public function test_domain_does_not_depend_on_outer_layers(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::DOMAIN, true))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace(self::APPLICATION, true),
                Selector::inNamespace(self::INFRASTRUCTURE, true),
            );
    }

    /**
     * The domain must stay framework-agnostic: no Symfony or Doctrine leaking
     * into the core. Persistence mapping belongs to Infrastructure adapters.
     */
    public function test_domain_is_framework_agnostic(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::DOMAIN, true))
            ->shouldNotDependOn()
            ->classes(
                Selector::inNamespace('Symfony'),
                Selector::inNamespace('Doctrine'),
            );
    }

    public function test_application_does_not_depend_on_infrastructure(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace(self::APPLICATION, true))
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace(self::INFRASTRUCTURE, true));
    }

    /**
     * Contexts communicate only through Shared (or exposed Ports), never by
     * reaching into each other's internals.
     */
    public function test_teaching_does_not_depend_on_scheduling(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\\Teaching'))
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('App\\Scheduling'));
    }

    public function test_scheduling_does_not_depend_on_teaching(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\\Scheduling'))
            ->shouldNotDependOn()
            ->classes(Selector::inNamespace('App\\Teaching'));
    }
}
