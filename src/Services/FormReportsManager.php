<?php

namespace Appercode\Services;

use Appercode\Backend;

use Appercode\FormReport;

use Appercode\Contracts\Form as FormContract;
use Appercode\Contracts\FormReport as FormReportContract;

class FormReportsManager
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
        $this->form->reports()->each(function ($report) {
            $report->delete();
        });

        return $this->createVariantsReport();
    }
}
