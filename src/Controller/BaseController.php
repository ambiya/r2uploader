<?php
declare(strict_types=1);

namespace R2Uploader\Controller;

use R2Uploader\Http\Response;
use R2Uploader\Http\Exception\HttpException;
use R2Uploader\ViewData\LayoutViewData;
use R2Uploader\ViewData\ViewData;

abstract class BaseController
{
    /**
     * Render a PHP template and return the HTML as a string.
     *
     * @param string $template Template name without the .php extension
     * @param ViewData|array<string, mixed> $data Variables to extract into the template scope
     * @return string
     */
    protected function render(string $template, ViewData|array $data = []): string
    {
        $viewData = $data instanceof ViewData ? $data->toArray() : $data;
        extract($viewData, EXTR_SKIP);
        ob_start();
        require dirname(__DIR__, 2) . '/templates/' . $template . '.php';
        return ob_get_clean() ?: '';
    }

    /**
     * Render a template wrapped in the layout and return an HTML Response.
     *
     * @param string $title     Page title
     * @param string $template  Inner template name
     * @param ViewData|array<string, mixed> $data Template variables
     * @param string $csrfToken CSRF token for the layout
     * @param string[] $extraJs Additional JS files to load
     * @param int $statusCode   HTTP status code
     * @return Response
     */
    protected function renderPage(
        string $title,
        string $template,
        ViewData|array $data = [],
        string $csrfToken = '',
        array $extraJs = [],
        int $statusCode = 200
    ): Response {
        $innerHtml = $this->render($template, $data);
        $layoutData = new LayoutViewData($title, $csrfToken, $innerHtml, $extraJs);
        
        $html = $this->render('layout', $layoutData);
        return Response::html($html, $statusCode);
    }

    /**
     * Abort the request with an HTTP exception.
     *
     * @throws HttpException
     * @return never
     */
    protected function abort(int $code, string $message): never
    {
        throw new HttpException($code, $message);
    }

    /**
     * Create a redirect response.
     */
    protected function redirect(string $url): Response
    {
        return Response::redirect($url);
    }
}

