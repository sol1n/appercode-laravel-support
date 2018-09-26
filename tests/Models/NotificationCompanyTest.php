<?php

namespace Tests\Unit;

use Tests\TestCase;

use Appercode\User;
use Appercode\Backend;
use Appercode\NotificationCompany;

use Appercode\Exceptions\NotificationCompany\ReceiveException;

use Carbon\Carbon;

class NotificationCompanyTest extends TestCase
{
    private $user;

    protected function setUp()
    {
        parent::setUp();

        $this->user = User::login((new Backend), getenv('APPERCODE_USER'), getenv('APPERCODE_PASSWORD'));
    }

    protected function companyData()
    {
        return [
            'title' => [
                'ru' => 'NotificationCompany title',
                'en' => 'NotificationCompany title en'
            ],
            'body' => [
                'ru' => 'NotificationCompany description',
                'en' => 'NotificationCompany description en'
            ],
            'deepLink' => 'deepLink string',
            'to' => [1],
            'scheduledAt' => null,
            'withPushNotification' => true,
            'withBadgeNotification' => false,
            'installationFilter' => [
                "channel" => null,
                "deviceType" => null,
                "appVersion" => null,
                "language" => null
            ],
            'isPublished' => false
        ];
    }

    /**
     * @group notifications
     */
    public function test_company_can_be_created()
    {
        $company = NotificationCompany::create($this->user->backend, $this->companyData());

        foreach ($this->companyData() as $index => $field) {
            $this->assertEquals($field, $company->{$index});
        }

        $company->delete();
    }

    /**
     * @group notifications
     */
    public function test_company_can_be_deleted()
    {
        $company = NotificationCompany::create($this->user->backend, $this->companyData())->delete();

        $company = NotificationCompany::find($this->user->backend, $company->id);

        $this->assertEquals($company->isDeleted, true);
    }

    /**
     * @group notifications
     */
    public function test_company_can_be_counted()
    {
        $company = NotificationCompany::create($this->user->backend, $this->companyData());
        $count = NotificationCompany::count($this->user->backend, [
            'where' => [
                'id' => $company->id
            ]
        ]);
        
        $this->assertEquals($count, 1);

        $company->delete();
    }

    /**
     * @group notifications
     */
    public function test_company_can_be_listed()
    {
        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $ids[] = NotificationCompany::create($this->user->backend, $this->companyData())->id;
        }

        $companies = NotificationCompany::list($this->user->backend, [
            'where' => [
                'id' => [
                    '$in' => $ids
                ]
            ]
        ]);

        $this->assertEquals($companies->count(), 3);
        foreach ($companies as $company) {
            $this->assertEquals(in_array($company->id, $ids), true);
        }

        NotificationCompany::deleteStatic($this->user->backend, $ids);
    }

    /**
     * @group notifications
     */
    public function not_a_test_company_can_be_sended()
    {
        $company = NotificationCompany::create($this->user->backend, $this->companyData())->send();
        $company = NotificationCompany::find($this->user->backend, $company->id);

        $this->assertNotNull($company->sentAt);

        $company->delete();
    }

    /**
     * @group notifications
     */
    public function test_company_can_be_deleted_via_static_method()
    {
        $company = NotificationCompany::create($this->user->backend, $this->companyData());

        NotificationCompany::deleteStatic($this->user->backend, [$company->id]);

        $company = NotificationCompany::find($this->user->backend, $company->id);

        $this->assertEquals($company->isDeleted, true);
    }

    /**
     * @group notifications
     */
    public function not_a_test_company_can_be_sended_via_static_method()
    {
        $company = NotificationCompany::create($this->user->backend, $this->companyData());
        NotificationCompany::sendStatic($this->user->backend, [$company->id]);
        $company = NotificationCompany::find($this->user->backend, $company->id);

        $this->assertNotNull($company->sentAt);

        $company->delete();
    }

    /**
     * @group notifications
     */
    public function test_company_can_be_updated_via_static_method()
    {
        $company = NotificationCompany::create($this->user->backend, $this->companyData());

        NotificationCompany::update($this->user->backend, [
            'deepLink' => 'new deepLink value'
        ], $company->id);

        $company = NotificationCompany::find($this->user->backend, $company->id);

        $this->assertEquals($company->deepLink, 'new deepLink value');

        $company->delete();
    }

    /**
     * @group notifications
     */
    public function test_company_can_be_updated_via_instance_method()
    {
        $company = NotificationCompany::create($this->user->backend, $this->companyData());
        $company->deepLink = 'new deepLink value';
        $company->save();

        $this->assertEquals($company->deepLink, 'new deepLink value');

        $company = NotificationCompany::find($this->user->backend, $company->id);

        $this->assertEquals($company->deepLink, 'new deepLink value');

        $company->delete();
    }
}
