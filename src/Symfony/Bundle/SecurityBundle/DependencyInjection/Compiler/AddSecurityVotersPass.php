<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Adds all configured security voters to the access decision manager.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AddSecurityVotersPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('security.access.decision_manager')) {
            return;
        }

        $voters = $this->findAndSortTaggedServices('security.voter', $container);
        if (!$voters) {
            throw new LogicException('No security voters found. You need to tag at least one with "security.voter".');
        }

        foreach ($voters as $voter) {
            $class = $container->getDefinition((string) $voter)->getClass();

            if (!is_a($class, VoterInterface::class, true)) {
                throw new LogicException(sprintf('%s must implement the %s when used as a voter.', $class, VoterInterface::class));
            }
        }

        $adm = $container->getDefinition('security.access.decision_manager');
        $adm->replaceArgument(0, new IteratorArgument($voters));
    }
}
