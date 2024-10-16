<?php
declare(strict_types=1);

/**
 * BEdita, API-first content management framework
 * Copyright 2016 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\API\Test\TestCase\Controller\Component;

use BEdita\API\Controller\Component\JsonApiComponent;
use BEdita\API\Network\Exception\UnsupportedMediaTypeException;
use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Event\Event;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\TestSuite\TestCase;

/**
 * @coversDefaultClass \BEdita\API\Controller\Component\JsonApiComponent
 */
class JsonApiComponentTest extends TestCase
{
    /**
     * Fixtures.
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.BEdita/Core.ObjectTypes',
        'plugin.BEdita/Core.Relations',
        'plugin.BEdita/Core.RelationTypes',
        'plugin.BEdita/Core.Objects',
        'plugin.BEdita/Core.Profiles',
        'plugin.BEdita/Core.Users',
        'plugin.BEdita/Core.Roles',
        'plugin.BEdita/Core.RolesUsers',
    ];

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        Router::fullBaseUrl('http://example.org');
        $this->loadPlugins(['BEdita/API' => ['routes' => true]]);
    }

    /**
     * Data provider for `testInitialize` test case.
     *
     * @return array
     */
    public function initializeProvider()
    {
        return [
            'default' => [
                'application/vnd.api+json',
                [],
            ],
            'json' => [
                'application/json',
                [
                    'contentType' => 'application/json',
                ],
            ],
        ];
    }

    /**
     * Test component initialization.
     *
     * @param string $expectedMimeType Expected response MIME Type.
     * @param array $config Component configuration.
     * @return void
     * @dataProvider initializeProvider
     * @covers ::initialize()
     */
    public function testInitialize($expectedMimeType, array $config)
    {
        $component = new JsonApiComponent(new ComponentRegistry(new Controller()), $config);

        static::assertEquals($expectedMimeType, $component->getController()->getResponse()->getHeaderLine('content-type'));
        static::assertArrayHasKey('jsonapi', $component->RequestHandler->getConfig('viewClassMap'));
    }

    /**
     * Test component `getLinks()` method.
     *
     * @return void
     * @covers ::getLinks()
     */
    public function testLinks()
    {
        $expected = [
            'self' => 'http://example.org/roles',
            'home' => 'http://example.org/home',
        ];

        $request = new ServerRequest([
            'params' => [
                'plugin' => 'BEdita/API',
                'controller' => 'Roles',
                'action' => 'index',
                '_method' => 'GET',
            ],
            'base' => '/',
            'url' => 'roles',
        ]);
        $controller = new Controller($request);
        $component = new JsonApiComponent(new ComponentRegistry($controller), []);

        static::assertEquals($expected, $component->getLinks());
    }

    /**
     * Data provider for `testPagination` test case.
     *
     * @return array
     */
    public function paginationProvider()
    {
        return [
            'default' => [
                [
                    'self' => 'http://example.org/roles',
                    'first' => 'http://example.org/roles',
                    'last' => 'http://example.org/roles',
                    'prev' => null,
                    'next' => null,
                    'home' => 'http://example.org/home',
                ],
                [
                    'pagination' => [
                        'count' => 2,
                        'page' => 1,
                        'page_count' => 1,
                        'page_items' => 2,
                        'page_size' => 20,
                    ],
                ],
                [],
            ],
            'limit' => [
                [
                    'self' => 'http://example.org/roles?limit=1',
                    'first' => 'http://example.org/roles?limit=1',
                    'last' => 'http://example.org/roles?limit=1&page=2',
                    'prev' => null,
                    'next' => 'http://example.org/roles?limit=1&page=2',
                    'home' => 'http://example.org/home',
                ],
                [
                    'pagination' => [
                        'count' => 2,
                        'page' => 1,
                        'page_count' => 2,
                        'page_items' => 1,
                        'page_size' => 1,
                    ],
                ],
                [
                    'limit' => 1,
                ],
            ],
            'page' => [
                [
                    'self' => 'http://example.org/roles?page=2&limit=1',
                    'first' => 'http://example.org/roles?limit=1',
                    'last' => 'http://example.org/roles?page=2&limit=1',
                    'prev' => 'http://example.org/roles?limit=1',
                    'next' => null,
                    'home' => 'http://example.org/home',
                ],
                [
                    'pagination' => [
                        'count' => 2,
                        'page' => 2,
                        'page_count' => 2,
                        'page_items' => 1,
                        'page_size' => 1,
                    ],
                ],
                [
                    'page' => 2,
                    'limit' => 1,
                ],
            ],
        ];
    }

