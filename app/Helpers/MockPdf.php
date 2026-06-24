<?php

namespace App\Helpers;

use Illuminate\Support\Facades\View;

class MockPdf
{
    public static function loadView(string $view, array $data = []): MockPdfWrapper
    {
        return new MockPdfWrapper($view, $data);
    }
}

class MockPdfWrapper
{
    public function __construct(protected string $view, protected array $data = [])
    {
    }

    public function output(): string
    {
        $html = View::make($this->view, $this->data)->render();
        return "%PDF-1.4 (Mocked PDF)\n" . $html;
    }
}
