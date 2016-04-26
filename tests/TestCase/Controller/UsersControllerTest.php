<?php
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
namespace BEdita\API\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class UsersControllerTest extends IntegrationTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = [
        'plugin.BEdita/Core.users',
    ];

    public function testIndex()
    {
        $this->configRequest([
            'headers' => ['Accept' => 'application/json']
        ]);
        $result = $this->get('/users');

        // Check that the response was a 200
        $this->assertResponseOk();

        $expected = [
            'links' => [
                'self' => 'http:///users'
            ],
            'data' => [
                [
                    'id' => '1',
                    'type' => 'users',
                    'attributes' => [
                        'username' => 'first user',
                        'blocked' => false,
                        'last_login' => null,
                        'last_login_err' => null,
                        'num_login_err' => 1,
                        'created' => '2016-03-15T09:57:38+0000',
                        'modified' => '2016-03-15T09:57:38+0000',
                    ]
                ],
                [
                    'id' => '2',
                    'type' => 'users',
                    'attributes' => [
                        'username' => 'second user',
                        'blocked' => false,
                        'last_login' => '2016-03-15T09:57:38+0000',
                        'last_login_err' => '2016-03-15T09:57:38+0000',
                        'num_login_err' => 0,
                        'created' => '2016-03-15T09:57:38+0000',
                        'modified' => '2016-03-15T09:57:38+0000',
                    ]
                ],
            ]
        ];
        $expected = json_encode($expected, JSON_PRETTY_PRINT);
        $response = $this->_response->body();
        $this->assertEquals($expected, $response);
    }

    public function testSingle()
    {
        $this->configRequest([
            'headers' => ['Accept' => 'application/json']
        ]);
        $result = $this->get('/users/1');

        // Check that the response was a 200
        $this->assertResponseOk();

        $expected = [
            'links' => [
                'self' => 'http:///users/1'
            ],
            'data' => [
                'id' => '1',
                'type' => 'users',
                'attributes' => [
                    'username' => 'first user',
                    'blocked' => false,
                    'last_login' => null,
                    'last_login_err' => null,
                    'num_login_err' => 1,
                    'created' => '2016-03-15T09:57:38+0000',
                    'modified' => '2016-03-15T09:57:38+0000',
                ]
            ]
        ];
        $expected = json_encode($expected, JSON_PRETTY_PRINT);
        $response = $this->_response->body();
        $this->assertEquals($expected, $response);
    }
}