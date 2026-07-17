<?php
declare(strict_types=1);

namespace R2Uploader;

use Aws\S3\S3Client;
use R2Uploader\Auth\SessionAuth;
use R2Uploader\Auth\UserManager;
use R2Uploader\Config\ConfigLoader;
use R2Uploader\Controller\AuthController;
use R2Uploader\Controller\DashboardController;
use R2Uploader\Controller\FileController;
use R2Uploader\Controller\HomeController;
use R2Uploader\Controller\SettingsController;
use R2Uploader\Controller\UploadController;
use R2Uploader\Controller\UserController;
use R2Uploader\Http\ErrorHandler;
use R2Uploader\Http\Middleware\AuthMiddleware;
use R2Uploader\Http\Middleware\CsrfMiddleware;
use R2Uploader\Http\Request;
use R2Uploader\Http\Response;
use R2Uploader\Security\Csrf;
use R2Uploader\Service\ActivityLogger;
use R2Uploader\Service\BucketResolver;
use R2Uploader\Service\R2Service;
use R2Uploader\Service\SettingManager;

/**
 * Application bootstrap — initialises the container, registers routes, dispatches requests.
 */
class App
{
    private Container $container;

    public function __construct()
    {
        $this->initSession();
        $this->initTranslator();
        $this->container = $this->buildContainer();
        $this->migrateEnvAdmin();
    }

    /**
     * Initialise translator service.
     */
    private function initTranslator(): void
    {
        $langPath = dirname(__DIR__) . '/lang';
        $translator = new \R2Uploader\Service\Translator($langPath, 'en', 'id');
        if (isset($_SESSION['lang'])) {
            $translator->setLocale($_SESSION['lang']);
        }
    }

    /**
     * Run the application: dispatch the current request and send the response.
     */
    public function run(): void
    {
        $request      = $this->container->get('request');
        $router       = $this->buildRouter();
        $errorHandler = $this->container->get('errorHandler');

        $response = $errorHandler->handle(
            fn() => $router->dispatch($request),
            $request
        );

        $response->send();
    }

