<?php
declare(strict_types=1);

namespace R2Uploader\Controller;

use R2Uploader\Http\Response;

class HomeController extends BaseController
{
    public function index(): Response
    {
        return $this->renderPage(__('home_title'), 'home');
    }
}

