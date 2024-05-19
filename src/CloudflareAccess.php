<?php

namespace calips\cfaccess;

use Craft;
use calips\cfaccess\models\Settings;
use calips\cfaccess\services\CloudflareValidation;
use calips\cfaccess\services\Login;
use calips\cfaccess\utilities\CfAccessTest;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Utilities;
use craft\web\Application;
use craft\web\User;
use yii\base\Event;

/**
 * Cloudflare Access plugin
 *
 * @method static CloudflareAccess getInstance()
 * @method Settings getSettings()
 * @author Calips <support@calips.nl>
 * @copyright Calips
 * @license https://craftcms.github.io/license/ Craft License
 * @property-read Settings $settings
 * @property-read CloudflareValidation $cloudflareValidation
 * @property-read Login $login
 */
class CloudflareAccess extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                'cloudflareValidation' => CloudflareValidation::class,
                'login' => Login::class,
            ],
        ];
    }

    public function init()
    {
        parent::init();

        if (method_exists(Craft::$app, 'onInit')) {
            // Defer most setup tasks until Craft is fully initialized
            Craft::$app->onInit(function () {
                $this->attachEventHandlers();
            });
        } else {
            $this->attachEventHandlers();
        }
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('cloudflare-access/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        Event::on(
            Application::class,
            Application::EVENT_BEFORE_ACTION,
            function (Event $event) {
                // Check whether we should automatically sign in a user
                $this->login->attemptAutoLogin();
            }
        );

        // Register the utility
        if (Craft::$app->version < '5.0.0') {
            Event::on(
                Utilities::class,
                Utilities::EVENT_REGISTER_UTILITY_TYPES,
                function (RegisterComponentTypesEvent $event) {
                    $event->types[] = CfAccessTest::class;
                }
            );
        } else {
            Event::on(
                Utilities::class,
                Utilities::EVENT_REGISTER_UTILITIES,
                function (RegisterComponentTypesEvent $event) {
                    $event->types[] = CfAccessTest::class;
                }
            );
        }

        // If user has session through Cloudflare Access, log them also out through Cloudflare:
        Event::on(
            User::class,
            User::EVENT_AFTER_LOGOUT,
            function (Event $event) {
                $session = Craft::$app->session;
                if ($session->has('cloudflare-access-session')) {
                    $session->remove('cloudflare-access-session');

                    // Redirect to CF logout
                    Craft::$app->response->redirect($this->getSettings()->getLogoutUrl());
                    Craft::$app->end();
                }
            }
        );
    }
}
