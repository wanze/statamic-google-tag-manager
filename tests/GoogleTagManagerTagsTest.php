<?php

namespace Statamic\Addons\GoogleTagManager\tests;

use Illuminate\Http\Request;
use PHPUnit_Framework_MockObject_MockObject;
use Statamic\Config\Addons;
use Statamic\Addons\GoogleTagManager\GoogleTagManagerTags;
use Statamic\Data\Services\UserGroupsService;
use Statamic\Data\Users\User;

/**
 * Unit tests for the GoogleTagManagerTags class.
 *
 * @coversDefaultClass \Statamic\Addons\GoogleTagManager\GoogleTagManagerTags
 *
 * @group google_tag_manager
 */
class GoogleTagManagerTagsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var GoogleTagManagerTags
     */
    private $googleTagManagerTags;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $request;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $user;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $addons;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $userGroupService;

    /**
     * @var array
     */
    private $config = [
        'container_id' => 'GTM-XXX',
        'data_layer_variable' => 'dataLayer',
        'exclude_paths' => [],
        'exclude_authenticated' => true,
        'exclude_user_roles' => [],
        'exclude_user_groups' => [],
    ];

    protected function setUp()
    {
        $this->request = $this->getMockBuilder(Request::class)->getMock();
        $this->addons = $this->getMockBuilder(Addons::class)->getMock();
        $this->user = $this->getMockBuilder(User::class)->getMock();
        $this->userGroupService = $this->getMockBuilder(UserGroupsService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->googleTagManagerTags = new GoogleTagManagerTags($this->request, $this->addons, $this->userGroupService);
    }

    /**
     * Test that the tags contain the GTM container ID and data layer name from the addon's config.
     */
    public function test_output_contains_data_from_config()
    {
        $dataLayerVariable = 'customDataLayerVariable';

        $this->addons
            ->method('get')
            ->willReturn(array_merge($this->config, ['data_layer_variable' => $dataLayerVariable]));

        $this->request
            ->expects($this->any())
            ->method('user')
            ->willReturn(null);

        $this->assertTrue((bool) strpos($this->googleTagManagerTags->head(), 'GTM-XXX'));
        $this->assertTrue((bool) strpos($this->googleTagManagerTags->head(), $dataLayerVariable));
        $this->assertTrue((bool) strpos($this->googleTagManagerTags->body(), 'GTM-XXX'));
    }

    /**
     * Test that the tags do not output the GTM javascript for authenticated users.
     */
    public function test_exclude_authenticated_users()
    {
        $this->addons
            ->method('get')
            ->willReturn($this->config);

        $this->request
            ->expects($this->any())
            ->method('user')
            ->willReturn($this->user);

        $this->assertEquals('', $this->googleTagManagerTags->head());
        $this->assertEquals('', $this->googleTagManagerTags->body());
    }

    /**
     * Test that the tags do output the GTM javascript for anonymous users.
     */
    public function test_include_anonymous_users()
    {
        $this->addons
            ->method('get')
            ->willReturn($this->config);

        $this->request
            ->expects($this->any())
            ->method('user')
            ->willReturn(null);

        $this->assertNotEquals('', $this->googleTagManagerTags->head());
        $this->assertNotEquals('', $this->googleTagManagerTags->body());
    }

    /**
     * Test that the tags do not output GTM javascript on excluded paths.
     *
     * @dataProvider pathExclusionDataProvider
     */
    public function test_exclude_by_paths(array $excludedPaths, $path, $expectEmptyOutput)
    {
        $config = array_merge($this->config, ['exclude_paths' => $excludedPaths]);

        $this->addons
            ->method('get')
            ->willReturn($config);

        $this->request
            ->expects($this->any())
            ->method('getPathInfo')
            ->willReturn($path);

        if ($expectEmptyOutput) {
            $this->assertEquals('', $this->googleTagManagerTags->head());
            $this->assertEquals('', $this->googleTagManagerTags->body());
        } else {
            $this->assertNotEquals('', $this->googleTagManagerTags->head());
            $this->assertNotEquals('', $this->googleTagManagerTags->body());
        }
    }

    /**
     * Test that the tags do not output GTM javascript for excluded user roles or groups.
     *
     * @dataProvider userExclusionDataProvider
     */
    public function test_exclude_by_roles_or_groups($hasRole, $inGroup, $expectEmptyOutput)
    {
        $config = array_merge(
            $this->config,
            [
                'exclude_authenticated' => false,
                'exclude_user_roles' => ['role1', 'role2'],
                'exclude_user_groups' => ['group1', 'group2'],
            ]
        );

        $this->addons
            ->method('get')
            ->willReturn($config);

        $this->request
            ->expects($this->any())
            ->method('user')
            ->willReturn($this->user);

        $this->user
            ->method('hasRole')
            ->willReturn($hasRole);

        $this->userGroupService
            ->method('handle')
            ->willReturn($this->any());

        $this->user
            ->method('inGroup')
            ->with($this->any())
            ->willReturn($inGroup);

        if ($expectEmptyOutput) {
            $this->assertEquals('', $this->googleTagManagerTags->head());
            $this->assertEquals('', $this->googleTagManagerTags->body());
        } else {
            $this->assertNotEquals('', $this->googleTagManagerTags->head());
            $this->assertNotEquals('', $this->googleTagManagerTags->body());
        }
    }

    /**
     * @return array
     */
    public function userExclusionDataProvider()
    {
        return [
            [
                false,      // User has excluded role
                false,      // User has excluded group
                false,      // Empty output?
            ],
            [
                true,
                false,
                true,
            ],
            [
                false,
                true,
                true,
            ],
            [
                true,
                true,
                true,
            ],
        ];
    }

    /**
     * @return array
     */
    public function pathExclusionDataProvider()
    {
        return [
            [
                [],         // Excluded paths in config
                '/',        // Current path of request
                false,      // Empty output?
            ],
            [
                ['/foo'],
                '/bar',
                false,
            ],
            [
                ['/foo'],
                '/foo',
                true,
            ],
            [
                ['/foo*'],
                '/foo/bar',
                true,
            ],
            [
                ['/foo', '/bar'],
                '/bard',
                false,
            ],
            [
                ['/foo', '/bar'],
                '/bar',
                true,
            ],
            [
                ['/foo', '/bar*'],
                '/bar/x',
                true,
            ],
        ];
    }
}
