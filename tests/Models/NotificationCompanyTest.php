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

    public function test_company_can_be_created()
    {
        $company = NotificationCompany::create($this->user->backend, $this->companyData());

        foreach ($this->companyData() as $index => $field) {
            $this->assertEquals($field, $company->{$index});
        }

        $company->delete();
    }

    public function test_company_can_be_deleted()
    {
        $company = NotificationCompany::create($this->user->backend, $this->companyData())->delete();

        $company = NotificationCompany::find($this->user->backend, $company->id);

        $this->assertEquals($company->isDeleted, true);
    }
}
