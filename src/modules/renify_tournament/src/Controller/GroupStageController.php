<?php

namespace Drupal\renify_tournament\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for renify_tournament routes.
 */
class GroupStageController extends ControllerBase
{

    /**
     * The path alias manager.
     *
     * @var \Drupal\path_alias\AliasManagerInterface
     */
    protected $aliasManager;

    /**
     * The controller constructor.
     *
     * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
     *   The path alias manager.
     */
    public function __construct(AliasManagerInterface $alias_manager)
    {
        $this->aliasManager = $alias_manager;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static (
            $container->get('path_alias.manager')
            );
    }

    /**
     * Renders the group stage page.
     */
    public function content()
    {
        // $alias = '/tournaments/' . $tournament_name;
        // $path = $this->aliasManager->getPathByAlias($alias);

        // if (preg_match('/^\/node\/(\d+)$/', $path, $matches)) {
        //     $nid = $matches[1];
        // }
        // else {
        //     // If the alias doesn't start with /tournaments, or we can't find node ID,
        //     // we might want to throw 404 if it's strictly required to be a tournament alias.
        //     // However, for now, I'll just proceed or throw 404 if path is same as alias (not resolved).
        //     if ($path === $alias) {
        //         throw new NotFoundHttpException();
        //     }

        //     // Fallback/extra check for node ID if path is different but doesn't match node/(\d+)
        //     // This might happen if it's another redirect or something, but usually it's /node/N.
        //     $nid = NULL;
        // }
        $nid = 1;
        return [
            '#theme' => 'group_stage',
            '#tournament_id' => $nid,
            '#attached' => [
                'library' => [
                    'renify_tournament/group-stage',
                ],
            ],
        ];
    }

}
