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

namespace BEdita\API\Test\TestCase\Model\Action;

use Authorization\Identity;
use Authorization\Policy\Exception\MissingPolicyException;
use BEdita\API\Model\Action\UpdateAssociatedAction;
use BEdita\Core\Exception\InvalidDataException;
use BEdita\Core\Model\Action\AddRelatedObjectsAction;
use BEdita\Core\Model\Action\SetAssociatedAction;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\ServerRequest;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;

/**
 * @covers \BEdita\API\Model\Action\UpdateAssociatedAction
 */
class UpdateAssociatedActionTest extends TestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    protected $fixtures = [
        'plugin.BEdita/Core.FakeAnimals',
        'plugin.BEdita/Core.FakeArticles',
        'plugin.BEdita/Core.FakeTags',
        'plugin.BEdita/Core.FakeArticlesTags',
        'plugin.BEdita/Core.ObjectTypes',
        'plugin.BEdita/Core.Objects',
        'plugin.BEdita/Core.Relations',
        'plugin.BEdita/Core.RelationTypes',
        'plugin.BEdita/Core.ObjectRelations',
    ];

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        TableRegistry::getTableLocator()->get('FakeTags')
            ->belongsToMany('FakeArticles', [
                'joinTable' => 'fake_articles_tags',
            ]);
        /** @var \Cake\ORM\Association\BelongsToMany $association */
        $association = TableRegistry::getTableLocator()->get('FakeTags')->getAssociation('FakeArticles');
        $association->junction()
            ->getValidator()
            ->email('fake_params');

        TableRegistry::getTableLocator()->get('FakeArticles')
            ->belongsToMany('FakeTags', [
                'joinTable' => 'fake_articles_tags',
            ])
            ->getSource()
            ->belongsTo('FakeAnimals');

        TableRegistry::getTableLocator()->get('FakeAnimals')
            ->hasMany('FakeArticles');
    }

    /**
     * Data provider for `testInvocation` test case.
     *
     * @return array
     */
    public function invocationProvider()
    {
        return [
            'belongsToManyDuplicateEntry' => [
                1,
                'FakeTags',
                'FakeArticles',
                1,
                [
                    ['id' => 1],
                    ['id' => 2],
                    ['id' => 2],
                ],
            ],
            'belongsToManySingleDuplicateEntry' => [
                2,
                'FakeTags',
                'FakeArticles',
                1,
                [
                    ['id' => 2],
                    ['id' => 2],
                ],
            ],
            'belongsToManyEmpty' => [
                1,
                'FakeTags',
                'FakeArticles',
                1,
                [],
            ],
            'belongsToManyNothingToDo' => [
                0,
                'FakeTags',
                'FakeArticles',
                1,
                [
                    ['id' => 1],
                ],
            ],
            'hasManyNothingToDo' => [
                0,
                'FakeAnimals',
                'FakeArticles',
                1,
                [
                    ['id' => 1],
                    ['id' => 2],
                ],
            ],
            'unsupportedMultipleEntities' => [
                new \InvalidArgumentException(
                    'Unable to link multiple entities'
                ),
                'FakeArticles',
                'FakeAnimals',
                1,
                [
                    ['id' => 1],
                    ['id' => 2],
                ],
            ],
            'belongsTo' => [
                1,
                'FakeArticles',
                'FakeAnimals',
                1,
                [
                    'id' => 2,
                ],
            ],
            'belongsToNothingToDo' => [
                0,
                'FakeArticles',
                'FakeAnimals',
                1,
                [
                    'id' => 1,
                ],
            ],
            'missingEntity' => [
                new RecordNotFoundException('Record not found in table "fake_animals"'),
                'FakeArticles',
                'FakeAnimals',
                2,
                [
                    'id' => 99,
                ],
            ],
            'belongsToMany with parameters' => [
                2,
                'FakeTags',
                'FakeArticles',
                1,
                [
                    [
                        'id' => 2,
                        '_meta' => [
                            'relation' => [
                                'fake_params' => 'gustavo.supporto@example.org',
                            ],
                        ],
                    ],
                ],
            ],
            'belongsToMany invalid parameters' => [
                new InvalidDataException('Invalid data'),
                'FakeTags',
                'FakeArticles',
                1,
                [
                    [
                        'id' => 2,
                        '_meta' => [
                            'relation' => [
                                'fake_params' => 'not an email',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test invocation of command.
     *
     * @param bool|\Exception $expected Expected result.
     * @param string $table Table to use.
     * @param string $association Association to use.
     * @param int $id Entity ID to update relations for.
     * @param int|int[]|null $data Related entity(-ies).
     * @return void
     * @dataProvider invocationProvider()
     */
    public function testInvocation($expected, $table, $association, $id, $data)
    {
        if ($expected instanceof \Exception) {
            $this->expectException(get_class($expected));
            $this->expectExceptionMessage($expected->getMessage());
        }

        $request = new ServerRequest();
        $identityMock = $this->getMockBuilder(Identity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['can'])
            ->getMock();

        $identityMock->method('can')->willReturn(true);

        $request = $request->withParsedBody($data)
            ->withAttribute('identity', $identityMock);
        $association = TableRegistry::getTableLocator()->get($table)->getAssociation($association);
        $parentAction = new SetAssociatedAction(compact('association'));
        $action = new UpdateAssociatedAction(['action' => $parentAction, 'request' => $request]);

        $result = $action(['primaryKey' => $id]);

        $count = 0;
        if ($data !== null) {
            $count = $association->getTarget()->find()
                ->matching(
                    Inflector::camelize($association->getSource()->getTable()),
                    function (Query $query) use ($association, $id) {
                        return $query->where([
                            $association->getSource()->aliasField($association->getSource()->getPrimaryKey()) => $id,
                        ]);
                    }
                )
                ->count();
        }

        static::assertEquals($expected, $result);
        static::assertEquals(count(array_unique($data, SORT_REGULAR)), $count);
    }

    /**
     * Test that invocation of command does not remove previously existing junction data.
     *
     * @return void
     */
    public function testKeepJunctionData()
    {
        // Prepare link with junction data.
        $junction = TableRegistry::getTableLocator()->get('FakeArticlesTags');
        $junctionEntity = $junction->newEntity([]);
        $junction->patchEntity($junctionEntity, [
            'fake_article_id' => 2,
            'fake_tag_id' => 1,
            'fake_params' => 'previous@example.com',
        ]);
        $junction->saveOrFail($junctionEntity);

        $request = new ServerRequest();
        $identityMock = $this->getMockBuilder(Identity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['can'])
            ->getMock();

        $identityMock->method('can')->willReturn(true);

        $request = $request
            ->withAttribute('identity', $identityMock)
            ->withParsedBody([
                [
                    'id' => 1,
                ],
                [
                    'id' => 2,
                ],
            ]);
        $association = TableRegistry::getTableLocator()->get('FakeArticles')->getAssociation('FakeTags');
        $parentAction = new SetAssociatedAction(compact('association'));
        $action = new UpdateAssociatedAction(['action' => $parentAction, 'request' => $request]);

        $action(['primaryKey' => 2]);

        $junctionEntities = $junction->find('all')
            ->select(['fake_article_id', 'fake_tag_id', 'fake_params'])
            ->where(['fake_article_id' => 2])
            ->enableHydration(false)
            ->all()
            ->toList();

        $expected = [
            [
                'fake_article_id' => 2,
                'fake_tag_id' => 2,
                'fake_params' => null,
            ],
            [
                'fake_article_id' => 2,
                'fake_tag_id' => 1,
                'fake_params' => 'previous@example.com',
            ],
        ];
        static::assertSame($expected, $junctionEntities);
    }

    /**
     * Test forbidden response if identity can't update an entity
     *
     * @return void
     */
    public function testForbidden(): void
    {
        $this->expectExceptionObject(new ForbiddenException('Cake\ORM\Entity [id=1] update is forbidden for user'));

        $data = [
            ['id' => 1],
            ['id' => 2],
        ];
        $request = new ServerRequest();
        $identityMock = $this->getMockBuilder(Identity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['can'])
            ->getMock();

        $identityMock->method('can')->willReturn(false);

        $request = $request->withParsedBody($data)
            ->withAttribute('identity', $identityMock);

        $association = TableRegistry::getTableLocator()->get('FakeTags')->getAssociation('FakeArticles');
        $parentAction = new SetAssociatedAction(compact('association'));
        $action = new UpdateAssociatedAction(['action' => $parentAction, 'request' => $request]);

        $action(['primaryKey' => 1]);
    }

    /**
     * Test forbidden response if identity can't update an entity's parent
     *
     * @return void
     */
    public function testForbiddenParent(): void
    {
        $this->expectExceptionObject(new ForbiddenException('Cake\ORM\Entity [id=1] patching "Parents" is forbidden due to restricted permission on some parent'));

        $data = [
            ['id' => 1],
            ['id' => 2],
        ];
        $request = new ServerRequest();
        $identityMock = $this->getMockBuilder(Identity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['can'])
            ->getMock();

        $identityMock->method('can')->with('updateParents')->willReturn(false);

        $request = $request->withParsedBody($data)
            ->withMethod('PATCH')
            ->withAttribute('identity', $identityMock);

        $associationMock = $this->getMockBuilder(HasMany::class)
            ->setConstructorArgs(['Parents'])
            ->onlyMethods(['getSource', 'getTarget'])
            ->getMock();

        $associationMock->method('getSource')->willReturn(TableRegistry::getTableLocator()->get('FakeAnimals'));
        $associationMock->method('getTarget')->willReturn(TableRegistry::getTableLocator()->get('FakeArticles'));

        $parentAction = new SetAssociatedAction(['association' => $associationMock]);
        $action = new UpdateAssociatedAction(['action' => $parentAction, 'request' => $request]);

        $action(['primaryKey' => 1]);
    }

    /**
     * Test that if the policy was not found, the action go ahead.
     *
     * @return void
     */
    public function testMissingPolicyContinue(): void
    {
        $data = [
            ['id' => 1],
            ['id' => 2],
        ];
        $request = new ServerRequest();
        $identityMock = $this->getMockBuilder(Identity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['can'])
            ->getMock();

        $identityMock->method('can')->willThrowException(new MissingPolicyException('Missing policy'));

        $request = $request->withParsedBody($data)
            ->withAttribute('identity', $identityMock);

        $association = TableRegistry::getTableLocator()->get('FakeTags')->getAssociation('FakeArticles');
        $parentAction = new SetAssociatedAction(compact('association'));
        $action = new UpdateAssociatedAction(['action' => $parentAction, 'request' => $request]);

        $result = $action(['primaryKey' => 1]);
        static::assertEquals(1, $result);
    }

    /**
     * Data provider for {@see UpdateAssociatedActionTest::testPrepareMeta()} test case.
     *
     * @return array[]
     */
    public function prepareMetaProvider(): array
    {
        return [
            'add relation without params, body without params' => [
                [6],
                null,
                'Test',
                2,
                [
                    [
                        'id' => 6,
                        'type' => 'documents',
                    ],
                ],
            ],
            'add relation without params, body wih params' => [
                [3],
                ['this' => 'has no schema'],
                'Test',
                2,
                [
                    [
                        'id' => 3,
                        'type' => 'documents',
                        '_meta' => [
                            'relation' => [
                                'params' => ['this' => 'has no schema'],
                            ],
                        ],
                    ],
                ],
            ],
            'add relation with params, body without params' => [
                [3],
                null,
                'TestSimple',
                2,
                [
                    [
                        'id' => 3,
                        'type' => 'documents',
                    ],
                ],
            ],
            'add relation with params, body with params' => [
                [3],
                ['name' => 'Andrew', 'age' => 26],
                'TestSimple',
                2,
                [
                    [
                        'id' => 3,
                        'type' => 'documents',
                        '_meta' => [
                            'relation' => [
                                'params' => ['name' => 'Andrew', 'age' => 26],
                            ],
                        ],
                    ],
                ],
            ],
            'add relation with defaults, body without params' => [
                [3],
                ['size' => 5, 'street' => 'fighter', 'color' => null],
                'TestDefaults',
                2,
                [
                    [
                        'id' => 3,
                        'type' => 'documents',
                    ],
                ],
            ],
            'add relation with defaults, body with params' => [
                [3],
                ['size' => 8, 'color' => 'green', 'street' => 'fighter'],
                'TestDefaults',
                2,
                [
                    [
                        'id' => 3,
                        'type' => 'documents',
                        '_meta' => [
                            'relation' => [
                                'params' => ['size' => 8, 'color' => 'green'],
                            ],
                        ],
                    ],
                ],
            ],
            'update relation without params, body without params' => [
                [], // already related, request has no changes
                null,
                'Test',
                2,
                [
                    [
                        'id' => 4,
                        'type' => 'profiles',
                    ],
                ],
            ],
            'update relation without params, body wih params' => [
                [4], // already related, request has changes
                ['this' => 'has no schema'],
                'Test',
                2,
                [
                    [
                        'id' => 4,
                        'type' => 'profiles',
                        '_meta' => [
                            'relation' => [
                                'params' => ['this' => 'has no schema'],
                            ],
                        ],
                    ],
                ],
            ],
            'update relation with params, body without params' => [
                [], // already related, request has no changes
                ['name' => 'John'],
                'TestSimple',
                2,
                [
                    [
                        'id' => 4,
                        'type' => 'profiles',
                    ],
                ],
            ],
            'update relation with params, body with params' => [
                [4], // already related, request has changes
                ['name' => 'Andrew', 'age' => 26],
                'TestSimple',
                2,
                [
                    [
                        'id' => 4,
                        'type' => 'profiles',
                        '_meta' => [
                            'relation' => [
                                'params' => ['name' => 'Andrew', 'age' => 26],
                            ],
                        ],
                    ],
                ],
            ],
            'update relation with defaults, body without params' => [
                [4],
                ['size' => 5, 'street' => 'fighter', 'color' => null],
                'TestDefaults',
                2,
                [
                    [
                        'id' => 4,
                        'type' => 'profiles',
                    ],
                ],
            ],
            'update relation with defaults, body with params' => [
                [4],
                ['size' => 8, 'color' => 'green', 'street' => 'fighter'],
                'TestDefaults',
                2,
                [
                    [
                        'id' => 4,
                        'type' => 'profiles',
                        '_meta' => [
                            'relation' => [
                                'params' => ['size' => 8, 'color' => 'green'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * Test case for {@see UpdateAssociatedActionTest::prepareMetaProvider()} when adding relation.
     *
     * @param int[] $expectedResult Expected action result.
     * @param array $expectedParams Expected relation parameters.
     * @param string $associationName Name of association.
     * @param int $primaryKey Left entity ID.
     * @param array $body Request body.
     * @return void
     * @dataProvider prepareMetaProvider()
     */
    public function testPrepareMeta($expectedResult, $expectedParams, $associationName, $primaryKey, $body): void
    {
        $Documents = TableRegistry::getTableLocator()->get('Documents');
        $association = $Documents->getAssociation($associationName);
        $identityMock = $this->getMockBuilder(Identity::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['can'])
            ->getMock();
        $identityMock->method('can')->willReturn(true);
        $request = (new ServerRequest())->withParsedBody($body)
            ->withAttribute('identity', $identityMock);
        $parentAction = new AddRelatedObjectsAction(compact('association'));
        $action = new UpdateAssociatedAction(['action' => $parentAction, 'request' => $request]);
        $result = $action(compact('primaryKey'));
        static::assertEquals($expectedResult, $result);

        // $entity = $Documents->get($primaryKey, ['contain' => [$associationName]]);
        $entity = $Documents->find()
            ->where(fn (QueryExpression $exp): QueryExpression => $exp
                ->eq('id', $primaryKey))
            ->contain([$associationName => fn (Query $q): Query => $q->where(['right_id' => $body[0]['id']])])
            ->first();
        $actualParams = Hash::get(
            (array)$entity->get(Inflector::underscore($associationName)),
            '0._joinData.params',
        );
        static::assertSame($expectedParams, $actualParams);
    }
}
