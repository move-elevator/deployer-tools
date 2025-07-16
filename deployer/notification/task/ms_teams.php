<?php

namespace Deployer;


use Deployer\Exception\GracefulShutdownException;
use Deployer\Utility\Httpie;

task('msteams:notify', function () {
    sendMessage(get('msteams_text'), get('msteams_color'));
})->desc('Send ms teams notification');


/**
 * @param string $message Message to be displayed in MS Teams.
 * @param string $color Highlight color. Not in use with webhook v2.
 * @return void
 */
function sendMessage(string $message = '', string $color = ''): void
{
    if (!empty(getenv('DEPLOYER_CONFIG_NOTIFICATION_MUTE'))) {
        debug("skipping notification because of DEPLOYER_CONFIG_NOTIFICATION_MUTE environment variable");
        return;
    }

    $msTeamsWebhook = has('msteams_webhook') ? get('msteams_webhook') : getenv('DEPLOYER_CONFIG_NOTIFICATION_MSTEAMS_WEBHOOK');
    if (!$msTeamsWebhook) {
        throw new GracefulShutdownException('Missing MS Teams webhook for notification task. Use "msteams_webhook" deployer configuration or "DEPLOYER_CONFIG_NOTIFICATION_MSTEAMS_WEBHOOK" environment variable to define the necessary webhook url.');
    }

    if (has('msteams_webhook_version') && get('msteams_webhook_version') == 2) {
      // Make it possible to define entirely customized messages and only use the simple card as a fallback.
      if (has('msteams_request_body')) {
        $body = get('msteams_request_body');
      } else {
        $body = json_encode([
          "type" => "message",
          "attachments" => [
            [
              "contentType" => "application/vnd.microsoft.card.adaptive",
              "content" => [
                "\$schema" => "http://adaptivecards.io/schemas/adaptive-card.json",
                "type" => "AdaptiveCard",
                "version" => "1.4",
                "msTeams" => [
                  'width' => "full",
                ],
                "body" => [
                  [
                    "type" => "TextBlock",
                    "text" => $message,
                    "size" => "medium",
                    "wrap" => true,
                  ],
                ],
              ],
            ],
          ],
        ], \JSON_UNESCAPED_SLASHES);
      }
    } else {
      $body = json_encode([
        "themeColor" => $color,
        'text'       => $message
      ]);
    }

    $curlTimeout = getenv('DEPLOYER_CONFIG_NOTIFICATION_MSTEAMS_TIMEOUT') ?: 10;
    Httpie::post(get('msteams_webhook'))
        ->setopt(CURLOPT_TIMEOUT, $curlTimeout)
        ->body($body)
        ->send();
}
