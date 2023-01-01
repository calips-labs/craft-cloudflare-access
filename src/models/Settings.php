<?php

namespace calips\cfaccess\models;

use Craft;
use craft\base\Model;
use craft\helpers\App;

/**
 * Cloudflare Access settings
 */
class Settings extends Model
{
    public bool|string $autologin_cp = false;
    public bool|string $autologin_frontend = false;
    public ?string $issuer = null;
    public ?string $aud = null;

    public function defineRules(): array
    {
        return [
            [['autologin_cp', 'autologin_frontend'], 'safe'],
            [
                ['issuer', 'aud'],
                'required',
                'when' => function (self $model) {
                    return $model->isAutoLoginCp();
                }
            ],
            [['issuer', 'aud'], 'string'],
        ];
    }

    /**
     * @return string|null
     */
    public function getIssuer(): ?string
    {
        return App::parseEnv($this->issuer);
    }

    /**
     * @return string|null
     */
    public function getAud(): ?string
    {
        return App::parseEnv($this->aud);
    }

    /**
     * @return bool
     */
    public function isAutoLoginCp(): bool
    {
        return App::parseBooleanEnv($this->autologin_cp);
    }

    public function isAutoLoginFrontend(): bool
    {
        return App::parseBooleanEnv($this->autologin_frontend);
    }
}
