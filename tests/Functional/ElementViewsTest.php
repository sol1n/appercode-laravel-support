<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\Element;
use Appercode\Schema;
use Appercode\Services\ElementManager;
use Appercode\Services\ViewsManager;
use Appercode\Enums\Schema\FieldTypes as SchemaFieldTypes;

class ElementViewsTest extends TestCase
{
    private $user;
    private $schema;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
        $this->schema = $this->createSchema($this->user->backend, [
            [
                'name' => 'stringSingleField',
                'type' => SchemaFieldTypes::STRING
            ]
        ]);
    }

    protected function tearDown()
    {
        $this->schema->delete();
    }

    private function createSchema(Backend $backend, $fields = [])
    {
        return Schema::create([
            'id' => 'elementsTestSchema',
            'title' => 'deleteMePlease',
            'fields' => $fields
        ], $backend);
    }

    /**
     * @group views
     */
    public function test_can_update_badgets_for_user_set()
    {
        $element = Element::create($this->schema->id, ['stringSingleField' => 'title'], $this->user->backend);

        $viewsManager = new ViewsManager($this->user->backend);

        $viewsManager->addBadges($this->schema, [
            $element->id => [
                $this->user->id
            ]
        ]);

        $badgets = $viewsManager->getBadges($this->schema);

        $this->assertEquals(in_array($element->id, $badgets->toArray()), true);

        $viewsManager->removeBadges($this->schema, [
            $element->id => [
                $this->user->id
            ]
        ]);

        $badgets = $viewsManager->getBadges($this->schema);

        $this->assertEquals(in_array($element->id, $badgets->toArray()), false);
    }

    /**
     * @group views
     */
    public function test_can_update_badgets_for_all_users()
    {
        $element = Element::create($this->schema->id, ['stringSingleField' => 'title'], $this->user->backend);

        $viewsManager = new ViewsManager($this->user->backend);

        $viewsManager->addBadges($this->schema, [
            $element->id => []
        ]);

        $badgets = $viewsManager->getBadges($this->schema);

        $this->assertEquals(in_array($element->id, $badgets->toArray()), true);

        $viewsManager->removeBadges($this->schema, [
            $element->id => []
        ]);

        $badgets = $viewsManager->getBadges($this->schema);

        $this->assertEquals(in_array($element->id, $badgets->toArray()), false);
    }

    /**
     * @group views
     */
    public function test_can_get_views_of_element()
    {
        $element = Element::create($this->schema->id, ['stringSingleField' => 'title'], $this->user->backend);

        $viewsManager = new ViewsManager($this->user->backend);

        $viewsManager->sendView($this->user, $element);

        $views = $viewsManager->views($this->schema, [$element->id]);

        $this->assertEquals(is_array($views[$element->id]), true);
        $this->assertEquals($views[$element->id]['viewsCount'], 1);
        $this->assertEquals($views[$element->id]['viewedByCurrentUser'], true);
        $this->assertEquals($views[$element->id]['objectId'], $element->id);
    }
}
