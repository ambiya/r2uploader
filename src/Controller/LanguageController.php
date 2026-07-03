<?php
declare(strict_types=1);

namespace R2Uploader\Controller;

use R2Uploader\Http\Request;
use R2Uploader\Http\Response;

class LanguageController extends BaseController
{
    public function switchLang(Request $request): Response
    {
        $lang = $request->query('l');
        if (in_array($lang, ['id', 'en'], true)) {
            $_SESSION['lang'] = $lang;
        }

        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        return $this->redirect($referer);
    }
}
