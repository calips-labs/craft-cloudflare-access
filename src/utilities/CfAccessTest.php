<?php

namespace calips\cfaccess\utilities;

use calips\cfaccess\CloudflareAccess;
use Craft;
use craft\base\Utility;

/**
 * Cf Access Test utility
 */
class CfAccessTest extends Utility
{
    public static function displayName(): string
    {
        return Craft::t('cloudflare-access', 'Cloudflare Access');
    }

    static function id(): string
    {
        return 'cf-access-test';
    }

    public static function iconPath(): ?string
    {
        $iconPath = Craft::getAlias('@vendor/calips-labs/craft-cloudflare-access/src/icon.svg');

        if (!is_string($iconPath)) {
            return null;
        }

        return $iconPath;
    }

    static function contentHtml(): string
    {
        $plugin = CloudflareAccess::getInstance();
        $jwt = $plugin->cloudflareValidation->getJwtFromHeaders();

        $validationResult = null;

        if ($jwt != null) {
            $validationResult = $plugin->cloudflareValidation->verifyJwt($jwt);
        }

        return Craft::$app->getView()->renderTemplate('cloudflare-access/_utility', [
            'jwt' => $jwt,
            'result' => $validationResult,
            'issuer' => $plugin->settings->getIssuer(),
            'aud' => $plugin->settings->getAud(),
            'autologin_cp' => $plugin->settings->isAutoLoginCp(),
            'autologin_frontend' => $plugin->settings->isAutoLoginFrontend(),
        ]);
    }
}
