<?php

namespace calips\cfaccess;

use Craft;
use calips\cfaccess\models\Settings;
use craft\base\Model;
use craft\base\Plugin;
use craft\web\Application;
use yii\base\Event;

/**
 * Cloudflare Access plugin
 *
 * @method static CloudflareAccess getInstance()
 * @method Settings getSettings()
 * @author Calips <support@calips.nl>
 * @copyright Calips
 * @license https://craftcms.github.io/license/ Craft License
 */
class CloudflareAccess extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => [
                // Define component configs here...
            ],
        ];
    }

    public function init()
    {
        parent::init();

        // Defer most setup tasks until Craft is fully initialized
        Craft::$app->onInit(function () {
            $this->attachEventHandlers();
        });
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
        // Check whether this plugin is enabled
        if (!$this->getSettings()->enable) {
            return;
        }

        Event::on(
            Application::class,
            Application::EVENT_BEFORE_ACTION,
            function (Event $event) {
                if (!CloudflareAccess::getInstance()->getSettings()->enable) {
                    // Plugin not enabled
                    return;
                }

                if (!Application::getInstance()->request->isCpRequest) {
                    // Not a control panel request
                    return;
                }

                if (!Application::getInstance()->user->isGuest) {
                    // User already logged in
                    return;
                }

                if (Application::getInstance()->controller->route == 'users/login') {
                    // CP login screen.
                    // Check whether we can log in this user using Cloudflare Access

                    // TODO
                }
            });
    }
}