    /**
     * Test component `getLinks()` and `getMeta()` methods with pagination.
     *
     * @param array $expectedLinks Expected links array.
     * @param array $expectedMeta Expected meta array.
     * @param array $query Request query params.
     * @return void
     * @dataProvider paginationProvider
     * @covers ::getLinks()
     * @covers ::getMeta()
     */
    public function testPagination(array $expectedLinks, array $expectedMeta, array $query)
    {
        $request = new ServerRequest([
            'params' => [
                'plugin' => 'BEdita/API',
                'controller' => 'Roles',
                'action' => 'index',
                '_method' => 'GET',
            ],
            'base' => '/',
            'url' => 'roles',
            'query' => $query,
        ]);
        $controller = new Controller($request);
        $controller->paginate(TableRegistry::getTableLocator()->get('Roles'));
        $component = new JsonApiComponent(new ComponentRegistry($controller), []);

        static::assertEquals($expectedLinks, $component->getLinks());
        static::assertEquals($expectedMeta, $component->getMeta());
    }

    /**
     * Test component `beforeRender()` method.
     *
     * @param array $expectedLinks Expected links array.
     * @param array $expectedMeta Expected meta array.
     * @param array $query Request query params.
     * @return void
     * @dataProvider paginationProvider
     * @covers ::beforeRender()
     */
    public function testBeforeRender(array $expectedLinks, array $expectedMeta, array $query)
    {
        $base = [
            'gustavo' => 'https://support.example.org',
        ];

        $request = new ServerRequest([
            'params' => [
                'plugin' => 'BEdita/API',
                'controller' => 'Roles',
                'action' => 'index',
                '_method' => 'GET',
            ],
            'base' => '/',
            'url' => 'roles',
            'query' => $query,
        ]);
        $controller = new Controller($request);
        $controller->paginate(TableRegistry::getTableLocator()->get('Roles'));
        $controller->set([
            '_links' => $base,
            '_meta' => $base,
        ]);
        $controller->loadComponent('BEdita/API.JsonApi');

        $controller->dispatchEvent('Controller.beforeRender');

        static::assertEquals($expectedLinks + $base, $controller->viewBuilder()->getVar('_links'));
        static::assertEquals($expectedMeta + $base, $controller->viewBuilder()->getVar('_meta'));
    }

    /**
     * Test `error()` method.
     *
     * @return void
     * @covers ::error()
     */
    public function testError()
    {
        $expected = [
            'status' => '500',
            'title' => 'Example error',
            'detail' => 'Example description',
            'code' => 'my-code',
            'meta' => [
                'key' => 'Example metadata',
            ],
        ];

        $controller = new Controller();
        $component = new JsonApiComponent(new ComponentRegistry($controller), []);

        $component->error(500, 'Example error', 'Example description', 'my-code', ['key' => 'Example metadata']);

        static::assertEquals($expected, $controller->viewBuilder()->getVar('_error'));
    }

    /**
     * Data provider for `beforeFilter` test case.
     *
     * @return array
     */
    public function beforeFilterProvider()
    {
        return [
            'valid' => [
                [
                    'type' => 'customType',
                    'key' => 'value',
                ],
                json_decode('{"data":{"type":"customType","attributes":{"key":"value"}}}', true),
            ],
            'no parse' => [
                ['some' => 'value'],
                ['some' => 'value'],
                ['parseJson' => false],
            ],
            'empty' => [
                [],
                [],
            ],
            'missing data' => [
                new BadRequestException('Invalid JSON input'),
                ['some' => 'value'],
            ],
            'bad json api' => [
                new BadRequestException('Bad JSON API input'),
                ['data' => ['id' => 'a']],
            ],
        ];
    }

    /**
     * Test `beforeFilter()` method.
     *
     * @param \Excepion|array $expected Exception or expected parsed array.
     * @param string $input Input to be parsed.
     * @return void
     * @dataProvider beforeFilterProvider
     * @covers ::beforeFilter()
     * @covers ::parseInput()
     */
    public function testParseJsonInput($expected, array $input, array $config = []): void
    {
        if ($expected instanceof \Exception) {
            $this->expectException(get_class($expected));
            $this->expectExceptionMessage($expected->getMessage());
        }

        $component = new JsonApiComponent(new ComponentRegistry(new Controller()));
        $component->setConfig($config);
        $request = $component->getController()->getRequest();
        $component->getController()->setRequest(
            $request->withParsedBody($input)->withHeader('Content-Type', 'application/json')
        );

        $component->beforeFilter(new Event('test'));

        $result = $component->getController()->getRequest()->getData();

        static::assertEquals($expected, $result);
    }

