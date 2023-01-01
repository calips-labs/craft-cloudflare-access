<?php

namespace calips\cfaccess\services;

use calips\cfaccess\CloudflareAccess;
use Craft;
use craft\web\Application;
use yii\base\Component;

/**
 * Login service
 */
class Login extends Component
{
    public function attemptAutoLogin()
    {
        $plugin = CloudflareAccess::getInstance();

        if (!$plugin->settings->enable) {
            // Plugin not enabled
            return;
        }

        if (!Application::getInstance()->user->isGuest) {
            // User already logged in
            return;
        }

        if (!Application::getInstance()->request->isCpRequest) {
            // Not a control panel request
            // TODO: also allow non-CP requests
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
        $user = Craft::$app->users->getUserByUsernameOrEmail($validationResult->username);

        if ($user == null) {
            // Token valid, but no user found
            return;
        }

        if ($user->suspended || (!$user->active && !$user->pending)) {
            // User found, but is suspended or inactive in Craft CMS
            return;
        }

        if ($user->pending) {
            // Active pending user (we assume identity/e-mail have been verified through Cloudflare IDP):
            Craft::$app->users->activateUser($user);
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
    }
}
