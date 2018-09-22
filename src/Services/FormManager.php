<?php

namespace Appercode\Services;

use Appercode\Backend;

use Appercode\FormReport;

use Appercode\Contracts\Form as FormContract;
use Appercode\Contracts\FormReport as FormReportContract;

class FormManager
{
    protected $backend;

    public $form;

    public function __construct(FormContract $form, Backend $backend)
    {
        $this->form = $form;
        $this->backend = $backend;
    }

    protected function createVariantsReport()
    {
        $controlsIds = $this->form->questions()->map(function ($item) {
            return in_array($item['type'], ['radioButtons', 'comboBox', 'imagesCheckBoxList', 'checkBoxList'])
                ? $item['id']
                : null;
        })->filter()->values()->toArray();

        return FormReport::create($this->backend, $this->form->id, $controlsIds);
    }

    public function recreateVariantsReport()
    {
        FormReport::list($this->backend, [
            'where' => [
                'formId' => $this->form->id
            ]
        ])->each(function ($report) {
            $report->delete();
        });

        return $this->createVariantsReport();
    }
}