    /**
     * Data provider for `testAllowedResourceTypes` test case.
     *
     * @return array
     */
    public function allowedResourceTypesProvider()
    {
        return [
            'single' => [
                true,
                'myCustomType',
                [
                    'type' => 'myCustomType',
                    'key' => 'value',
                ],
            ],
            'multiple' => [
                true,
                ['myCustomType1', 'myCustomType2'],
                [
                    [
                        'type' => 'myCustomType1',
                        'key' => 'value',
                    ],
                    [
                        'type' => 'myCustomType2',
                        'key' => 'value',
                    ],
                ],
            ],
            'emptyData' => [
                true,
                ['myCustomType1', 'myCustomType2'],
                [],
            ],
            'emptyTypes' => [
                true,
                null,
                [
                    'type' => 'myCustomType',
                    'key' => 'value',
                ],
            ],
            'unsupportedType' => [
                false,
                ['myCustomType'],
                [
                    'type' => 'unsupportedType',
                    'key' => 'value',
                ],
            ],
        ];
    }

    /**
     * Test `allowedResourceTypes()` method.
     *
     * @param bool $expected Expected success.
     * @param mixed $types Allowed types.
     * @param array $data Data to be checked.
     * @return void
     * @dataProvider allowedResourceTypesProvider
     * @covers ::allowedResourceTypes()
     * @covers ::startup()
     */
    public function testAllowedResourceTypes($expected, $types, array $data)
    {
        if (!$expected) {
            $this->expectException('\Cake\Http\Exception\ConflictException');
        }

        $request = new ServerRequest([
            'environment' => [
                'HTTP_ACCEPT' => 'application/vnd.api+json',
                'HTTP_CONTENT_TYPE' => 'application/vnd.api+json',
                'REQUEST_METHOD' => 'POST',
            ],
            'post' => $data,
        ]);

        $controller = new Controller($request);
        $controller->loadComponent('BEdita/API.JsonApi', ['resourceTypes' => $types]);

        $controller->dispatchEvent('Controller.startup');

        static::assertTrue(true);
    }

    /**
     * Data provider for `testCheckMediaType` test case.
     *
     * @return array
     */
    public function checkMediaTypeProvider()
    {
        return [
            'ok' => [
                true,
                'application/vnd.api+json',
                true,
            ],
            'no check' => [
                true,
                'application/json',
                false,
            ],
            'error (dramatic music)' => [
                new UnsupportedMediaTypeException('Bad request content type "gustavo/supporto"'),
                'gustavo/supporto',
                true,
            ],
        ];
    }

    /**
     * Test media type checks in `startup()` method.
     *
     * @param true|\Exception $expected Expected success.
     * @param string $accept Value of "Accept" header.
     * @param bool $checkMediaType Is media type check enabled?
     * @return void
     * @dataProvider checkMediaTypeProvider
     * @covers ::startup()
     */
    public function testCheckMediaType($expected, $accept, $checkMediaType)
    {
        if ($expected instanceof \Exception) {
            $this->expectException(get_class($expected));
            $this->expectExceptionCode($expected->getCode());
            $this->expectExceptionMessage($expected->getMessage());
        }

        $request = new ServerRequest([
            'environment' => [
                'HTTP_ACCEPT' => $accept,
                'REQUEST_METHOD' => 'GET',
            ],
        ]);

        $controller = new Controller($request);
        $controller->loadComponent('BEdita/API.JsonApi', compact('checkMediaType'));

        $controller->dispatchEvent('Controller.startup');

        static::assertTrue($expected);
    }

    /**
     * Data provider for `testAllowClientGeneratedIds` test case.
     *
     * @return array
     */
    public function allowClientGeneratedIdsProvider()
    {
        return [
            'single' => [
                true,
                [
                    'type' => 'myCustomType',
                    'key' => 'value',
                ],
            ],
            'multiple' => [
                true,
                [
                    [
                        'type' => 'myCustomType1',
                        'key' => 'value',
                    ],
                    [
                        'type' => 'myCustomType2',
                        'key' => 'value',
                    ],
                ],
            ],
            'emptyData' => [
                true,
                [],
            ],
            'unsupportedClientGeneratedId' => [
                false,
                [
                    'id' => 'my-id',
                    'type' => 'myCustomType',
                    'key' => 'value',
                ],
            ],
        ];
    }

    /**
     * Test `allowClientGeneratedIds()` method.
     *
     * @param bool $expected Expected success.
     * @param array $data Data to be checked.
     * @return void
     * @dataProvider allowClientGeneratedIdsProvider
     * @covers ::allowClientGeneratedIds()
     * @covers ::startup()
     */
    public function testAllowClientGeneratedIds($expected, array $data)
    {
        if (!$expected) {
            $this->expectException('\Cake\Http\Exception\ForbiddenException');
        }

        $request = new ServerRequest([
            'environment' => [
                'HTTP_ACCEPT' => 'application/vnd.api+json',
                'HTTP_CONTENT_TYPE' => 'application/vnd.api+json',
                'REQUEST_METHOD' => 'POST',
            ],
            'post' => $data,
        ]);

        $controller = new Controller($request);
        $controller->loadComponent('BEdita/API.JsonApi');

        $controller->dispatchEvent('Controller.startup');

        static::assertTrue(true);
    }
}