    /**
     * Initialise PHP session with security flags.
     */
    private function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'secure'   => $isSecure,
                'httponly'  => true,
                'samesite'  => 'Strict',
            ]);
            session_start();
        }
    }

    /**
     * Build the DI container with all service registrations.
     */
    private function buildContainer(): Container
    {
        $c = new Container();

        // Database path
        $dbPath = dirname(__DIR__) . '/data/database.sqlite';
        if (!is_dir(dirname($dbPath))) {
            mkdir(dirname($dbPath), 0755, true);
        }

        // Core services
        $c->set('settingManager', fn() => new SettingManager($dbPath));

        $c->set('config', function (Container $c) {
            $loader = new ConfigLoader($c->get('settingManager'));
            return $loader->load();
        });

        $c->set('configError', function (Container $c) {
            $config = $c->get('config');
            if (empty($config['accountId']) || empty($config['accessKeyId']) || empty($config['secretAccessKey'])) {
                return 'Cloudflare R2 configuration is incomplete in the <code>.env</code> file. '
                    . 'Please fill in <code>R2_ACCOUNT_ID</code>, <code>R2_ACCESS_KEY_ID</code>, and <code>R2_SECRET_ACCESS_KEY</code>.';
            }
            return '';
        });

        $c->set('r2', function (Container $c) {
            $config = $c->get('config');
            if (empty($config['accountId']) || empty($config['accessKeyId']) || empty($config['secretAccessKey'])) {
                return null;
            }
            try {
                $s3Client = new S3Client([
                    'version'     => 'latest',
                    'region'      => 'auto',
                    'endpoint'    => "https://{$config['accountId']}.r2.cloudflarestorage.com",
                    'credentials' => [
                        'key'    => $config['accessKeyId'],
                        'secret' => $config['secretAccessKey'],
                    ],
                ]);
                return new R2Service($s3Client);
            } catch (\Exception $e) {
                return null;
            }
        });

        $c->set('bucketResolver', function (Container $c) {
            $config = $c->get('config');
            return new BucketResolver($config['buckets']);
        });

        $c->set('userManager', fn() => new UserManager($dbPath));

        $c->set('auth', function (Container $c) {
            return new SessionAuth($c->get('userManager'));
        });

        $c->set('logger', function (Container $c) use ($dbPath) {
            return new ActivityLogger($dbPath, $c->get('auth'));
        });

        $c->set('csrf', fn() => new Csrf());

        $c->set('request', fn() => Request::createFromGlobals());

        $c->set('errorHandler', function (Container $c) {
            $config = $c->get('config');
            return new ErrorHandler($config['debug']);
        });

        // Middleware instances
        $c->set('authMiddleware', function (Container $c) {
            return new AuthMiddleware($c->get('auth'));
        });

        $c->set('adminMiddleware', function (Container $c) {
            return new AuthMiddleware($c->get('auth'), 'admin');
        });

        $c->set('csrfMiddleware', function (Container $c) {
            return new CsrfMiddleware($c->get('csrf'));
        });

        $c->set('fileIndex', function (Container $c) use ($dbPath) {
            $pdo = new \PDO('sqlite:' . $dbPath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            return new \R2Uploader\Service\R2FileIndexService($pdo);
        });

        // Controllers
        $c->set('homeCtrl', fn() => new HomeController());

        $c->set('authCtrl', function (Container $c) {
            return new AuthController($c->get('auth'), $c->get('csrf'));
        });

        $c->set('langCtrl', fn() => new \R2Uploader\Controller\LanguageController());

        $c->set('uploadCtrl', function (Container $c) {
            $config = $c->get('config');
            $config['configError'] = $c->get('configError');
            return new UploadController(
                $c->get('r2'),
                $c->get('csrf'),
                $c->get('bucketResolver'),
                $c->get('logger'),
                $config,
                $c->get('fileIndex')
            );
        });

        $c->set('fileCtrl', function (Container $c) {
            return new FileController(
                $c->get('r2'),
                $c->get('csrf'),
                $c->get('bucketResolver'),
                $c->get('logger'),
                $c->get('fileIndex')
            );
        });

        $c->set('userCtrl', function (Container $c) {
            return new UserController(
                $c->get('auth'),
                $c->get('userManager'),
                $c->get('csrf')
            );
        });

        $c->set('dashCtrl', function (Container $c) {
            return new DashboardController(
                $c->get('csrf'),
                $c->get('logger'),
                $c->get('bucketResolver'),
                $c->get('r2'),
                $c->get('fileIndex')
            );
        });

        $c->set('settingsCtrl', function (Container $c) {
            $config = $c->get('config');
            return new SettingsController(
                $c->get('settingManager'),
                $c->get('csrf'),
                $c->get('logger'),
                $config
            );
        });

        return $c;
    }

    /**
     * Migrate initial admin from .env if DB is empty.
     */
    private function migrateEnvAdmin(): void
    {
        $lockFile = dirname(__DIR__) . '/data/.admin_migrated';
        if (file_exists($lockFile)) {
            return;
        }

        $config = $this->container->get('config');
        $envUser = $config['auth']['user'] ?? '';
        $envPass = $config['auth']['pass'] ?? '';
        
        $migrated = $this->container->get('userManager')->migrateFromEnv($envUser, $envPass);
        if ($migrated) {
            touch($lockFile);
        }
    }

    /**
     * Build the router with all application routes and middleware.
     */
    private function buildRouter(): Router
    {
        $c = $this->container;
        $router = new Router();

        $authMw  = $c->get('authMiddleware');
        $adminMw = $c->get('adminMiddleware');
        $csrfMw  = $c->get('csrfMiddleware');

        // --- Public routes (no middleware) ---
        $router->get(null, fn(Request $r) => $c->get('homeCtrl')->index());
        $router->get('login', fn(Request $r) => $c->get('authCtrl')->showLogin());
        $router->post('login', fn(Request $r) => $c->get('authCtrl')->handleLogin($r));
        $router->get('logout', fn(Request $r) => $c->get('authCtrl')->logout());
        $router->get('lang', fn(Request $r) => $c->get('langCtrl')->switchLang($r));

        // --- Authenticated routes ---
        $router->get('upload', fn(Request $r) => $c->get('uploadCtrl')->showForm(), [$authMw]);
        $router->post('upload', fn(Request $r) => $c->get('uploadCtrl')->handleUpload($r), [$authMw, $csrfMw]);
        $router->get('list', fn(Request $r) => $c->get('fileCtrl')->list($r), [$authMw]);
        $router->get('api_list', fn(Request $r) => $c->get('fileCtrl')->apiList($r), [$authMw]);
        $router->post('delete', fn(Request $r) => $c->get('fileCtrl')->delete($r), [$authMw, $csrfMw]);
        $router->post('rename', fn(Request $r) => $c->get('fileCtrl')->rename($r), [$authMw, $csrfMw]);
        $router->post('bulk_delete', fn(Request $r) => $c->get('fileCtrl')->bulkDelete($r), [$authMw, $csrfMw]);
        $router->post('bulk_download', fn(Request $r) => $c->get('fileCtrl')->bulkDownload($r), [$authMw, $csrfMw]);
        $router->post('create_folder', fn(Request $r) => $c->get('fileCtrl')->createFolder($r), [$authMw, $csrfMw]);
        $router->post('sync_index', fn(Request $r) => $c->get('fileCtrl')->syncIndex($r), [$authMw, $csrfMw]);

        // --- Admin routes ---
        $router->get('dashboard', fn(Request $r) => $c->get('dashCtrl')->index(), [$adminMw]);
        $router->post('dashboard_clear_logs', fn(Request $r) => $c->get('dashCtrl')->clearLogs($r), [$adminMw, $csrfMw]);
        $router->get('users', fn(Request $r) => $c->get('userCtrl')->listUsers(), [$adminMw]);
        $router->post('user_create', fn(Request $r) => $c->get('userCtrl')->createUser($r), [$adminMw, $csrfMw]);
        $router->post('user_update', fn(Request $r) => $c->get('userCtrl')->updateUser($r), [$adminMw, $csrfMw]);
        $router->post('user_delete', fn(Request $r) => $c->get('userCtrl')->deleteUser($r), [$adminMw, $csrfMw]);
        $router->get('settings', fn(Request $r) => $c->get('settingsCtrl')->showSettings(), [$adminMw]);
        $router->post('settings_update', fn(Request $r) => $c->get('settingsCtrl')->updateSettings($r), [$adminMw, $csrfMw]);

        return $router;
    }
}

