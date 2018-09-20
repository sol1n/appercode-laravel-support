<?php

namespace Appercode;

use Appercode\Traits\AppercodeRequest;

use Appercode\Contracts\FormReport as FormReportContract;

use Appercode\Form;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use GuzzleHttp\Exception\BadResponseException;

class FormReport implements FormReportContract
{
    use AppercodeRequest;

    private $backend;
}
