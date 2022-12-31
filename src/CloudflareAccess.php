<?php

namespace calips\cfaccess;

use Craft;
use calips\cfaccess\models\Settings;
use calips\cfaccess\services\CloudflareValidation;
use calips\cfaccess\utilities\CfAccessTest;
use craft\base\Model;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Utilities;
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
 * @property-read Settings $settings
 * @property-read CloudflareValidation $cloudflareValidation
 */
class CloudflareAccess extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = true;

    public static function config(): array
    {
        return [
            'components' => ['cloudflareValidation' => CloudflareValidation::class],
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
                $plugin = CloudflareAccess::getInstance();

                if (!$plugin->settings->enable) {
                    // Plugin not enabled
                    return;
                }

                if (!Application::getInstance()->request->isCpRequest) {
                    // Not a control panel request
                    // TODO: also allow non-CP requests
                    return;
                }

                if (!Application::getInstance()->user->isGuest) {
                    // User already logged in
                    return;
                }

                // Check whether we can log in this user using Cloudflare Access
                $jwt = $plugin->cloudflareValidation->getJwtFromHeaders();

                if ($jwt == null) {
                    // No token set
                    return;
                }

                // Validate token
                $validationResult = $plugin->cloudflareValidation->verifyJwt($jwt);

                if (!$validationResult->valid) {
                    // Token invalid
                    return;
                }

                // Find user:
                $user = Craft::$app->getUsers()->getUserByUsernameOrEmail($validationResult->username);

                if ($user == null) {
                    // Token valid, but no user found
                    return;
                }

                $userSession = Craft::$app->getUser();

                if (!$userSession->login($user)) {
                    // Error occurred while logging in
                    return;
                }

                $returnUrl = $userSession->getReturnUrl();

                if ($returnUrl != null) {
                    Craft::$app->response->redirect($returnUrl);
                }
            });
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES, function (RegisterComponentTypesEvent $event) {
            $event->types[] = CfAccessTest::class;
        });
    }
}
