<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Portal;

use Portal\Controller;
use Portal\Controller\UserRelatedControllerFactory;
use Zend\Router\Http\Literal;
use Zend\Router\Http\Method;
use Zend\Router\Http\Segment;
use Zend\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'profile' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/profile',
                    'defaults' => [
                        'controller' => Controller\ProfileController::class,
                        'action' => 'view',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'view_profile' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/',
                            'defaults' => [
                                'controller' => Controller\ProfileController::class,
                                'action' => 'view',
                            ],
                        ],
                    ],
                    'edit_profile' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/edit',
                        ],
                        'may_terminate' => false,
                        'child_routes' => [
                            'form_profile' => [
                                'type' => Method::class,
                                'options' => [
                                    'verb' => 'get',
                                    'defaults' => [
                                        'action' => 'edit',
                                    ],
                                ],
                            ],
                            'submit_profile' => [
                                'type' => Method::class,
                                'options' => [
                                    'verb' => 'post',
                                    'defaults' => [
                                        'action' => 'submit',
                                    ]
                                ]
                            ]
                        ],
                    ],
                ],
            ],
            'admin' => [
                'type' => Literal::class,
                'options' => [
                    'route' => '/admin',
                    'defaults' => [
                        'controller' => Controller\AdminController::class,
                        'action' => 'index',
                    ],
                ],
                'may_terminate' => true,
                'child_routes' => [
                    'dbtools' => [
                        'type' => Literal::class,
                        'options' => [
                            'route' => '/db',
                            'defaults' => [
                                'needsDatabase' => false,
                            ],
                        ],
                        'may_terminate' => true,
                        'child_routes' => [
                            'runmigrations' => [
                                'type' => Literal::class,
                                'options' => [
                                'route' => '/runmigrations',
                                    'defaults' => [
                                        'needsDatabase' => false,
                                        'action' => 'runmigrations',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'application' => [
                'type'    => Segment::class,
                'options' => [
                    'route'    => '/application[/:action]',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => UserRelatedControllerFactory::class,
            Controller\AdminController::class => Controller\AdminControllerFactory::class,
            Controller\ProfileController::class => InvokableFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/portal/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'view_helper_config' => [
        'flashmessenger' => [
            'message_open_format' => '<div %s>',
            'message_separator_string' => '<button type="button", class="close" data-dismis="alert" aria-hidden="true">&times;</button></div><div $s>',
            'message_close_string' => '<button type="button", class="close" data-dismiss="alert" aria-hidden="true">&times;</button></div>',
        ]
    ],
];
