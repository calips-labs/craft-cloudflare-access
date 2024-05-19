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

        if (!Application::getInstance()->user->isGuest) {
            // User already logged in
            return;
        }

        if (Application::getInstance()->request->isCpRequest && !$plugin->settings->isAutoLoginCp()) {
            // Request is for CP, and auto sign in is not enabled for control panel
            return;
        }

        if (!Application::getInstance()->request->isCpRequest && !$plugin->settings->isAutoLoginFrontend()) {
            // Request is for frontend, and auto sign in is not enabled for frontend
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
            // Activate pending user (we assume identity/e-mail have been verified through Cloudflare IDP):
            Craft::$app->users->activateUser($user);
        }

        $userSession = Craft::$app->getUser();
        $userSession->enableAutoLogin = true;

        if (!$userSession->login($user)) {
            // Error occurred while logging in
            Craft::error('Authentication error while auto signing in '. $user->username, 'cloudflare-access');
            return;
        }

        Craft::$app->session->set('cloudflare-access-session', true);
    }
}
